<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Master\Company;
use App\Models\Master\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

echo "Setting up EcoGreen tenant...\n\n";

try {
    // Step 1: Register company
    echo "Step 1: Registering company...\n";
    $company = Company::on('master')->create([
        'name' => 'EcoGreen',
        'slug' => 'ecogreen',
        'database_name' => 'crm_ecogreen',
        'is_active' => true,
        'subscription_status' => 'trial',
        'max_users' => 10,
        'max_customers' => 1000,
    ]);
    echo "✓ Company registered (ID: {$company->id})\n\n";
    
    // Step 2: Configure tenant connection
    echo "Step 2: Configuring tenant connection...\n";
    Config::set('database.connections.tenant.database', 'crm_ecogreen');
    DB::purge('tenant');
    DB::reconnect('tenant');
    echo "✓ Connection configured\n\n";
    
    // Step 3: Run tenant migrations
    echo "Step 3: Running tenant migrations...\n";
    Artisan::call('migrate', [
        '--database' => 'tenant',
        '--path' => 'database/migrations/tenant',
        '--force' => true,
    ]);
    echo "✓ Tenant migrations completed\n\n";
    
    // Step 4: Run application migrations
    echo "Step 4: Running application migrations...\n";
    Artisan::call('migrate', [
        '--database' => 'tenant',
        '--force' => true,
    ]);
    echo "✓ Application migrations completed\n\n";
    
    // Step 5: Seed data
    echo "Step 5: Seeding initial data...\n";
    try {
        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--force' => true,
        ]);
        echo "✓ Initial data seeded\n\n";
    } catch (\Exception $e) {
        echo "⚠ Seeder skipped: {$e->getMessage()}\n\n";
    }
    
    // Step 6: Create admin user
    echo "Step 6: Creating admin user...\n";
    
    // User in master database
    $user = User::on('master')->create([
        'company_id' => $company->id,
        'name' => 'Andhia',
        'email' => 'andhia@ecogreen.id',
        'password' => Hash::make('andhia123@@'),
        'is_active' => true,
    ]);
    
    // User profile in tenant database
    DB::connection('tenant')->table('user_profiles')->insert([
        'master_user_id' => $user->id,
        'name' => 'Andhia',
        'email' => 'andhia@ecogreen.id',
        'role' => 'admin',
        'permissions' => json_encode(['*']),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "✓ Admin user created\n\n";
    
    // Success
    echo "═══════════════════════════════════════\n";
    echo "   TENANT CREATED SUCCESSFULLY!\n";
    echo "═══════════════════════════════════════\n";
    echo "Company Name : EcoGreen\n";
    echo "Company ID   : {$company->id}\n";
    echo "Database     : crm_ecogreen\n";
    echo "Admin Email  : andhia@ecogreen.id\n";
    echo "Admin Pass   : andhia123@@\n";
    echo "Status       : Active (Trial)\n";
    echo "═══════════════════════════════════════\n";
    
} catch (\Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
