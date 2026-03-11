<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Database Connections...\n\n";

// Test master connection
echo "1. Testing MASTER connection (crm_master):\n";
try {
    $config = config('database.connections.master');
    echo "   Host: {$config['host']}\n";
    echo "   Database: {$config['database']}\n";
    echo "   Username: {$config['username']}\n";
    echo "   Password: " . (strlen($config['password']) > 0 ? str_repeat('*', strlen($config['password'])) : 'EMPTY') . "\n";
    
    $result = DB::connection('master')->select('SELECT current_database()');
    echo "   ✓ Connection successful! Database: " . $result[0]->current_database . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ Connection failed: " . $e->getMessage() . "\n\n";
}

// Test pgsql connection
echo "2. Testing PGSQL connection (crm):\n";
try {
    $config = config('database.connections.pgsql');
    echo "   Host: {$config['host']}\n";
    echo "   Database: {$config['database']}\n";
    echo "   Username: {$config['username']}\n";
    echo "   Password: " . (strlen($config['password']) > 0 ? str_repeat('*', strlen($config['password'])) : 'EMPTY') . "\n";
    
    $result = DB::connection('pgsql')->select('SELECT current_database()');
    echo "   ✓ Connection successful! Database: " . $result[0]->current_database . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ Connection failed: " . $e->getMessage() . "\n\n";
}

// Test tenant connection
echo "3. Testing TENANT connection:\n";
try {
    config(['database.connections.tenant.database' => 'crm_ecogreen']);
    DB::connection('tenant')->reconnect();
    
    $config = config('database.connections.tenant');
    echo "   Host: {$config['host']}\n";
    echo "   Database: {$config['database']}\n";
    echo "   Username: {$config['username']}\n";
    echo "   Password: " . (strlen($config['password']) > 0 ? str_repeat('*', strlen($config['password'])) : 'EMPTY') . "\n";
    
    $result = DB::connection('tenant')->select('SELECT current_database()');
    echo "   ✓ Connection successful! Database: " . $result[0]->current_database . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ Connection failed: " . $e->getMessage() . "\n\n";
}
