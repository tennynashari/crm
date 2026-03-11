<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "\n";
echo "═══════════════════════════════════════\n";
echo "   RESET MAIN COMPANY PASSWORDS\n";
echo "═══════════════════════════════════════\n\n";

try {
    // Get Main Company from master database
    $company = DB::connection('master')
        ->table('companies')
        ->where('database_name', 'crm')
        ->first();
    
    if (!$company) {
        echo "❌ Main Company not found in master database\n";
        exit(1);
    }
    
    echo "Company: {$company->name}\n";
    echo "Database: {$company->database_name}\n";
    echo "------------------------------------------------------------\n\n";
    
    // Switch to tenant database
    config(['database.connections.tenant.database' => $company->database_name]);
    DB::purge('tenant');
    DB::reconnect('tenant');
    
    // Get all user profiles
    $users = DB::connection('tenant')
        ->table('user_profiles')
        ->get();
    
    if ($users->isEmpty()) {
        echo "❌ No users found in Main Company database\n";
        exit(1);
    }
    
    echo "Found {$users->count()} users. Resetting passwords...\n\n";
    
    // Default password untuk semua users
    $defaultPassword = 'password123';
    $hashedPassword = Hash::make($defaultPassword);
    
    foreach ($users as $user) {
        // Update password di master users table
        DB::connection('master')
            ->table('users')
            ->where('email', $user->email)
            ->update([
                'password' => $hashedPassword,
                'updated_at' => now()
            ]);
        
        echo "✅ Password reset untuk: {$user->email}\n";
    }
    
    echo "\n";
    echo "════════════════════════════════════════════════════════════\n";
    echo "✅ Password Reset Complete!\n";
    echo "════════════════════════════════════════════════════════════\n\n";
    
    echo "LOGIN CREDENTIALS - Main Company:\n";
    echo "------------------------------------------------------------\n";
    foreach ($users as $user) {
        echo "  - {$user->email} (password: {$defaultPassword})\n";
    }
    echo "\n";
    
    echo "You can now login with any Main Company user using password: {$defaultPassword}\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
