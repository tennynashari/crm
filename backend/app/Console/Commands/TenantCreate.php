<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Master\Company;
use App\Models\Master\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class TenantCreate extends Command
{
    protected $signature = 'tenant:create 
                            {name : Company name}
                            {email : Admin email}
                            {password : Admin password}';
    
    protected $description = 'Create new tenant company with database and admin user';
    
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        
        $slug = Str::slug($name);
        $dbName = 'crm_' . str_replace('-', '_', $slug);
        
        $this->info("Creating tenant: {$name}");
        $this->info("Database: {$dbName}");
        
        try {
            // Step 1: Create physical database
            $this->info("Step 1/7: Creating database...");
            DB::connection('master')->statement("CREATE DATABASE {$dbName} OWNER " . env('DB_USERNAME', 'crm'));
            $this->info("✓ Database created");
            
            // Step 2: Register company in master
            $this->info("Step 2/7: Registering company...");
            $company = Company::on('master')->create([
                'name' => $name,
                'slug' => $slug,
                'database_name' => $dbName,
                'is_active' => true,
                'subscription_status' => 'trial',
                'max_users' => 10,
                'max_customers' => 1000,
            ]);
            $this->info("✓ Company registered (ID: {$company->id})");
            
            // Step 3: Configure tenant connection
            $this->info("Step 3/7: Configuring connection...");
            Config::set('database.connections.tenant.database', $dbName);
            DB::purge('tenant');
            DB::reconnect('tenant');
            $this->info("✓ Connection configured");
            
            // Step 4: Run base migrations (untuk tenant structure)
            $this->info("Step 4/7: Running base migrations...");
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
            $this->info("✓ Tenant migrations completed");
            
            // Step 5: Run existing migrations (main application tables)
            $this->info("Step 5/7: Running application migrations...");
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force' => true,
            ]);
            $this->info("✓ Application migrations completed");
            
            // Step 6: Seed initial data if seeder exists
            $this->info("Step 6/7: Seeding initial data...");
            try {
                // Only seed if seeder exists
                Artisan::call('db:seed', [
                    '--database' => 'tenant',
                    '--force' => true,
                ]);
                $this->info("✓ Initial data seeded");
            } catch (\Exception $e) {
                $this->warn("Seeder skipped: {$e->getMessage()}");
            }
            
            // Step 7: Create admin user
            $this->info("Step 7/7: Creating admin user...");
            
            // User di master database
            $user = User::on('master')->create([
                'company_id' => $company->id,
                'name' => 'Administrator',
                'email' => $email,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);
            
            // User profile di tenant database
            DB::connection('tenant')->table('user_profiles')->insert([
                'master_user_id' => $user->id,
                'name' => 'Administrator',
                'email' => $email,
                'role' => 'admin',
                'permissions' => json_encode(['*']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->info("✓ Admin user created");
            
            // Success summary
            $this->newLine();
            $this->info("═══════════════════════════════════════");
            $this->info("   TENANT CREATED SUCCESSFULLY!   ");
            $this->info("═══════════════════════════════════════");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Company Name', $name],
                    ['Company ID', $company->id],
                    ['Database', $dbName],
                    ['Admin Email', $email],
                    ['Status', 'Active (Trial)'],
                ]
            );
            $this->newLine();
            $this->info("Admin can now login with the provided credentials");
            
            return 0;
            
        } catch (\Exception $e) {
            // Cleanup: Drop database if created
            try {
                DB::connection('master')->statement("DROP DATABASE IF EXISTS {$dbName}");
                $this->warn("Database {$dbName} dropped (cleanup)");
            } catch (\Exception $dropError) {
                // Ignore
            }
            
            // Delete company record if created
            if (isset($company)) {
                $company->delete();
                $this->warn("Company record deleted (cleanup)");
            }
            
            $this->error("Failed to create tenant: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            
            return 1;
        }
    }
}
