<?php

namespace App\Services;

use App\Models\Master\Company;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TenantService
{
    protected $currentTenant = null;
    
    public function setTenantByCompany(Company $company)
    {
        if (!$company->is_active) {
            throw new \Exception('Company is inactive');
        }
        
        $this->currentTenant = $company;
        
        // Configure tenant database connection
        $this->configureTenantConnection($company->database_name);
        
        // Test connection
        try {
            DB::connection('tenant')->getPdo();
        } catch (\Exception $e) {
            throw new \Exception("Cannot connect to tenant database: {$company->database_name}");
        }
        
        return $this;
    }
    
    public function setTenantBySession()
    {
        $tenantDb = session('tenant_db');
        
        if (!$tenantDb) {
            \Log::warning('No tenant in session', [
                'session_id' => session()->getId(),
                'all_session_data' => session()->all(),
            ]);
            throw new \Exception('No tenant in session');
        }
        
        \Log::info('Setting tenant from session', [
            'tenant_db' => $tenantDb,
            'company_id' => session('company_id'),
            'session_id' => session()->getId(),
        ]);
        
        // Get company dari cache atau database
        $companyId = session('company_id');
        $company = Cache::remember("company_{$companyId}", 3600, function () use ($companyId) {
            return Company::on('master')->find($companyId);
        });
        
        if (!$company) {
            throw new \Exception('Company not found');
        }
        
        $this->setTenantByCompany($company);
        
        \Log::info('Tenant set successfully', [
            'tenant_db' => $company->database_name,
            'company_name' => $company->name,
        ]);
        
        return $this;
    }
    
    protected function configureTenantConnection(string $databaseName)
    {
        $tenantConfig = [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'database' => $databaseName,
            'username' => env('DB_USERNAME', 'crm'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ];
        
        // Set tenant connection
        Config::set('database.connections.tenant', $tenantConfig);
        
        // IMPORTANT: Set default connection to tenant database
        // This makes all models use tenant DB without explicit ->on('tenant')
        Config::set('database.default', 'tenant');
        
        // Purge & reconnect both
        DB::purge('tenant');
        DB::purge(); // Purge default connection cache
        DB::reconnect('tenant');
        DB::reconnect(); // Reconnect default
        
        \Log::info('Tenant connection configured', [
            'database' => $databaseName,
            'default_connection' => config('database.default'),
        ]);
    }
    
    public function getCurrentTenant()
    {
        return $this->currentTenant;
    }
    
    public function getTenantDatabase()
    {
        return session('tenant_db');
    }
}
