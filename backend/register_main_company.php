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
    config(['database.connections.pgsql.database' => 'crm']);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
    
    // Check if user_profiles table exists, if not use users table
    try {
        $tableCheck = DB::connection('pgsql')
            ->select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'user_profiles')");
        $hasUserProfiles = $tableCheck[0]->exists;
    } catch (\Exception $e) {
        $hasUserProfiles = false;
    }
    
    if ($hasUserProfiles) {
        echo "Using user_profiles table...\n";
        $users = DB::connection('pgsql')->table('user_profiles')->get();
    } else {
        echo "Using users table (existing database)...\n";
        $users = DB::connection('pgsql')->table('users')->get();
    }
    
    if ($users->isEmpty()) {
        echo "⚠️  No users found in Main Company database\n\n";
    } else {
        echo "Found {$users->count()} users. Registering in master database...\n\n";
        
        foreach ($users as $user) {
            // Check if user already exists in master
            $existingUser = DB::connection('master')
                ->table('users')
                ->where('email', $user->email)
                ->first();
            
            if ($existingUser) {
                echo "   ℹ️  User already exists: {$user->email}\n";
                continue;
            }
            
            // Register user in master database with default password
            $userId = DB::connection('master')
                ->table('users')
                ->insertGetId([
                    'company_id' => $company->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => bcrypt('password123'), // Default password
                    'email_verified_at' => now(),
                    'created_at' => $user->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            
            echo "   ✅ Registered: {$user->email} (ID: {$userId})\n";
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
