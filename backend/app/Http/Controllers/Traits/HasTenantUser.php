<?php

namespace App\Http\Controllers\Traits;

use App\Models\Tenant\UserProfile;

trait HasTenantUser
{
    /**
     * Get current tenant user profile
     * 
     * In multi-tenant system, auth()->user() returns the master user.
     * This method returns the tenant user profile which has the actual
     * role and relationships to tenant data (customers, interactions, etc)
     * 
     * @return \App\Models\Tenant\UserProfile
     */
    protected function getCurrentUserProfile()
    {
        $masterUser = auth()->user();
        
        if (!$masterUser) {
            abort(401, 'Unauthenticated');
        }
        
        // First try to get from session
        $userProfileId = session('tenant_user_profile_id');
        
        if ($userProfileId) {
            $userProfile = UserProfile::find($userProfileId);
            if ($userProfile) {
                return $userProfile;
            }
        }
        
        // Fallback: find by master_user_id
        $userProfile = UserProfile::where('master_user_id', $masterUser->id)->first();
        
        if (!$userProfile) {
            abort(404, 'User profile not found in tenant database. Please logout and login again.');
        }
        
        // Cache in session for next requests
        session(['tenant_user_profile_id' => $userProfile->id]);
        
        return $userProfile;
    }
}
