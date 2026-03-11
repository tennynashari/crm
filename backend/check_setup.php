<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Master\Company;
use App\Models\Master\User;

echo "═══════════════════════════════════════\n";
echo "   MULTI-TENANT SETUP SUMMARY\n";
echo "═══════════════════════════════════════\n\n";

// Companies
echo "COMPANIES:\n";
echo str_repeat('-', 60) . "\n";
$companies = Company::on('master')->get();
foreach ($companies as $company) {
    echo "ID: {$company->id}\n";
    echo "Name: {$company->name}\n";
    echo "Database: {$company->database_name}\n";
    echo "Status: " . ($company->is_active ? 'Active' : 'Inactive') . "\n";
    echo "Users: " . User::on('master')->where('company_id', $company->id)->count() . "\n";
    echo str_repeat('-', 60) . "\n";
}

echo "\nUSERS IN MASTER DATABASE:\n";
echo str_repeat('-', 60) . "\n";
$users = User::on('master')->with('company')->get();
foreach ($users as $user) {
    echo "{$user->email} → {$user->company->name} (DB: {$user->company->database_name})\n";
}
echo str_repeat('-', 60) . "\n";

echo "\n✅ Multi-tenant system ready!\n";
echo "\nLogin Test:\n";
echo "1. Users dari Main Company (database: crm):\n";
echo "   - admin@flowcrm.test\n";
echo "   - sales1@flowcrm.test, sales2@flowcrm.test\n";
echo "   - marketing@flowcrm.test\n";
echo "   - manager@flowcrm.test\n\n";
echo "2. Users dari EcoGreen (database: crm_ecogreen):\n";
echo "   - andhia@ecogreen.id (password: andhia123@@)\n\n";
