<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Master\Company;
use App\Models\Master\User as MasterUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "Migrating existing company to multi-tenant...\n\n";

try {
    // Step 1: Register existing company
    echo "Step 1: Registering existing company...\n";
    
    $existingCompany = Company::on('master')->create([
        'name' => 'Main Company',
        'slug' => 'main',
        'database_name' => 'crm',  // Use existing database
        'is_active' => true,
        'subscription_status' => 'active',
        'max_users' => 100,
        'max_customers' => 10000,
    ]);
    
    echo "✓ Company registered (ID: {$existingCompany->id})\n";
    echo "  Name: Main Company\n";
    echo "  Database: crm\n\n";
    
    // Step 2: Migrate existing users from crm.users to crm_master.users
    echo "Step 2: Migrating existing users...\n";
    
    // Get existing users from crm database
    $existingUsers = DB::connection('pgsql')->table('users')->get();
    
    echo "Found {$existingUsers->count()} users in crm database\n";
    
    foreach ($existingUsers as $oldUser) {
        // Create user in master database
        $masterUser = MasterUser::on('master')->create([
            'company_id' => $existingCompany->id,
            'name' => $oldUser->name,
            'email' => $oldUser->email,
            'password' => $oldUser->password,  // Already hashed
            'is_active' => $oldUser->is_active ?? true,
            'created_at' => $oldUser->created_at,
            'updated_at' => $oldUser->updated_at,
        ]);
        
        // Create user profile in tenant database (crm)
        DB::connection('pgsql')->table('user_profiles')->insert([
            'master_user_id' => $masterUser->id,
            'name' => $oldUser->name,
            'email' => $oldUser->email,
            'role' => $oldUser->role ?? 'sales',
            'permissions' => json_encode([]),
            'is_active' => $oldUser->is_active ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "  ✓ Migrated: {$oldUser->email}\n";
    }
    
    echo "\n";
    
    // Step 3: Summary
    echo "═══════════════════════════════════════\n";
    echo "   MIGRATION COMPLETED!\n";
    echo "═══════════════════════════════════════\n";
    echo "Existing Company:\n";
    echo "  - Name: Main Company\n";
    echo "  - Database: crm\n";
    echo "  - Users: {$existingUsers->count()}\n\n";
    echo "New Tenant:\n";
    echo "  - Name: EcoGreen\n";
    echo "  - Database: crm_ecogreen\n";
    echo "  - Users: 1 (andhia@ecogreen.id)\n\n";
    echo "All users can now login with existing credentials!\n";
    echo "═══════════════════════════════════════\n";
    
} catch (\Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
