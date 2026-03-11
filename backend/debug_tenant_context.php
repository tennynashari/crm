<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DEBUG TENANT CONTEXT ===\n\n";

// Check what's configured
echo "1. DATABASE CONFIGURATION:\n";
echo "  Master DB: " . config('database.connections.master.database') . "\n";
echo "  Default DB: " . config('database.connections.pgsql.database') . "\n";
echo "  Tenant DB (current config): " . config('database.connections.tenant.database', 'NOT SET') . "\n";

// Check TenantService
echo "\n2. TEST TenantService->setTenantBySession():\n";

// Simulate session data (from login)
$fakeCompanyId = 2; // Main Company ID in master
$fakeTenantDb = 'crm';
$fakeUserProfileId = 1; // admin@flowcrm.test

echo "  Simulated session data:\n";
echo "    - company_id: $fakeCompanyId\n";
echo "    - tenant_db: $fakeTenantDb\n";
echo "    - tenant_user_profile_id: $fakeUserProfileId\n";

// Manually set tenant DB (what TenantService should do)
DB::purge('tenant');
config(['database.connections.tenant.database' => $fakeTenantDb]);
DB::reconnect('tenant');

echo "\n  After manual tenant setup:\n";
echo "    Tenant DB config: " . config('database.connections.tenant.database') . "\n";

// Test connection
try {
    $result = DB::connection('tenant')->select('SELECT current_database()');
    echo "    Connected to: " . $result[0]->current_database . " ✅\n";
} catch (\Exception $e) {
    echo "    Connection FAILED: " . $e->getMessage() . " ❌\n";
}

// Test getCurrentUserProfile logic
echo "\n3. TEST getCurrentUserProfile() LOGIC:\n";

// Simulate auth()->user() returning master user ID 6
$masterUserId = 6; // admin@flowcrm.test master user ID

echo "  Master user ID: $masterUserId\n";
echo "  Looking for user_profile with master_user_id = $masterUserId\n";

$userProfile = DB::connection('tenant')
    ->table('user_profiles')
    ->where('master_user_id', $masterUserId)
    ->first();

if ($userProfile) {
    echo "  ✅ User profile found:\n";
    echo "    - ID: {$userProfile->id}\n";
    echo "    - Email: {$userProfile->email}\n";
    echo "    - Role: {$userProfile->role}\n";
} else {
    echo "  ❌ User profile NOT FOUND!\n";
    echo "  Available user_profiles:\n";
    $profiles = DB::connection('tenant')->table('user_profiles')->get(['id', 'email', 'master_user_id']);
    foreach ($profiles as $p) {
        echo "    - ID:{$p->id} | Email:{$p->email} | Master ID:{$p->master_user_id}\n";
    }
}

// Test customer query
echo "\n4. TEST CUSTOMER QUERY:\n";

if ($userProfile) {
    $query = DB::connection('tenant')->table('customers');
    
    if ($userProfile->role !== 'admin') {
        echo "  Filtering by assigned_sales_id = {$userProfile->id}\n";
        $query->where('assigned_sales_id', $userProfile->id);
    } else {
        echo "  No filter (admin role)\n";
    }
    
    $count = $query->count();
    echo "  Result: $count customers\n";
    
    if ($count > 0) {
        echo "  ✅ Query successful!\n";
    } else {
        echo "  ❌ Query returned 0 customers!\n";
    }
}

echo "\n5. CHECK DEFAULT DB CONNECTION:\n";
$currentDb = DB::connection()->getDatabaseName();
echo "  Default connection using: $currentDb\n";

if ($currentDb !== $fakeTenantDb) {
    echo "  ⚠️  WARNING: Default DB is not tenant DB!\n";
    echo "  This means models without ->on('tenant') will query wrong DB\n";
}

echo "\n=================================\n";
echo "\nNEXT STEPS:\n";
echo "1. Check if AuthController sets session data correctly on login\n";
echo "2. Check if TenantService is actually being called\n";
echo "3. Check if models use 'tenant' connection\n";
echo "\n";
