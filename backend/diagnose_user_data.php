<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSTIC: User Data Access ===\n\n";

// Set tenant database connection
DB::purge('tenant');
config(['database.connections.tenant.database' => 'crm']);
DB::reconnect('tenant');

echo "Connected to database: crm\n\n";

// 1. Check user_profiles
echo "1. USER PROFILES:\n";
$profiles = DB::connection('tenant')->table('user_profiles')
    ->select('id', 'email', 'name', 'role', 'master_user_id', 'is_active')
    ->orderBy('id')
    ->get();

foreach ($profiles as $profile) {
    echo sprintf(
        "  ID: %d | Email: %s | Name: %s | Role: %s | Master ID: %d | Active: %s\n",
        $profile->id,
        $profile->email,
        $profile->name,
        $profile->role,
        $profile->master_user_id,
        $profile->is_active ? 'Yes' : 'No'
    );
}

echo "\n2. CUSTOMERS COUNT:\n";
$totalCustomers = DB::connection('tenant')->table('customers')->count();
echo "  Total customers: $totalCustomers\n";

echo "\n3. CUSTOMERS BY ASSIGNED_SALES_ID:\n";
$customersByUser = DB::connection('tenant')->table('customers')
    ->select('assigned_sales_id', DB::raw('COUNT(*) as count'))
    ->groupBy('assigned_sales_id')
    ->orderBy('assigned_sales_id')
    ->get();

foreach ($customersByUser as $row) {
    $salesId = $row->assigned_sales_id ?? 'NULL';
    echo "  Sales ID $salesId: {$row->count} customers\n";
}

echo "\n4. SAMPLE CUSTOMERS:\n";
$sampleCustomers = DB::connection('tenant')->table('customers')
    ->select('id', 'company', 'assigned_sales_id')
    ->limit(10)
    ->get();

foreach ($sampleCustomers as $customer) {
    echo sprintf(
        "  Customer ID: %d | Company: %s | Assigned to: %s\n",
        $customer->id,
        $customer->company,
        $customer->assigned_sales_id ?? 'NULL'
    );
}

echo "\n5. ADMIN USER SHOULD SEE ALL CUSTOMERS:\n";
$adminProfile = $profiles->where('role', 'admin')->first();
if ($adminProfile) {
    echo "  Admin user found: {$adminProfile->email} (ID: {$adminProfile->id})\n";
    echo "  Admin should see ALL $totalCustomers customers\n";
} else {
    echo "  ❌ NO ADMIN USER FOUND!\n";
    echo "  Fix: UPDATE user_profiles SET role='admin' WHERE email='admin@flowcrm.test';\n";
}

echo "\n6. CHECK IF admin@flowcrm.test HAS ADMIN ROLE:\n";
$adminUser = $profiles->where('email', 'admin@flowcrm.test')->first();
if ($adminUser) {
    echo "  Email: {$adminUser->email}\n";
    echo "  Role: {$adminUser->role}\n";
    echo "  ID: {$adminUser->id}\n";
    
    if ($adminUser->role !== 'admin') {
        echo "\n  ⚠️  MASALAH DITEMUKAN!\n";
        echo "  User admin@flowcrm.test role-nya: '{$adminUser->role}', bukan 'admin'\n";
        echo "\n  SOLUSI: Run command di server:\n";
        echo "  psql -U crm -d crm -h 127.0.0.1 -c \"UPDATE user_profiles SET role='admin' WHERE email='admin@flowcrm.test';\"\n";
    } else {
        echo "\n  ✅ Role sudah benar (admin)\n";
    }
} else {
    echo "  ❌ User admin@flowcrm.test NOT FOUND in user_profiles!\n";
}

echo "\n=================================\n";
