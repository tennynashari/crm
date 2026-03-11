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
        // Skip untuk public routes (login, register)
        if ($request->routeIs('login', 'register')) {
            return $next($request);
        }
        
        // Check authentication
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Set tenant dari session
        try {
            $this->tenantService->setTenantBySession();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Tenant context error',
                'error' => $e->getMessage()
            ], 500);
        }
        
        return $next($request);
    }
}
