<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromSession
{
    protected $tenantService;
    
    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }
    
    public function handle(Request $request, Closure $next): Response
    {
        // This middleware is only applied to authenticated routes
        // So we can safely assume user is authenticated here
        
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Set tenant dari session
        try {
            $this->tenantService->setTenantBySession();
        } catch (\Exception $e) {
            \Log::error('Tenant context error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'session_data' => session()->all()
            ]);
            
            return response()->json([
                'message' => 'Tenant context error. Please logout and login again.',
                'error' => $e->getMessage()
            ], 500);
        }
        
        return $next($request);
    }
}
