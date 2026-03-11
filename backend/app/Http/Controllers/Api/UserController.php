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
        try {
            $companyId = session('company_id');
            $tenantDb = session('tenant_db');
            
            Log::info('UserController::index called', [
                'company_id' => $companyId,
                'tenant_db' => $tenantDb,
                'role_filter' => $request->role,
                'auth_user' => auth()->id(),
            ]);
            
            if (!$companyId || !$tenantDb) {
                Log::warning('Session missing company_id or tenant_db', [
                    'session_data' => session()->all()
                ]);
                return response()->json([
                    'message' => 'Invalid session. Please login again.'
                ], 401);
            }
            
            // Query from Tenant DB (user_profiles) - already isolated by tenant
            // Tenant middleware ensures correct database is selected
            $query = DB::connection('tenant')
                ->table('user_profiles')
                ->select('id', 'master_user_id', 'name', 'email', 'role', 'phone', 'is_active', 'created_at', 'updated_at');

            // Filter by role
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            // Show all users (active and inactive) for management
            $users = $query->orderBy('name')->get();
            
            Log::info('UserController::index success', [
                'count' => $users->count(),
                'tenant_db' => $tenantDb,
            ]);

            return response()->json($users);
            
        } catch (\Exception $e) {
            Log::error('UserController::index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_id' => session('company_id'),
                'tenant_db' => session('tenant_db'),
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
                'debug' => [
                    'company_id' => session('company_id'),
                    'tenant_db' => session('tenant_db'),
                ]
            ], 500);
        }
    }

    public function show($id)
    {
        $tenantDb = session('tenant_db');
        
        if (!$tenantDb) {
            return response()->json([
                'message' => 'Invalid session. Please login again.'
            ], 401);
        }
        
        // Query from Tenant DB (user_profiles) - tenant isolation ensures company security
        $user = DB::connection('tenant')
            ->table('user_profiles')
            ->where('id', $id)
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
            'email' => 'required|email',  // Uniqueness checked below
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
            // Check email uniqueness in master DB
            $existingUser = DB::connection('master')
                ->table('users')
                ->where('email', $validated['email'])
                ->exists();
            
            if ($existingUser) {
                return response()->json([
                    'message' => 'The email has already been taken.',
                    'errors' => ['email' => ['The email has already been taken.']]
                ], 422);
            }
            
            // Step 1: Insert to Master DB (no role in master)
            $userId = DB::connection('master')->table('users')->insertGetId([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
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
            
            // Step 2: Insert to Tenant DB with master_user_id reference and role
            $profileId = DB::connection('tenant')->table('user_profiles')->insertGetId([
                'master_user_id' => $userId,  // Link to master DB user
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'is_active' => $validated['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            Log::info('User profile created in tenant DB', [
                'user_id' => $userId,
                'profile_id' => $profileId,
                'database' => $tenantDb
            ]);
            
            DB::commit();
            
            // Return user data from tenant DB
            $user = DB::connection('tenant')
                ->table('user_profiles')
                ->find($profileId);
            
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
        $tenantDb = session('tenant_db');
        
        if (!$companyId || !$tenantDb) {
            return response()->json([
                'message' => 'Invalid session. Please login again.'
            ], 401);
        }
        
        DB::beginTransaction();
        try {
            // Step 1: Get user profile from tenant DB
            // $id is user_profiles.id, not master users.id
            $userProfile = DB::connection('tenant')
                ->table('user_profiles')
                ->where('id', $id)
                ->first();
            
            if (!$userProfile) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }
            
            // Step 2: Verify master user belongs to this company
            $masterUser = DB::connection('master')
                ->table('users')
                ->where('id', $userProfile->master_user_id)
                ->where('company_id', $companyId)
                ->first();
            
            if (!$masterUser) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Access denied'
                ], 403);
            }
            
            // Step 3: Validate input
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => ['sometimes', 'required', 'email', Rule::unique('master.users', 'email')->ignore($userProfile->master_user_id)],
                'password' => 'nullable|string|min:8',
                'role' => ['sometimes', 'required', Rule::in(['admin', 'sales'])],
                'is_active' => 'boolean',
            ]);

            // Step 4: Update Master DB (only name, email, password, is_active - NO role)
            $masterData = [];
            if (isset($validated['name'])) $masterData['name'] = $validated['name'];
            if (isset($validated['email'])) $masterData['email'] = $validated['email'];
            if (isset($validated['is_active'])) $masterData['is_active'] = $validated['is_active'];
            
            if (!empty($validated['password'])) {
                $masterData['password'] = Hash::make($validated['password']);
            }
            
            if (!empty($masterData)) {
                $masterData['updated_at'] = now();
                DB::connection('master')
                    ->table('users')
                    ->where('id', $userProfile->master_user_id)
                    ->update($masterData);
                
                Log::info('User updated in master DB', ['master_user_id' => $userProfile->master_user_id]);
            }
            
            // Step 5: Update Tenant DB (name, email, role, is_active - NO password)
            $tenantData = [];
            if (isset($validated['name'])) $tenantData['name'] = $validated['name'];
            if (isset($validated['email'])) $tenantData['email'] = $validated['email'];
            if (isset($validated['role'])) $tenantData['role'] = $validated['role'];
            if (isset($validated['is_active'])) $tenantData['is_active'] = $validated['is_active'];
            
            if (!empty($tenantData)) {
                $tenantData['updated_at'] = now();
                DB::connection('tenant')
                    ->table('user_profiles')
                    ->where('id', $id)
                    ->update($tenantData);
                
                Log::info('User profile updated in tenant DB', ['profile_id' => $id]);
            }
            
            DB::commit();
            
            // Return updated user from tenant DB
            $updatedUser = DB::connection('tenant')
                ->table('user_profiles')
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
        $tenantDb = session('tenant_db');
        
        if (!$companyId || !$tenantDb) {
            return response()->json([
                'message' => 'Invalid session. Please login again.'
            ], 401);
        }
        
        DB::beginTransaction();
        try {
            // Step 1: Get user profile from tenant DB
            // $id is user_profiles.id, not master users.id
            $userProfile = DB::connection('tenant')
                ->table('user_profiles')
                ->where('id', $id)
                ->first();
            
            if (!$userProfile) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }
            
            // Step 2: Verify master user belongs to this company
            $masterUser = DB::connection('master')
                ->table('users')
                ->where('id', $userProfile->master_user_id)
                ->where('company_id', $companyId)
                ->first();
            
            if (!$masterUser) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Access denied'
                ], 403);
            }
            
            // Step 3: Prevent deleting yourself
            if ($masterUser->id === auth()->id()) {
                return response()->json([
                    'message' => 'You cannot delete your own account'
                ], 403);
            }
            
            // Step 4: Delete from Tenant DB first (FK constraints)
            DB::connection('tenant')
                ->table('user_profiles')
                ->where('id', $id)
                ->delete();
            
            Log::info('User profile deleted from tenant DB', ['profile_id' => $id]);
            
            // Step 5: Delete from Master DB
            DB::connection('master')
                ->table('users')
                ->where('id', $userProfile->master_user_id)
                ->delete();
            
            Log::info('User deleted from master DB', ['master_user_id' => $userProfile->master_user_id]);
            
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
