<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Master\User as MasterUser;
use App\Models\Tenant\UserProfile;

echo "=== TEST USER CONTEXT ===\n\n";

// Setup tenant connection
DB::purge('tenant');
config(['database.connections.tenant.database' => 'crm']);
DB::reconnect('tenant');

// Simulate login as admin@flowcrm.test
echo "1. SIMULATE LOGIN AS admin@flowcrm.test:\n";
$masterUser = MasterUser::on('master')->where('email', 'admin@flowcrm.test')->first();

if (!$masterUser) {
    die("❌ Master user not found!\n");
}

echo "  ✅ Master user found: ID={$masterUser->id}, Email={$masterUser->email}\n";

// Get user profile
echo "\n2. GET USER PROFILE FROM TENANT DB:\n";
$userProfile = UserProfile::where('master_user_id', $masterUser->id)->first();

if (!$userProfile) {
    die("❌ User profile not found!\n");
}

echo "  ✅ User profile found:\n";
echo "     - ID: {$userProfile->id}\n";
echo "     - Email: {$userProfile->email}\n";
echo "     - Name: {$userProfile->name}\n";
echo "     - Role: {$userProfile->role}\n";
echo "     - Master User ID: {$userProfile->master_user_id}\n";

// Test customer query
echo "\n3. TEST CUSTOMER QUERY (AS ADMIN):\n";
$query = DB::connection('tenant')->table('customers');

if ($userProfile->role !== 'admin') {
    echo "  (Filtering by assigned_sales_id = {$userProfile->id})\n";
    $query->where('assigned_sales_id', $userProfile->id);
} else {
    echo "  (No filter - admin sees all)\n";
}

$customerCount = $query->count();
echo "  Result: {$customerCount} customers\n";

if ($customerCount > 0) {
    echo "\n  ✅ SUCCESS! Admin can see {$customerCount} customers\n";
    echo "\n  Sample customers:\n";
    $samples = $query->limit(5)->get(['id', 'company', 'assigned_sales_id']);
    foreach ($samples as $c) {
        echo "    - ID:{$c->id} | {$c->company} | Sales:{$c->assigned_sales_id}\n";
    }
} else {
    echo "\n  ❌ PROBLEM: Query returned 0 customers!\n";
}

// Test as sales user
echo "\n4. TEST CUSTOMER QUERY (AS SALES - ID 2):\n";
$salesProfile = UserProfile::find(2);
echo "  User: {$salesProfile->email} (Role: {$salesProfile->role})\n";

$salesQuery = DB::connection('tenant')->table('customers')
    ->where('assigned_sales_id', $salesProfile->id);
$salesCount = $salesQuery->count();

echo "  Result: {$salesCount} customers assigned to this sales\n";

echo "\n=================================\n";
