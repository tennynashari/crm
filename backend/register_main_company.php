<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "\n";
echo "═══════════════════════════════════════\n";
echo "   REGISTER MAIN COMPANY\n";
echo "═══════════════════════════════════════\n\n";

try {
    // Check if Main Company already exists
    $existing = DB::connection('master')
        ->table('companies')
        ->where('database_name', 'crm')
        ->first();
    
    if ($existing) {
        echo "ℹ️  Main Company already registered:\n";
        echo "   ID: {$existing->id}\n";
        echo "   Name: {$existing->name}\n";
        echo "   Database: {$existing->database_name}\n";
        echo "   Slug: {$existing->slug}\n\n";
    } else {
        // Register Main Company
        $companyId = DB::connection('master')
            ->table('companies')
            ->insertGetId([
                'name' => 'Main Company',
                'slug' => 'main-company',
                'database_name' => 'crm',
                'email' => 'admin@flowcrm.test',
                'phone' => null,
                'is_active' => true,
                'subscription_status' => 'active',
                'subscription_expires_at' => null,
                'max_users' => 100,
                'max_customers' => 10000,
                'settings' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        
        echo "✅ Main Company registered successfully!\n";
        echo "   ID: {$companyId}\n";
        echo "   Name: Main Company\n";
        echo "   Database: crm\n";
        echo "   Slug: main-company\n\n";
    }
    
    // Now get all users from crm database and register them in master users table
    echo "Registering users from Main Company database...\n";
    echo "------------------------------------------------------------\n\n";
    
    // Get or use company
    $company = $existing ?? DB::connection('master')
        ->table('companies')
        ->where('database_name', 'crm')
        ->first();
    
    // Switch to tenant database
    config(['database.connections.tenant.database' => 'crm']);
    DB::purge('tenant');
    DB::reconnect('tenant');
    
    // Get all user profiles from tenant database
    $userProfiles = DB::connection('tenant')
        ->table('user_profiles')
        ->get();
    
    if ($userProfiles->isEmpty()) {
        echo "⚠️  No users found in Main Company database\n\n";
    } else {
        echo "Found {$userProfiles->count()} users. Registering in master database...\n\n";
        
        foreach ($userProfiles as $profile) {
            // Check if user already exists in master
            $existingUser = DB::connection('master')
                ->table('users')
                ->where('email', $profile->email)
                ->first();
            
            if ($existingUser) {
                echo "   ℹ️  User already exists: {$profile->email}\n";
                continue;
            }
            
            // Register user in master database with default password
            $userId = DB::connection('master')
                ->table('users')
                ->insertGetId([
                    'company_id' => $company->id,
                    'name' => $profile->name,
                    'email' => $profile->email,
                    'password' => bcrypt('password123'), // Default password
                    'email_verified_at' => now(),
                    'created_at' => $profile->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            
            echo "   ✅ Registered: {$profile->email} (ID: {$userId})\n";
        }
    }
    
    echo "\n";
    echo "════════════════════════════════════════════════════════════\n";
    echo "✅ Registration Complete!\n";
    echo "════════════════════════════════════════════════════════════\n\n";
    
    echo "All Main Company users now have default password: password123\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
