<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DEBUG MULTI-TENANT LOGIN ===\n\n";

// Check companies in master database
echo "1. COMPANIES IN MASTER DATABASE:\n";
$companies = DB::connection('master')
    ->table('companies')
    ->select('id', 'name', 'database_name', 'is_active')
    ->get();

foreach ($companies as $company) {
    echo sprintf(
        "  ID: %d | Name: %-20s | Database: %-15s | Active: %s\n",
        $company->id,
        $company->name,
        $company->database_name,
        $company->is_active ? 'Yes' : 'No'
    );
}

// Check users in master database
echo "\n2. USERS IN MASTER DATABASE:\n";
$users = DB::connection('master')
    ->table('users')
    ->select('id', 'email', 'name', 'company_id', 'is_active')
    ->get();

foreach ($users as $user) {
    $company = $companies->firstWhere('id', $user->company_id);
    $companyName = $company ? $company->name : 'N/A';
    echo sprintf(
        "  ID: %d | Email: %-25s | Company: %-20s | Active: %s\n",
        $user->id,
        $user->email,
        $companyName,
        $user->is_active ? 'Yes' : 'No'
    );
}

// Test login flow for EcoGreen
echo "\n3. SIMULATE LOGIN: andhia@ecogreen.id\n";
$ecoUser = $users->firstWhere('email', 'andhia@ecogreen.id');

if (!$ecoUser) {
    echo "  ❌ User not found in master database!\n";
} else {
    echo "  ✅ Master user found:\n";
    echo "    - ID: {$ecoUser->id}\n";
    echo "    - Email: {$ecoUser->email}\n";
    echo "    - Company ID: {$ecoUser->company_id}\n";
    
    $ecoCompany = $companies->firstWhere('id', $ecoUser->company_id);
    if ($ecoCompany) {
        echo "    - Company: {$ecoCompany->name}\n";
        echo "    - Database: {$ecoCompany->database_name}\n";
        
        // Check if database exists
        $dbExists = DB::connection('master')
            ->select("SELECT 1 FROM pg_database WHERE datname = ?", [$ecoCompany->database_name]);
        
        if ($dbExists) {
            echo "    - Database exists: ✅\n";
            
            // Test connection to EcoGreen database
            try {
                DB::purge('tenant');
                config(['database.connections.tenant.database' => $ecoCompany->database_name]);
                DB::reconnect('tenant');
                
                $currentDb = DB::connection('tenant')->select('SELECT current_database()')[0]->current_database;
                echo "    - Connected to: $currentDb ✅\n";
                
                // Check user_profiles in EcoGreen DB
                $profiles = DB::connection('tenant')->table('user_profiles')->count();
                echo "    - User profiles in {$ecoCompany->database_name}: $profiles\n";
                
                // Check customers in EcoGreen DB
                $customers = DB::connection('tenant')->table('customers')->count();
                echo "    - Customers in {$ecoCompany->database_name}: $customers\n";
                
                if ($customers > 0) {
                    echo "\n    ⚠️  WARNING: EcoGreen database has $customers customers!\n";
                    echo "    This should be 0 or only EcoGreen's customers.\n";
                }
                
            } catch (\Exception $e) {
                echo "    - Connection FAILED: {$e->getMessage()} ❌\n";
            }
        } else {
            echo "    - Database exists: ❌ NOT FOUND!\n";
        }
    }
}

// Test login flow for Main Company
echo "\n4. SIMULATE LOGIN: admin@flowcrm.test\n";
$mainUser = $users->firstWhere('email', 'admin@flowcrm.test');

if (!$mainUser) {
    echo "  ❌ User not found in master database!\n";
} else {
    echo "  ✅ Master user found:\n";
    echo "    - ID: {$mainUser->id}\n";
    echo "    - Email: {$mainUser->email}\n";
    echo "    - Company ID: {$mainUser->company_id}\n";
    
    $mainCompany = $companies->firstWhere('id', $mainUser->company_id);
    if ($mainCompany) {
        echo "    - Company: {$mainCompany->name}\n";
        echo "    - Database: {$mainCompany->database_name}\n";
        
        // Test connection
        try {
            DB::purge('tenant');
            config(['database.connections.tenant.database' => $mainCompany->database_name]);
            DB::reconnect('tenant');
            
            $currentDb = DB::connection('tenant')->select('SELECT current_database()')[0]->current_database;
            echo "    - Connected to: $currentDb ✅\n";
            
            $customers = DB::connection('tenant')->table('customers')->count();
            echo "    - Customers in {$mainCompany->database_name}: $customers\n";
            
        } catch (\Exception $e) {
            echo "    - Connection FAILED: {$e->getMessage()} ❌\n";
        }
    }
}

echo "\n=================================\n";
echo "\nCONCLUSION:\n";
echo "- If EcoGreen database_name is correct → Check session persistence\n";
echo "- If EcoGreen database_name is wrong → Fix in master.companies table\n";
echo "- If EcoGreen has Main Company data → Wrong database assigned!\n";
echo "\n";
