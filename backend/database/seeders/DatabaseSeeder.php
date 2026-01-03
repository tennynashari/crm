<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Area;
use App\Models\LeadStatus;
use App\Models\Customer;
use App\Models\Contact;
use App\Models\Interaction;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@flowcrm.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create sales users
        $sales1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'sales1@flowcrm.test',
            'password' => Hash::make('password'),
            'role' => 'sales',
            'is_active' => true,
        ]);

        $sales2 = User::create([
            'name' => 'Siti Rahmawati',
            'email' => 'sales2@flowcrm.test',
            'password' => Hash::make('password'),
            'role' => 'sales',
            'is_active' => true,
        ]);

        $marketing = User::create([
            'name' => 'Andi Marketing',
            'email' => 'marketing@flowcrm.test',
            'password' => Hash::make('password'),
            'role' => 'marketing',
            'is_active' => true,
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@flowcrm.test',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        // Create areas
        $areas = [
            ['name' => 'Jakarta', 'code' => 'JKT', 'description' => 'Area Jakarta dan sekitarnya'],
            ['name' => 'Bandung', 'code' => 'BDG', 'description' => 'Area Bandung dan Jawa Barat'],
            ['name' => 'Surabaya', 'code' => 'SBY', 'description' => 'Area Surabaya dan Jawa Timur'],
            ['name' => 'Medan', 'code' => 'MDN', 'description' => 'Area Medan dan Sumatera'],
            ['name' => 'Bali', 'code' => 'DPS', 'description' => 'Area Bali dan Nusa Tenggara'],
            ['name' => 'Makassar', 'code' => 'MKS', 'description' => 'Area Makassar dan Sulawesi'],
        ];

        $areaModels = [];
        foreach ($areas as $area) {
            $areaModels[] = Area::create($area);
        }

        // Create lead statuses
        $statuses = [
            ['name' => 'New Lead', 'code' => 'new', 'color' => '#A78BFA', 'order' => 1],
            ['name' => 'Contacted', 'code' => 'contacted', 'color' => '#60A5FA', 'order' => 2],
            ['name' => 'Qualified', 'code' => 'qualified', 'color' => '#FBBF24', 'order' => 3],
            ['name' => 'Won', 'code' => 'won', 'color' => '#34D399', 'order' => 4],
            ['name' => 'Cold Lead', 'code' => 'cold', 'color' => '#93C5FD', 'order' => 5],
            ['name' => 'Warm Lead', 'code' => 'warm', 'color' => '#FCD34D', 'order' => 6],
            ['name' => 'Hot Lead', 'code' => 'hot', 'color' => '#F87171', 'order' => 7],
            ['name' => 'Dormant Lead', 'code' => 'dormant', 'color' => '#9CA3AF', 'order' => 8],
            ['name' => 'Lost Lead', 'code' => 'lost', 'color' => '#6B7280', 'order' => 9],
        ];

        $statusModels = [];
        foreach ($statuses as $status) {
            $statusModels[] = LeadStatus::create($status);
        }

        // Create dummy customers
        $companies = [
            'PT Maju Jaya', 'PT Berkah Sentosa', 'CV Harapan Indah', 'PT Sukses Makmur',
            'CV Cahaya Abadi', 'PT Mandiri Sejahtera', 'CV Bahagia Bersama', 'PT Karya Gemilang',
            'CV Mega Persada', 'PT Nusantara Digital', 'CV Indo Teknologi', 'PT Asia Pacific Solutions',
            'CV Global Dinamika', 'PT Surya Cemerlang', 'CV Bintang Terang', 'PT Harmoni Kreasi',
            'CV Pesona Nusantara', 'PT Wijaya Kusuma', 'CV Tanjung Sari', 'PT Anugerah Sejati',
            'CV Sejahtera Bersama', 'PT Mitra Utama', 'CV Duta Mandiri', 'PT Insan Mulia',
            'CV Pelita Harapan', 'PT Cipta Karya', 'CV Buana Raya', 'PT Trisula Jaya',
            'CV Artha Prima', 'PT Sentosa Lestari'
        ];

        $firstNames = ['Ahmad', 'Budi', 'Citra', 'Dian', 'Eko', 'Fitri', 'Gunawan', 'Heni', 'Indra', 'Joko', 'Kartika', 'Lisa', 'Made', 'Nina', 'Oki', 'Putri', 'Rizki', 'Sari', 'Tono', 'Umi', 'Vina', 'Wawan', 'Yanti', 'Zaki'];
        $lastNames = ['Wijaya', 'Santoso', 'Kusuma', 'Pratama', 'Utama', 'Saputra', 'Handoko', 'Setiawan', 'Rahman', 'Hidayat'];

        $salesUsers = [$sales1, $sales2];
        
        // Create 12 dummy companies with realistic data
        $companiesData = [
            [
                'company' => 'PT Maju Jaya Digital', 
                'area' => 0, 
                'source' => 'inbound', 
                'status' => 2, 
                'sales' => 0,
                'address' => 'Jl. Sudirman No. 123, Jakarta Selatan',
                'phone' => '021-5551234',
                'email' => 'info@majujaya.co.id',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Ahmad Santoso', 'position' => 'Direktur Utama', 'whatsapp' => '+628121234567', 'email' => 'ahmad@majujaya.co.id', 'is_primary' => true],
                    ['name' => 'Budi Wijaya', 'position' => 'Manager IT', 'whatsapp' => '+628129876543', 'email' => 'budi@majujaya.co.id', 'is_primary' => false],
                ]
            ],
            [
                'company' => 'CV Berkah Sentosa', 
                'area' => 0, 
                'source' => 'outbound', 
                'status' => 0, 
                'sales' => 1,
                'address' => 'Jl. Gatot Subroto No. 45, Jakarta Pusat',
                'phone' => '021-5552345',
                'email' => 'contact@berkahsentosa.com',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Siti Rahayu', 'position' => 'Owner', 'whatsapp' => '+628122345678', 'email' => 'siti@berkahsentosa.com', 'is_primary' => true],
                ]
            ],
            [
                'company' => 'PT Sukses Makmur', 
                'area' => 1, 
                'source' => 'inbound', 
                'status' => 1, 
                'sales' => 0,
                'address' => 'Jl. Dago No. 67, Bandung',
                'phone' => '022-8881234',
                'email' => 'admin@suksesmakmur.co.id',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Dian Kusuma', 'position' => 'General Manager', 'whatsapp' => '+628123456789', 'email' => 'dian@suksesmakmur.co.id', 'is_primary' => true],
                ]
            ],
            [
                'company' => 'Eko Pratama', 
                'area' => 1, 
                'source' => 'outbound', 
                'status' => 0, 
                'sales' => 1,
                'address' => 'Jl. Braga No. 89, Bandung',
                'phone' => '022-8882345',
                'email' => 'eko.pratama@gmail.com',
                'is_individual' => true,
                'contacts' => [
                    ['name' => 'Eko Pratama', 'position' => 'Owner', 'whatsapp' => '+628124567890', 'email' => 'eko.pratama@gmail.com', 'is_primary' => true],
                ]
            ],
            [
                'company' => 'PT Nusantara Digital', 
                'area' => 2, 
                'source' => 'inbound', 
                'status' => 2, 
                'sales' => 0,
                'address' => 'Jl. Tunjungan No. 101, Surabaya',
                'phone' => '031-7771234',
                'email' => 'hello@nusantaradigital.com',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Fitri Handayani', 'position' => 'CEO', 'whatsapp' => '+628125678901', 'email' => 'fitri@nusantaradigital.com', 'is_primary' => true],
                    ['name' => 'Gunawan Setiawan', 'position' => 'CTO', 'whatsapp' => '+628126789012', 'email' => 'gunawan@nusantaradigital.com', 'is_primary' => false],
                ]
            ],
            [
                'company' => 'CV Indo Teknologi', 
                'area' => 2, 
                'source' => 'inbound', 
                'status' => 1, 
                'sales' => 1,
                'address' => 'Jl. Pemuda No. 234, Surabaya',
                'phone' => '031-7772345',
                'email' => 'support@indotek.co.id',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Heni Putri', 'position' => 'Director', 'whatsapp' => '+628127890123', 'email' => 'heni@indotek.co.id', 'is_primary' => true],
                ]
            ],
            [
                'company' => 'PT Asia Pacific Solutions', 
                'area' => 3, 
                'source' => 'outbound', 
                'status' => 3, 
                'sales' => 0,
                'address' => 'Jl. Imam Bonjol No. 56, Medan',
                'phone' => '061-6661234',
                'email' => 'info@asiapacific.co.id',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Indra Rahman', 'position' => 'Managing Director', 'whatsapp' => '+628128901234', 'email' => 'indra@asiapacific.co.id', 'is_primary' => true],
                ]
            ],
            [
                'company' => 'Kartika Sari', 
                'area' => 3, 
                'source' => 'inbound', 
                'status' => 1, 
                'sales' => 1,
                'address' => 'Jl. Sisingamangaraja No. 78, Medan',
                'phone' => '061-6662345',
                'email' => 'kartika.sari88@gmail.com',
                'is_individual' => true,
                'contacts' => [
                    ['name' => 'Kartika Sari', 'position' => 'Freelancer', 'whatsapp' => '+628129012345', 'email' => 'kartika.sari88@gmail.com', 'is_primary' => true],
                ]
            ],
            [
                'company' => 'PT Surya Cemerlang', 
                'area' => 4, 
                'source' => 'outbound', 
                'status' => 4, 
                'sales' => 0,
                'address' => 'Jl. Sunset Road No. 90, Kuta, Bali',
                'phone' => '0361-8881234',
                'email' => 'contact@suryacemerlang.com',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Made Wibawa', 'position' => 'Founder', 'whatsapp' => '+628130123456', 'email' => 'made@suryacemerlang.com', 'is_primary' => true],
                ]
            ],
            [
                'company' => 'CV Bintang Terang', 
                'area' => 4, 
                'source' => 'inbound', 
                'status' => 2, 
                'sales' => 1,
                'address' => 'Jl. Ubud Raya No. 12, Gianyar, Bali',
                'phone' => '0361-8882345',
                'email' => 'admin@bintangterang.co.id',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Nina Anggraeni', 'position' => 'Owner', 'whatsapp' => '+628131234567', 'email' => 'nina@bintangterang.co.id', 'is_primary' => true],
                ]
            ],
            [
                'company' => 'PT Harmoni Kreasi', 
                'area' => 5, 
                'source' => 'outbound', 
                'status' => 0, 
                'sales' => 0,
                'address' => 'Jl. Urip Sumoharjo No. 34, Makassar',
                'phone' => '0411-5551234',
                'email' => 'info@harmonikreasi.com',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Oki Firmansyah', 'position' => 'CEO', 'whatsapp' => '+628132345678', 'email' => 'oki@harmonikreasi.com', 'is_primary' => true],
                ]
            ],
            [
                'company' => 'CV Pesona Nusantara', 
                'area' => 5, 
                'source' => 'inbound', 
                'status' => 1, 
                'sales' => 1,
                'address' => 'Jl. Pettarani No. 56, Makassar',
                'phone' => '0411-5552345',
                'email' => 'hello@pesonanusantara.co.id',
                'is_individual' => false,
                'contacts' => [
                    ['name' => 'Putri Maharani', 'position' => 'Director', 'whatsapp' => '+628133456789', 'email' => 'putri@pesonanusantara.co.id', 'is_primary' => true],
                    ['name' => 'Rizki Hidayat', 'position' => 'Operations Manager', 'whatsapp' => '+628134567890', 'email' => 'rizki@pesonanusantara.co.id', 'is_primary' => false],
                ]
            ],
        ];
        
        foreach ($companiesData as $i => $data) {
            $area = $areaModels[$data['area']];
            $status = $statusModels[$data['status']];
            $assignedSales = $salesUsers[$data['sales']];
            
            // Next action based on lead status
            $nextActionDate = null;
            $nextActionPlan = null;
            $nextActionPriority = null;
            
            if ($data['status'] < 4) { // Not Lost Lead
                $daysOffset = rand(-3, 7);
                $nextActionDate = Carbon::now()->addDays($daysOffset)->format('Y-m-d');
                $nextActionPlans = [
                    'Follow up via WhatsApp',
                    'Schedule product demo',
                    'Send detailed quotation',
                    'Visit customer office',
                    'Follow up on previous discussion',
                    'Present case studies',
                    'Discuss payment terms',
                ];
                $nextActionPlan = $nextActionPlans[array_rand($nextActionPlans)];
                $nextActionPriority = ['low', 'medium', 'high'][rand(0, 2)];
            }
            
            $sourceNote = $data['source'] == 'inbound' ? 'Inquiry dari website/social media' : 'Cold calling/prospecting';
            
            $customer = Customer::create([
                'company' => $data['company'],
                'is_individual' => $data['is_individual'],
                'area_id' => $area->id,
                'email' => $data['email'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'source' => $data['source'],
                'assigned_sales_id' => $assignedSales->id,
                'lead_status_id' => $status->id,
                'next_action_date' => $nextActionDate,
                'next_action_plan' => $nextActionPlan,
                'next_action_priority' => $nextActionPriority,
                'next_action_status' => 'pending',
                'notes' => $sourceNote . '. Interested in our services.',
                'created_at' => Carbon::now()->subDays(rand(5, 60)),
            ]);

            // Create contacts from data
            foreach ($data['contacts'] as $contactData) {
                Contact::create([
                    'customer_id' => $customer->id,
                    'name' => $contactData['name'],
                    'position' => $contactData['position'],
                    'whatsapp' => $contactData['whatsapp'],
                    'email' => $contactData['email'],
                    'is_primary' => $contactData['is_primary'],
                ]);
            }

            // Create 2-4 interactions per customer
            $interactionCount = rand(2, 4);
            for ($k = 0; $k < $interactionCount; $k++) {
                $interactionTypes = [
                    ['type' => 'manual_channel', 'channel' => 'whatsapp'],
                    ['type' => 'manual_channel', 'channel' => 'telephone'],
                    ['type' => 'manual_channel', 'channel' => 'instagram'],
                    ['type' => 'note', 'channel' => null],
                ];
                
                $interaction = $interactionTypes[array_rand($interactionTypes)];
                
                $summaries = [
                    'Initial contact - customer interested in our services',
                    'Discussed product features and pricing options',
                    'Sent product catalog via WhatsApp',
                    'Follow up on quotation sent last week',
                    'Customer requested demo session',
                    'Meeting scheduled for next week',
                    'Positive feedback on proposal',
                    'Customer needs time to discuss internally'
                ];
                
                Interaction::create([
                    'customer_id' => $customer->id,
                    'interaction_type' => $interaction['type'],
                    'channel' => $interaction['channel'],
                    'subject' => null,
                    'content' => null,
                    'summary' => $summaries[array_rand($summaries)],
                    'created_by_type' => 'user',
                    'created_by_user_id' => $assignedSales->id,
                    'lead_status_snapshot_id' => $status->id,
                    'interaction_at' => Carbon::now()->subDays(rand(1, 45))->subHours(rand(0, 23)),
                    'created_at' => Carbon::now()->subDays(rand(1, 45)),
                ]);
            }
        }

        echo "\n✅ Seeder completed successfully!\n";
        echo "📊 Created:\n";
        echo "   - 5 Users (1 admin, 2 sales, 1 marketing, 1 manager)\n";
        echo "   - 6 Areas\n";
        echo "   - 9 Lead Statuses (New Lead, Contacted, Qualified, Won, Cold, Warm, Hot, Dormant, Lost)\n";
        echo "   - 12 Companies (10 corporate, 2 individual customers)\n";
        echo "   - 15 Contacts (PICs)\n";
        echo "   - ~36 Interactions\n";
    }
}
