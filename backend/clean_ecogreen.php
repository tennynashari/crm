<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

echo "Cleaning crm_ecogreen database...\n\n";

try {
    // Configure tenant connection to crm_ecogreen
    Config::set('database.connections.tenant.database', 'crm_ecogreen');
    DB::purge('tenant');
    DB::reconnect('tenant');
    
    // Truncate all data tables (keep only user andhia)
    echo "Step 1: Clearing data tables...\n";
    
    // Get master_user_id for andhia
    $andhiaProfile = DB::connection('tenant')
        ->table('user_profiles')
        ->where('email', 'andhia@ecogreen.id')
        ->first();
    
    if (!$andhiaProfile) {
        throw new \Exception('User andhia@ecogreen.id not found!');
    }
    
    $andhiaId = $andhiaProfile->master_user_id;
    
    // Delete data tables
    DB::connection('tenant')->table('invoice_items')->truncate();
    echo "  ✓ invoice_items cleared\n";
    
    DB::connection('tenant')->table('invoices')->truncate();
    echo "  ✓ invoices cleared\n";
    
    DB::connection('tenant')->table('interactions')->truncate();
    echo "  ✓ interactions cleared\n";
    
    DB::connection('tenant')->table('emails')->truncate();
    echo "  ✓ emails cleared\n";
    
    DB::connection('tenant')->table('contacts')->truncate();
    echo "  ✓ contacts cleared\n";
    
    DB::connection('tenant')->table('customers')->truncate();
    echo "  ✓ customers cleared\n";
    
    DB::connection('tenant')->table('broadcast_email_history')->truncate();
    echo "  ✓ broadcast_email_history cleared\n";
    
    DB::connection('tenant')->table('broadcast_email_drafts')->truncate();
    echo "  ✓ broadcast_email_drafts cleared\n";
    
    DB::connection('tenant')->table('audit_logs')->truncate();
    echo "  ✓ audit_logs cleared\n";
    
    // Keep master data tables (areas, lead_statuses) but can be cleared too if needed
    DB::connection('tenant')->table('areas')->truncate();
    echo "  ✓ areas cleared\n";
    
    DB::connection('tenant')->table('lead_statuses')->truncate();
    echo "  ✓ lead_statuses cleared\n";
    
    echo "\n";
    
    // Step 2: Keep only andhia user
    echo "Step 2: Keeping only andhia@ecogreen.id...\n";
    
    DB::connection('tenant')
        ->table('user_profiles')
        ->where('master_user_id', '!=', $andhiaId)
        ->delete();
    
    $remainingUsers = DB::connection('tenant')->table('user_profiles')->count();
    
    echo "  ✓ Remaining users: {$remainingUsers}\n";
    
    // Verify andhia user
    $andhia = DB::connection('tenant')
        ->table('user_profiles')
        ->where('email', 'andhia@ecogreen.id')
        ->first();
    
    echo "\n";
    echo "═══════════════════════════════════════\n";
    echo "   DATABASE CLEANED!\n";
    echo "═══════════════════════════════════════\n";
    echo "Database: crm_ecogreen\n";
    echo "Status: Empty (ready for use)\n\n";
    echo "User Profile:\n";
    echo "  - Email: {$andhia->email}\n";
    echo "  - Name: {$andhia->name}\n";
    echo "  - Role: {$andhia->role}\n";
    echo "  - Master User ID: {$andhia->master_user_id}\n\n";
    echo "All data tables are now empty.\n";
    echo "Ready to add customers, interactions, etc.\n";
    echo "═══════════════════════════════════════\n";
    
} catch (\Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
