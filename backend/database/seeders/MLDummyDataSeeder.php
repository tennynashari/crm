<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Interaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Area;
use App\Models\LeadStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MLDummyDataSeeder extends Seeder
{
    /**
     * Seed 50+ customers with varied history for ML training
     * History spans last 6 months
     */
    public function run(): void
    {
        $this->command->info('🤖 Generating ML Training Data...');
        
        // Get reference data
        $areas = Area::all();
        $leadStatuses = LeadStatus::all();
        $users = User::all();
        
        if ($areas->isEmpty() || $leadStatuses->isEmpty() || $users->isEmpty()) {
            $this->command->error('Please run basic seeders first (areas, lead_statuses, users)');
            return;
        }
        
        $defaultArea = $areas->first();
        $defaultLeadStatus = $leadStatuses->first();
        $defaultUser = $users->first();
        
        // Customer profiles with different patterns
        $profiles = [
            // HIGH POTENTIAL: Recent sales + active communication
            ['type' => 'high', 'weight' => 10],
            // MEDIUM POTENTIAL: Some history but not recent
            ['type' => 'medium', 'weight' => 20],
            // LOW POTENTIAL: Old customers, inactive
            ['type' => 'low', 'weight' => 15],
            // NEW POTENTIAL: New but very active
            ['type' => 'new_active', 'weight' => 10],
        ];
        
        $customerCount = 0;
        $targetCustomers = 55;
        
        DB::beginTransaction();
        
        try {
            while ($customerCount < $targetCustomers) {
                // Select profile type
                $profileType = $this->selectProfileType($profiles);
                
                // Create customer
                $customer = $this->createCustomer($areas, $leadStatuses, $users, $profileType);
                $customerCount++;
                
                // Generate history based on profile
                $this->generateHistory($customer, $profileType);
                
                $this->command->info("Created customer {$customerCount}/{$targetCustomers}: {$customer->company} ({$profileType})");
            }
            
            DB::commit();
            
            $this->command->info('✅ Successfully generated ' . $customerCount . ' customers with varied history');
            $this->command->info('📊 Interaction count: ' . Interaction::count());
            $this->command->info('💰 Invoice count: ' . Invoice::count());
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
        }
    }
    
    private function selectProfileType($profiles)
    {
        $totalWeight = array_sum(array_column($profiles, 'weight'));
        $random = rand(1, $totalWeight);
        $current = 0;
        
        foreach ($profiles as $profile) {
            $current += $profile['weight'];
            if ($random <= $current) {
                return $profile['type'];
            }
        }
        
        return 'medium';
    }
    
    private function createCustomer($areas, $leadStatuses, $users, $profileType)
    {
        $companies = [
            'PT Maju Jaya', 'CV Sukses Bersama', 'UD Makmur Sentosa', 'PT Berkah Abadi',
            'CV Cahaya Terang', 'PT Indah Karya', 'UD Sejahtera', 'PT Global Tech',
            'CV Digital Media', 'PT Software House', 'UD Retail Store', 'PT Manufacturing',
            'CV Creative Agency', 'PT Logistics Indonesia', 'UD Toko Online',
            'PT Food & Beverage', 'CV Healthcare', 'PT Education Center', 'UD Fashion Store',
            'PT Property Indonesia', 'CV Marketing Agency', 'PT Distributor Nasional',
            'UD Workshop Auto', 'PT Export Import', 'CV Consulting Firm'
        ];
        
        $company = $companies[array_rand($companies)] . ' ' . rand(100, 999);
        $slug = strtolower(str_replace([' ', '.'], ['', ''], $company));
        
        // Customer age based on profile
        $createdDaysAgo = match($profileType) {
            'high' => rand(90, 180),      // 3-6 months
            'medium' => rand(120, 180),   // 4-6 months
            'low' => rand(150, 180),      // 5-6 months
            'new_active' => rand(14, 60), // 2 weeks - 2 months
            default => rand(90, 180)
        };
        
        $createdAt = Carbon::now()->subDays($createdDaysAgo);
        
        // Lead status based on profile type (HIGH VALUE statuses untuk high potential)
        $leadStatusId = $this->selectLeadStatus($leadStatuses, $profileType);
        
        return Customer::create([
            'company' => $company,
            'is_individual' => rand(0, 10) > 7, // 30% individual
            'email' => $slug . '@example.com',
            'phone' => '08' . rand(10000000000, 99999999999),
            'address' => 'Jl. Example No. ' . rand(1, 100) . ', Jakarta',
            'source' => rand(0, 1) ? 'inbound' : 'outbound',
            'area_id' => $areas->random()->id,
            'lead_status_id' => $leadStatusId,
            'assigned_sales_id' => $users->random()->id,
            'next_action_date' => rand(0, 10) > 3 ? Carbon::now()->addDays(rand(1, 14)) : null,
            'next_action_plan' => rand(0, 10) > 3 ? 'Follow up meeting' : null,
            'notes' => 'Generated for ML training - ' . $profileType,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
    
    private function selectLeadStatus($leadStatuses, $profileType)
    {
        // Map lead status names to IDs
        $hotLead = $leadStatuses->firstWhere('name', 'Hot Lead');
        $warmLead = $leadStatuses->firstWhere('name', 'Warm Lead');
        $qualified = $leadStatuses->firstWhere('name', 'Qualified');
        $won = $leadStatuses->firstWhere('name', 'Won');
        $contacted = $leadStatuses->firstWhere('name', 'Contacted');
        $newLead = $leadStatuses->firstWhere('name', 'New Lead');
        $coldLead = $leadStatuses->firstWhere('name', 'Cold Lead');
        $dormant = $leadStatuses->firstWhere('name', 'Dormant Lead');
        
        // Select based on profile type
        switch ($profileType) {
            case 'high':
                // 70% hot/warm/qualified/won, 30% contacted
                $highValueStatuses = [$hotLead, $warmLead, $qualified, $won];
                if (rand(1, 10) <= 7) {
                    return $highValueStatuses[array_rand($highValueStatuses)]->id;
                }
                return $contacted->id ?? $leadStatuses->random()->id;
                
            case 'new_active':
                // 60% hot/warm/qualified, 40% contacted/new
                $activeStatuses = [$hotLead, $warmLead, $qualified];
                if (rand(1, 10) <= 6) {
                    return $activeStatuses[array_rand($activeStatuses)]->id;
                }
                return rand(0, 1) ? $contacted->id : $newLead->id;
                
            case 'medium':
                // 40% warm/contacted, 60% others
                if (rand(1, 10) <= 4) {
                    return rand(0, 1) ? $warmLead->id : $contacted->id;
                }
                return $leadStatuses->random()->id;
                
            case 'low':
                // Mostly cold/dormant/contacted
                $lowStatuses = [$coldLead, $dormant, $contacted];
                return $lowStatuses[array_rand($lowStatuses)]->id ?? $leadStatuses->random()->id;
                
            default:
                return $leadStatuses->random()->id;
        }
    }
    
    private function generateHistory($customer, $profileType)
    {
        $customerAge = Carbon::now()->diffInDays($customer->created_at);
        
        switch ($profileType) {
            case 'high':
                // High potential: Recent sales + many interactions
                $this->generateInteractions($customer, rand(15, 30), 0.7); // 70% in last 90 days
                $this->generateInvoices($customer, rand(3, 8), 0.6); // 60% in last 90 days
                break;
                
            case 'medium':
                // Medium potential: Some activity, spread out
                $this->generateInteractions($customer, rand(8, 15), 0.4); // 40% in last 90 days
                $this->generateInvoices($customer, rand(1, 4), 0.3); // 30% in last 90 days
                break;
                
            case 'low':
                // Low potential: Old history, not recent
                $this->generateInteractions($customer, rand(3, 8), 0.1); // 10% in last 90 days
                $this->generateInvoices($customer, rand(0, 2), 0); // No recent invoices
                break;
                
            case 'new_active':
                // New but very active
                $this->generateInteractions($customer, rand(10, 20), 0.8); // 80% recent
                $this->generateInvoices($customer, rand(1, 3), 0.7); // 70% recent
                break;
        }
    }
    
    private function generateInteractions($customer, $count, $recentRatio)
    {
        $customerAge = Carbon::now()->diffInDays($customer->created_at);
        $recentCount = (int)($count * $recentRatio);
        $oldCount = $count - $recentCount;
        
        $types = ['email_inbound', 'email_outbound', 'manual_channel', 'note'];
        $channels = ['email', 'whatsapp', 'telephone', 'instagram'];
        
        // Generate recent interactions (last 90 days)
        for ($i = 0; $i < $recentCount; $i++) {
            $daysAgo = rand(1, 90);
            $interactionAt = Carbon::now()->subDays($daysAgo);
            
            Interaction::create([
                'customer_id' => $customer->id,
                'interaction_type' => $types[array_rand($types)],
                'channel' => $channels[array_rand($channels)],
                'subject' => 'Follow up - ' . $customer->company,
                'content' => 'Discussion about product/service',
                'summary' => 'Customer interested in our offerings',
                'interaction_at' => $interactionAt,
                'created_at' => $interactionAt,
                'updated_at' => $interactionAt,
            ]);
        }
        
        // Generate old interactions (91 days - customer age)
        for ($i = 0; $i < $oldCount; $i++) {
            $daysAgo = rand(91, min($customerAge, 180));
            $interactionAt = Carbon::now()->subDays($daysAgo);
            
            Interaction::create([
                'customer_id' => $customer->id,
                'interaction_type' => $types[array_rand($types)],
                'channel' => $channels[array_rand($channels)],
                'subject' => 'Initial contact - ' . $customer->company,
                'content' => 'First discussion',
                'summary' => 'Introduction meeting',
                'interaction_at' => $interactionAt,
                'created_at' => $interactionAt,
                'updated_at' => $interactionAt,
            ]);
        }
    }
    
    private function generateInvoices($customer, $count, $recentRatio)
    {
        if ($count === 0) return;
        
        $customerAge = Carbon::now()->diffInDays($customer->created_at);
        $recentCount = (int)($count * $recentRatio);
        $oldCount = $count - $recentCount;
        
        // Generate recent invoices (last 90 days)
        for ($i = 0; $i < $recentCount; $i++) {
            $this->createInvoice($customer, rand(1, 90));
        }
        
        // Generate old invoices
        for ($i = 0; $i < $oldCount; $i++) {
            $this->createInvoice($customer, rand(91, min($customerAge, 180)));
        }
    }
    
    private function createInvoice($customer, $daysAgo)
    {
        $invoiceDate = Carbon::now()->subDays($daysAgo);
        $invoiceNumber = 'INV-' . $invoiceDate->format('Ymd') . '-' . rand(1000, 9999);
        
        // Random invoice value between 1M - 50M IDR
        $subtotal = rand(1000000, 50000000);
        $tax = $subtotal * 0.11; // 11% PPN
        $discount = rand(0, (int)($subtotal * 0.1)); // 0-10% discount
        $total = $subtotal + $tax - $discount;
        
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'due_date' => $invoiceDate->copy()->addDays(30),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'status' => rand(0, 10) > 2 ? 'paid' : 'sent', // 80% paid
            'notes' => 'Payment for services',
            'created_at' => $invoiceDate,
            'updated_at' => $invoiceDate,
        ]);
        
        // Add invoice items
        $itemCount = rand(1, 3);
        for ($i = 0; $i < $itemCount; $i++) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_name' => 'Product/Service ' . ($i + 1),
                'description' => 'Description for item ' . ($i + 1),
                'quantity' => rand(1, 10),
                'unit_price' => rand(100000, 5000000),
                'total_price' => rand(100000, 10000000),
                'created_at' => $invoiceDate,
                'updated_at' => $invoiceDate,
            ]);
        }
    }
}
