<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\User;
use App\Models\Tenant\UserProfile;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $tenantService;
    
    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Step 1: Query master database untuk authentication
        $user = User::on('master')
            ->where('email', $request->email)
            ->with('company')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Step 2: Check active status
        if (!$user->is_active || !$user->company->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Step 3: Setup tenant database connection
        try {
            $this->tenantService->setTenantByCompany($user->company);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'email' => ['Company database connection failed. Please contact support.'],
            ]);
        }

        // Step 4: Get user profile dari tenant database
        $userProfile = UserProfile::on('tenant')
            ->where('master_user_id', $user->id)
            ->first();

        if (!$userProfile) {
            // Auto-create jika belum ada (first time login)
            $userProfile = UserProfile::on('tenant')->create([
                'master_user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'sales',
                'is_active' => true,
            ]);
        }

        // Step 5: Login & create session
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Step 6: Store tenant context in session
        session([
            'company_id' => $user->company_id,
            'tenant_db' => $user->company->database_name,
            'tenant_user_profile_id' => $userProfile->id,
        ]);

        // Step 7: Update last login
        $user->update(['last_login_at' => now()]);

        // Step 8: Return response
        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $userProfile->role,
                'company' => [
                    'id' => $user->company->id,
                    'name' => $user->company->name,
                    'database' => $user->company->database_name,
                ],
            ],
            'session_id' => session()->getId(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,sales,marketing,manager',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'user' => $user,
            'message' => 'User registered successfully',
        ], 201);
    }
}
