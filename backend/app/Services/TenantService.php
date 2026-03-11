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
            throw new \Exception('No tenant in session');
        }
        
        // Get company dari cache atau database
        $companyId = session('company_id');
        $company = Cache::remember("company_{$companyId}", 3600, function () use ($companyId) {
            return Company::on('master')->find($companyId);
        });
        
        if (!$company) {
            throw new \Exception('Company not found');
        }
        
        $this->setTenantByCompany($company);
        
        return $this;
    }
    
    protected function configureTenantConnection(string $databaseName)
    {
        Config::set('database.connections.tenant', [
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
        ]);
        
        // Purge & reconnect
        DB::purge('tenant');
        DB::reconnect('tenant');
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
