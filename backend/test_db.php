<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Database Connection Test ===\n\n";

// Test PGSQL connection (crm database - should work)
echo "1. Testing PGSQL connection (crm):\n";
try {
    $result = DB::connection('pgsql')->select('SELECT current_database() as db');
    echo "   ✓ SUCCESS - Connected to: " . $result[0]->db . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ FAILED - " . $e->getMessage() . "\n\n";
}

// Test MASTER connection (crm_master database)
echo "2. Testing MASTER connection (crm_master):\n";
try {
    $result = DB::connection('master')->select('SELECT current_database() as db');
    echo "   ✓ SUCCESS - Connected to: " . $result[0]->db . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ FAILED - " . $e->getMessage() . "\n\n";
}

echo "=== Config Values ===\n";
echo "PGSQL Password: " . config('database.connections.pgsql.password') . "\n";
echo "MASTER Password: " . config('database.connections.master.password') . "\n";
