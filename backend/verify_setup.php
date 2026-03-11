<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Master\Company;

echo "═══════════════════════════════════════\n";
echo "   FINAL MULTI-TENANT SETUP\n";
echo "═══════════════════════════════════════\n\n";

// Companies
$companies = Company::on('master')->get();

foreach ($companies as $company) {
    echo "COMPANY: {$company->name}\n";
    echo "Database: {$company->database_name}\n";
    echo str_repeat('-', 60) . "\n";
    
    // Configure connection
    if ($company->database_name === 'crm') {
        $connection = 'pgsql';
    } else {
        Config::set('database.connections.tenant.database', $company->database_name);
        DB::purge('tenant');
        DB::reconnect('tenant');
        $connection = 'tenant';
    }
    
    // Count records
    $userProfiles = DB::connection($connection)->table('user_profiles')->count();
    $customers = DB::connection($connection)->table('customers')->count();
    $interactions = DB::connection($connection)->table('interactions')->count();
    $invoices = DB::connection($connection)->table('invoices')->count();
    
    echo "User Profiles: {$userProfiles}\n";
    echo "Customers: {$customers}\n";
    echo "Interactions: {$interactions}\n";
    echo "Invoices: {$invoices}\n\n";
    
    // List users
    $users = DB::connection($connection)->table('user_profiles')->get(['email', 'name', 'role']);
    echo "Users:\n";
    foreach ($users as $user) {
        echo "  - {$user->email} ({$user->role})\n";
    }
    
    echo "\n" . str_repeat('=', 60) . "\n\n";
}

echo "✅ Setup Complete!\n\n";
echo "LOGIN CREDENTIALS:\n";
echo str_repeat('-', 60) . "\n";
echo "Main Company (database: crm) - EXISTING DATA:\n";
echo "  - admin@flowcrm.test (password: existing)\n";
echo "  - sales1@flowcrm.test (password: existing)\n";
echo "  - sales2@flowcrm.test (password: existing)\n";
echo "  - marketing@flowcrm.test (password: existing)\n";
echo "  - manager@flowcrm.test (password: existing)\n\n";
echo "EcoGreen (database: crm_ecogreen) - EMPTY DATABASE:\n";
echo "  - andhia@ecogreen.id (password: andhia123@@)\n";
echo str_repeat('-', 60) . "\n";
