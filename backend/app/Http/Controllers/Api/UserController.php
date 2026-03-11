<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('company_id');
        
        if (!$companyId) {
            return response()->json([
                'message' => 'Invalid session. Please login again.'
            ], 401);
        }
        
        // Query from Master DB with company filter
        $query = DB::connection('master')
            ->table('users')
            ->where('company_id', $companyId);

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Show all users (active and inactive) for management
        $users = $query->orderBy('name')->get();

        return response()->json($users);
    }

    public function show($id)
    {
        $companyId = session('company_id');
        
        // Query from Master DB with company check
        $user = DB::connection('master')
            ->table('users')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found or access denied'
            ], 404);
        }
        
        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:master.users,email',  // Check uniqueness in master DB
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'sales'])],
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        
        // Get company context from session
        $companyId = session('company_id');
        $tenantDb = session('tenant_db');
        
        if (!$companyId || !$tenantDb) {
            return response()->json([
                'message' => 'Invalid session. Please login again.'
            ], 401);
        }

        DB::beginTransaction();
        try {
            // Step 1: Insert to Master DB
            $userId = DB::connection('master')->table('users')->insertGetId([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'company_id' => $companyId,
                'is_active' => $validated['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            Log::info('User created in master DB', [
                'user_id' => $userId,
                'email' => $validated['email'],
                'company_id' => $companyId
            ]);
            
            // Step 2: Insert to Tenant DB with SAME ID (no password in tenant)
            DB::connection('tenant')->table('user_profiles')->insert([
                'id' => $userId,  // Keep ID same for FK relationships
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'is_active' => $validated['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            Log::info('User profile created in tenant DB', [
                'user_id' => $userId,
                'database' => $tenantDb
            ]);
            
            DB::commit();
            
            // Return user data from master
            $user = DB::connection('master')
                ->table('users')
                ->find($userId);
            
            return response()->json($user, 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
                'tenant_db' => $tenantDb
            ]);
            
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $companyId = session('company_id');
        
        if (!$companyId) {
            return response()->json([
                'message' => 'Invalid session. Please login again.'
            ], 401);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('master.users')->ignore($id)],
            'password' => 'nullable|string|min:8',
            'role' => ['sometimes', 'required', Rule::in(['admin', 'sales'])],
            'is_active' => 'boolean',
        ]);

        // Only hash password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::beginTransaction();
        try {
            // Step 1: Check user belongs to company
            $user = DB::connection('master')
                ->table('users')
                ->where('id', $id)
                ->where('company_id', $companyId)
                ->first();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not found or access denied'
                ], 404);
            }
            
            // Step 2: Update Master DB
            $validated['updated_at'] = now();
            DB::connection('master')
                ->table('users')
                ->where('id', $id)
                ->update($validated);
            
            Log::info('User updated in master DB', ['user_id' => $id]);
            
            // Step 3: Sync to Tenant DB (exclude password)
            $tenantData = array_filter($validated, function($key) {
                return in_array($key, ['name', 'email', 'role', 'is_active', 'updated_at']);
            }, ARRAY_FILTER_USE_KEY);
            
            if (!empty($tenantData)) {
                DB::connection('tenant')
                    ->table('user_profiles')
                    ->where('id', $id)
                    ->update($tenantData);
                
                Log::info('User profile synced to tenant DB', ['user_id' => $id]);
            }
            
            DB::commit();
            
            // Return updated user from master
            $updatedUser = DB::connection('master')
                ->table('users')
                ->find($id);
            
            return response()->json($updatedUser);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('User update failed', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $companyId = session('company_id');
        
        if (!$companyId) {
            return response()->json([
                'message' => 'Invalid session. Please login again.'
            ], 401);
        }
        
        DB::beginTransaction();
        try {
            // Check user belongs to company
            $user = DB::connection('master')
                ->table('users')
                ->where('id', $id)
                ->where('company_id', $companyId)
                ->first();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not found or access denied'
                ], 404);
            }
            
            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                return response()->json([
                    'message' => 'You cannot delete your own account'
                ], 403);
            }
            
            // Step 1: Delete from Tenant DB first (FK constraints)
            DB::connection('tenant')
                ->table('user_profiles')
                ->where('id', $id)
                ->delete();
            
            Log::info('User profile deleted from tenant DB', ['user_id' => $id]);
            
            // Step 2: Delete from Master DB
            DB::connection('master')
                ->table('users')
                ->where('id', $id)
                ->delete();
            
            Log::info('User deleted from master DB', ['user_id' => $id]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'User deleted successfully'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('User deletion failed', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
