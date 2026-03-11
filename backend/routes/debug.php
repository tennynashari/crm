<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Debug routes - REMOVE IN PRODUCTION
 */
Route::get('/debug/session', function() {
    return response()->json([
        'auth' => [
            'check' => auth()->check(),
            'id' => auth()->id(),
            'user' => auth()->user(),
        ],
        'session' => [
            'company_id' => session('company_id'),
            'tenant_db' => session('tenant_db'),
            'tenant_user_profile_id' => session('tenant_user_profile_id'),
            'all' => session()->all(),
        ],
    ]);
});

Route::get('/debug/db-connections', function() {
    $results = [];
    
    // Test Master DB
    try {
        $masterTest = DB::connection('master')->select('SELECT 1 as test');
        $results['master'] = [
            'status' => 'OK',
            'test' => $masterTest,
            'config' => [
                'host' => config('database.connections.master.host'),
                'database' => config('database.connections.master.database'),
                'username' => config('database.connections.master.username'),
            ]
        ];
    } catch (\Exception $e) {
        $results['master'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // Test Tenant DB (pgsql)
    try {
        $pgsqlTest = DB::connection('pgsql')->select('SELECT 1 as test');
        $results['pgsql'] = [
            'status' => 'OK',
            'test' => $pgsqlTest,
            'config' => [
                'host' => config('database.connections.pgsql.host'),
                'database' => config('database.connections.pgsql.database'),
                'username' => config('database.connections.pgsql.username'),
            ]
        ];
    } catch (\Exception $e) {
        $results['pgsql'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    return response()->json($results);
});

Route::get('/debug/users', function() {
    try {
        // Check users in master DB
        $masterUsers = DB::connection('master')
            ->table('users')
            ->select('id', 'email', 'company_id', 'role', 'is_active')
            ->get();
        
        return response()->json([
            'status' => 'OK',
            'count' => $masterUsers->count(),
            'users' => $masterUsers,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'FAILED',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

Route::get('/debug/companies', function() {
    try {
        // Check companies in master DB
        $companies = DB::connection('master')
            ->table('companies')
            ->select('id', 'name', 'database_name', 'is_active')
            ->get();
        
        return response()->json([
            'status' => 'OK',
            'count' => $companies->count(),
            'companies' => $companies,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'FAILED',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
