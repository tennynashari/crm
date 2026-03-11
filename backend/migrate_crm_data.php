<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════\n";
echo "   MIGRATE CRM EXISTING DATA\n";
echo "═══════════════════════════════════════\n\n";

try {
    // Get Main Company from master
    $company = DB::connection('master')
        ->table('companies')
        ->where('database_name', 'crm')
        ->first();
    
    if (!$company) {
        echo "❌ Main Company not found\n";
        exit(1);
    }
    
    echo "Company: {$company->name}\n";
    echo "Database: {$company->database_name}\n\n";
    
    // Connect to CRM database
    config(['database.connections.pgsql.database' => 'crm']);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
    
    // Check if user_profiles table exists
    $hasUserProfiles = DB::connection('pgsql')
        ->select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'user_profiles')")[0]->exists;
    
    if (!$hasUserProfiles) {
        echo "❌ user_profiles table does not exist. Run migrations first:\n";
        echo "   php artisan migrate --database=pgsql --path=database/migrations/tenant --force\n\n";
        exit(1);
    }
    
    echo "STEP 1: Migrating users to user_profiles...\n";
    echo "------------------------------------------------------------\n";
    
    // Get all users from old users table
    $oldUsers = DB::connection('pgsql')->table('users')->get();
    
    if ($oldUsers->isEmpty()) {
        echo "⚠️  No users found in old users table\n\n";
    } else {
        foreach ($oldUsers as $oldUser) {
            // Get master user
            $masterUser = DB::connection('master')
                ->table('users')
                ->where('email', $oldUser->email)
                ->where('company_id', $company->id)
                ->first();
            
            if (!$masterUser) {
                echo "⚠️  Master user not found for: {$oldUser->email}, skipping...\n";
                continue;
            }
            
            // Check if user_profile already exists
            $existingProfile = DB::connection('pgsql')
                ->table('user_profiles')
                ->where('email', $oldUser->email)
                ->first();
            
            if ($existingProfile) {
                echo "ℹ️  User profile exists: {$oldUser->email} (ID: {$existingProfile->id})\n";
                
                // Update foreign keys to point to this user_profile
                $updates = [
                    'customers' => ['user_id'],
                    'interactions' => ['user_id'],
                    'invoices' => ['user_id'],
                ];
                
                foreach ($updates as $table => $columns) {
                    foreach ($columns as $column) {
                        $tableExists = DB::connection('pgsql')
                            ->select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '{$table}')")[0]->exists;
                        
                        if ($tableExists) {
                            $count = DB::connection('pgsql')
                                ->table($table)
                                ->where($column, $oldUser->id)
                                ->update([$column => $existingProfile->id]);
                            
                            if ($count > 0) {
                                echo "   ✅ Updated {$count} records in {$table}.{$column}\n";
                            }
                        }
                    }
                }
                
                continue;
            }
            
            // Create user_profile
            $userProfileId = DB::connection('pgsql')
                ->table('user_profiles')
                ->insertGetId([
                    'master_user_id' => $masterUser->id,
                    'name' => $oldUser->name,
                    'email' => $oldUser->email,
                    'phone' => $oldUser->phone ?? null,
                    'role' => $oldUser->role ?? 'user',
                    'is_active' => $oldUser->is_active ?? true,
                    'created_at' => $oldUser->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            
            echo "✅ Created user_profile: {$oldUser->email} (ID: {$userProfileId})\n";
            
            // Update foreign keys to point to new user_profile
            $updates = [
                'customers' => ['user_id'],
                'interactions' => ['user_id'],
                'invoices' => ['user_id'],
            ];
            
            foreach ($updates as $table => $columns) {
                foreach ($columns as $column) {
                    $tableExists = DB::connection('pgsql')
                        ->select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '{$table}')")[0]->exists;
                    
                    if ($tableExists) {
                        $count = DB::connection('pgsql')
                            ->table($table)
                            ->where($column, $oldUser->id)
                            ->update([$column => $userProfileId]);
                        
                        if ($count > 0) {
                            echo "   ✅ Updated {$count} records in {$table}.{$column}\n";
                        }
                    }
                }
            }
        }
    }
    
    echo "\n";
    echo "STEP 2: Verification...\n";
    echo "------------------------------------------------------------\n";
    
    $userProfileCount = DB::connection('pgsql')->table('user_profiles')->count();
    $customerCount = DB::connection('pgsql')->table('customers')->count();
    $interactionCount = DB::connection('pgsql')->table('interactions')->count();
    
    echo "User Profiles: {$userProfileCount}\n";
    echo "Customers: {$customerCount}\n";
    echo "Interactions: {$interactionCount}\n";
    
    echo "\n";
    echo "════════════════════════════════════════════════════════════\n";
    echo "✅ Migration Complete!\n";
    echo "════════════════════════════════════════════════════════════\n\n";
    
    echo "You can now login with admin@flowcrm.test and see all data.\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
