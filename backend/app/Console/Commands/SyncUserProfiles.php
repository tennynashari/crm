<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUserProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:sync-user-profiles {--company-id= : Sync users for specific company ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync users from master DB to tenant user_profiles tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyIdFilter = $this->option('company-id');
        
        $this->info('Starting user profiles sync...');
        
        // Get all companies or specific company
        $query = DB::connection('master')->table('companies');
        
        if ($companyIdFilter) {
            $query->where('id', $companyIdFilter);
        }
        
        $companies = $query->where('is_active', true)->get();
        
        if ($companies->isEmpty()) {
            $this->error('No companies found');
            return 1;
        }
        
        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name} (ID: {$company->id})");
            $this->syncCompanyUsers($company);
        }
        
        $this->info('Sync completed successfully!');
        return 0;
    }
    
    private function syncCompanyUsers($company)
    {
        // Get users for this company from master DB with explicit columns
        $users = DB::connection('master')
            ->table('users')
            ->select('id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at')
            ->where('company_id', $company->id)
            ->get();
        
        if ($users->isEmpty()) {
            $this->warn("  No users found for company: {$company->name}");
            return;
        }
        
        $this->info("  Found {$users->count()} users");
        
        // Configure tenant connection to this company's database
        config(['database.connections.tenant.database' => $company->database_name]);
        DB::connection('tenant')->reconnect();
        
        $synced = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($users as $user) {
            try {
                // Check if user_profile already exists
                $exists = DB::connection('tenant')
                    ->table('user_profiles')
                    ->where('id', $user->id)
                    ->exists();
                
                if ($exists) {
                    $this->line("    Skipping user {$user->email} (already exists)");
                    $skipped++;
                    continue;
                }
                
                // Handle missing or NULL fields with defaults
                $role = $user->role ?? 'sales';  // Default to 'sales' if NULL
                $isActive = isset($user->is_active) ? (bool)$user->is_active : true;
                $name = $user->name ?? 'Unknown';
                $email = $user->email ?? 'unknown@example.com';
                
                // Insert to user_profiles with same ID
                DB::connection('tenant')->table('user_profiles')->insert([
                    'id' => $user->id,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'is_active' => $isActive,
                    'created_at' => $user->created_at ?? now(),
                    'updated_at' => $user->updated_at ?? now(),
                ]);
                
                $this->line("    ✓ Synced user: {$email}");
                $synced++;
                
            } catch (\Exception $e) {
                $this->error("    ✗ Failed to sync user ID {$user->id}: {$e->getMessage()}");
                $errors++;
            }
        }
        
        $this->info("  Company {$company->name}: Synced {$synced}, Skipped {$skipped}, Errors {$errors}");
    }
}
