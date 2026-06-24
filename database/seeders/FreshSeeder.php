<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FreshSeeder extends Seeder
{
    public function run(): void
    {
        // ----------------------------------------------------------------
        // TRUNCATE ALL (children first)
        // ----------------------------------------------------------------
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('chat_sessions')->truncate();
        DB::table('account_requests')->truncate();
        DB::table('maintenance_alerts')->truncate();
        DB::table('service_history')->truncate();
        DB::table('labour_charges')->truncate();
        DB::table('job_card_parts')->truncate();
        DB::table('job_cards')->truncate();
        DB::table('appointments')->truncate();
        DB::table('spare_parts')->truncate();
        DB::table('job_types')->truncate();
        DB::table('vehicles')->truncate();
        DB::table('users')->truncate();
        DB::table('companies')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ----------------------------------------------------------------
        // COMPANIES (3)
        // ----------------------------------------------------------------
        DB::table('companies')->insert([
            [
                'id'               => 1,
                'name'             => 'Syarikat Logistik Mulia Sdn Bhd',
                'registration_no'  => 'SA0012345',
                'address'          => 'No 12, Jalan Perindustrian 4, Kawasan Perindustrian Senai, Johor',
                'email'    => 'ops@mulialogistik.com.my',
                'phone'    => '07-5551234',
                'person_in_charge' => 'Encik Hafiz bin Roslan',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'id'               => 2,
                'name'             => 'Perdana Fleet Services Sdn Bhd',
                'registration_no'  => 'KL0098765',
                'address'          => 'Unit 3-A, Kompleks Perdagangan Cheras, Kuala Lumpur',
                'email'    => 'fleet@perdanafleet.my',
                'phone'    => '03-9876543',
                'person_in_charge' => 'Puan Rozita binti Azman',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'id'               => 3,
                'name'             => 'Nusantara Express Sdn Bhd',
                'registration_no'  => 'JH0056321',
                'address'          => 'Lot 7, Jalan Tun Razak, Kota Bharu, Kelantan',
                'email'    => 'admin@nusantaraexpress.com',
                'phone'    => '09-7412580',
                'person_in_charge' => 'Encik Zulkifli bin Daud',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);

        // ----------------------------------------------------------------
        // USERS
        // 1 admin, 3 staff, 4 corporate (2 share company 1), 4 individual
        // ----------------------------------------------------------------
        DB::table('users')->insert([
        // Admin
        [
            'id'         => 1,
            'name'       => 'Ahmad Firdaus bin Ismail',
            'email'      => 'admin@teraju.my',
            'password'   => Hash::make('password'),
            'role'       => 'admin',
            'status'     => 'active',
            'contact_no' => '013-3421100',
            'company_id' => null,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => null,
            'specialties'=> null,                    // <-- added
        ],
        // Staff
        [
            'id'         => 2,
            'name'       => 'Mohd Ridhwan bin Sulaiman',
            'email'      => 'ridhwan@teraju.my',
            'password'   => Hash::make('password'),
            'role'       => 'staff',
            'status'     => 'active',
            'contact_no' => '011-22334455',
            'company_id' => null,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => null,
            'specialties'=> json_encode([1, 2, 6, 9]),
        ],
        [
            'id'         => 3,
            'name'       => 'Siti Nabilah binti Kamal',
            'email'      => 'nabilah@teraju.my',
            'password'   => Hash::make('password'),
            'role'       => 'staff',
            'status'     => 'active',
            'contact_no' => '010-55667788',
            'company_id' => null,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => null,
            'specialties'=> json_encode([3, 4, 7]),
        ],
        [
            'id'         => 4,
            'name'       => 'Hairul Azwan bin Musa',
            'email'      => 'hairul@teraju.my',
            'password'   => Hash::make('password'),
            'role'       => 'staff',
            'status'     => 'active',
            'contact_no' => '012-87654321',
            'company_id' => null,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => null,
            'specialties'=> json_encode([5, 6, 8]),
        ],
        // Corporate — company 1
        [
            'id'         => 5,
            'name'       => 'Encik Hafiz bin Roslan',
            'email'      => 'hafiz@mulialogistik.com.my',
            'password'   => Hash::make('password'),
            'role'       => 'corporate',
            'status'     => 'active',
            'contact_no' => '019-3312200',
            'company_id' => 1,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => 'primary',
            'specialties'=> null,
        ],
        [
            'id'         => 6,
            'name'       => 'Puan Fifi binti Ahmad',
            'email'      => 'fifi@mulialogistik.com.my',
            'password'   => Hash::make('password'),
            'role'       => 'corporate',
            'status'     => 'active',
            'contact_no' => '013-4186881',
            'company_id' => 1,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => 'secondary',
            'specialties'=> null,
        ],
        // Corporate — company 2
        [
            'id'         => 7,
            'name'       => 'Puan Rozita binti Azman',
            'email'      => 'rozita@perdanafleet.my',
            'password'   => Hash::make('password'),
            'role'       => 'corporate',
            'status'     => 'active',
            'contact_no' => '017-6654321',
            'company_id' => 2,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => 'primary',
            'specialties'=> null,
        ],
        // Corporate — company 3
        [
            'id'         => 8,
            'name'       => 'Encik Zulkifli bin Daud',
            'email'      => 'zulkifli@nusantaraexpress.com',
            'password'   => Hash::make('password'),
            'role'       => 'corporate',
            'status'     => 'active',
            'contact_no' => '013-9988776',
            'company_id' => 3,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => 'primary',
            'specialties'=> null,
        ],
        // Individual customers
        [
            'id'         => 9,
            'name'       => 'Nurul Ain binti Zakaria',
            'email'      => 'ain@gmail.com',
            'password'   => Hash::make('password'),
            'role'       => 'individual',
            'status'     => 'active',
            'contact_no' => '011-34567890',
            'company_id' => null,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => null,
            'specialties'=> null,
        ],
        [
            'id'         => 10,
            'name'       => 'Faizal bin Rahman',
            'email'      => 'faizal@gmail.com',
            'password'   => Hash::make('password'),
            'role'       => 'individual',
            'status'     => 'active',
            'contact_no' => '012-45678901',
            'company_id' => null,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => null,
            'specialties'=> null,
        ],
        [
            'id'         => 11,
            'name'       => 'Khairul Nizam bin Hassan',
            'email'      => 'nizam@gmail.com',
            'password'   => Hash::make('password'),
            'role'       => 'individual',
            'status'     => 'active',
            'contact_no' => '016-78901234',
            'company_id' => null,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => null,
            'specialties'=> null,
        ],
        [
            'id'         => 12,
            'name'       => 'Salmah binti Othman',
            'email'      => 'salmah@gmail.com',
            'password'   => Hash::make('password'),
            'role'       => 'individual',
            'status'     => 'inactive',
            'contact_no' => '014-22334455',
            'company_id' => null,
            'avatar'     => null,
            'created_at' => now(),
            'updated_at' => now(),
            'pic_role'   => null,
            'specialties'=> null,
        ],
        ]);

        // ----------------------------------------------------------------
        // JOB TYPES (10) with realistic base prices
        // ----------------------------------------------------------------
        DB::table('job_types')->insert([
            ['id'=>1,  'name'=>'Full Engine Service',        'category'=>'Engine',       'estimated_minutes'=>180, 'base_price'=>120.00, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>2,  'name'=>'Oil & Filter Change',        'category'=>'Engine',       'estimated_minutes'=>45,  'base_price'=>30.00,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>3,  'name'=>'Brake Pad Replacement',      'category'=>'Brakes',       'estimated_minutes'=>90,  'base_price'=>60.00,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>4,  'name'=>'Tyre Rotation & Balancing',  'category'=>'Tyres',        'estimated_minutes'=>60,  'base_price'=>40.00,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>5,  'name'=>'Battery Replacement',        'category'=>'Electrical',   'estimated_minutes'=>30,  'base_price'=>30.00,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>6,  'name'=>'Air Conditioning Service',   'category'=>'Cooling',      'estimated_minutes'=>120, 'base_price'=>80.00,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>7,  'name'=>'Suspension Inspection',      'category'=>'Suspension',   'estimated_minutes'=>75,  'base_price'=>60.00,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>8,  'name'=>'Transmission Fluid Change',  'category'=>'Transmission', 'estimated_minutes'=>60,  'base_price'=>60.00,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>9,  'name'=>'Engine Diagnostics Scan',    'category'=>'Engine',       'estimated_minutes'=>45,  'base_price'=>45.00,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>10, 'name'=>'Full Vehicle Inspection',    'category'=>'General',      'estimated_minutes'=>150, 'base_price'=>100.00, 'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ----------------------------------------------------------------
        // VEHICLES
        // Company 1 (Mulia): user 5 owns 3 vehicles, user 6 owns 1
        //   — both PICs see all 4 in shared fleet view
        // Company 2 (Perdana): user 7 owns 3
        // Company 3 (Nusantara): user 8 owns 2
        // Individual: ain=2, faizal=1, nizam=1
        // Walk-in: user_id null (2 vehicles)
        // ----------------------------------------------------------------
        DB::table('vehicles')->insert([
            // Mulia Logistik — Hafiz (user 5) owns these 3
            ['id'=>1,  'user_id'=>5,    'plate_number'=>'JQP 1122', 'brand'=>'Isuzu',    'model'=>'D-Max',      'year'=>2020, 'mileage'=>87000,  'color'=>'White',  'fuel_type'=>'Diesel',  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>2,  'user_id'=>5,    'plate_number'=>'JQP 1133', 'brand'=>'Isuzu',    'model'=>'D-Max',      'year'=>2021, 'mileage'=>64000,  'color'=>'White',  'fuel_type'=>'Diesel',  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>3,  'user_id'=>5,    'plate_number'=>'JKL 4456', 'brand'=>'Toyota',   'model'=>'Hilux',      'year'=>2019, 'mileage'=>112000, 'color'=>'Silver', 'fuel_type'=>'Diesel',  'created_at'=>now(),'updated_at'=>now()],
            // Mulia Logistik — Fifi (user 6) owns this 1
            // Both Hafiz and Fifi see all 4 vehicles (shared company fleet)
            ['id'=>4,  'user_id'=>6,    'plate_number'=>'JDE 7890', 'brand'=>'Nissan',   'model'=>'Navara',     'year'=>2022, 'mileage'=>43000,  'color'=>'Black',  'fuel_type'=>'Diesel',  'created_at'=>now(),'updated_at'=>now()],
            // Perdana Fleet (user 7)
            ['id'=>5,  'user_id'=>7,    'plate_number'=>'WXY 2211', 'brand'=>'Toyota',   'model'=>'Vios',       'year'=>2021, 'mileage'=>55000,  'color'=>'White',  'fuel_type'=>'Petrol',  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>6,  'user_id'=>7,    'plate_number'=>'WXY 2222', 'brand'=>'Toyota',   'model'=>'Vios',       'year'=>2021, 'mileage'=>58000,  'color'=>'White',  'fuel_type'=>'Petrol',  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>7,  'user_id'=>7,    'plate_number'=>'WPQ 8833', 'brand'=>'Honda',    'model'=>'City',       'year'=>2020, 'mileage'=>72000,  'color'=>'Grey',   'fuel_type'=>'Petrol',  'created_at'=>now(),'updated_at'=>now()],
            // Nusantara Express (user 8)
            ['id'=>8,  'user_id'=>8,    'plate_number'=>'KB 1234 A','brand'=>'Mercedes', 'model'=>'Sprinter',   'year'=>2019, 'mileage'=>135000, 'color'=>'White',  'fuel_type'=>'Diesel',  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>9,  'user_id'=>8,    'plate_number'=>'KB 5678 B','brand'=>'Hino',     'model'=>'300 Series', 'year'=>2020, 'mileage'=>98000,  'color'=>'White',  'fuel_type'=>'Diesel',  'created_at'=>now(),'updated_at'=>now()],
            // Individual — Ain (user 9)
            ['id'=>10, 'user_id'=>9,    'plate_number'=>'VKK 3456', 'brand'=>'Perodua',  'model'=>'Myvi',       'year'=>2022, 'mileage'=>28000,  'color'=>'Red',    'fuel_type'=>'Petrol',  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>11, 'user_id'=>9,    'plate_number'=>'VBB 7712', 'brand'=>'Proton',   'model'=>'X50',        'year'=>2023, 'mileage'=>12000,  'color'=>'Blue',   'fuel_type'=>'Petrol',  'created_at'=>now(),'updated_at'=>now()],
            // Individual — Faizal (user 10)
            ['id'=>12, 'user_id'=>10,   'plate_number'=>'PBD 9921', 'brand'=>'Proton',   'model'=>'Saga',       'year'=>2018, 'mileage'=>95000,  'color'=>'Black',  'fuel_type'=>'Petrol',  'created_at'=>now(),'updated_at'=>now()],
            // Individual — Nizam (user 11)
            ['id'=>13, 'user_id'=>11,   'plate_number'=>'BCG 4411', 'brand'=>'Honda',    'model'=>'Civic',      'year'=>2021, 'mileage'=>41000,  'color'=>'Silver', 'fuel_type'=>'Petrol',  'created_at'=>now(),'updated_at'=>now()],
            // Walk-in vehicles (no registered owner)
            ['id'=>14, 'user_id'=>null, 'plate_number'=>'SAB 6677', 'brand'=>'Toyota',   'model'=>'Fortuner',   'year'=>2020, 'mileage'=>61000,  'color'=>'Brown',  'fuel_type'=>'Diesel',  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>15, 'user_id'=>null, 'plate_number'=>'BNX 3310', 'brand'=>'Perodua',  'model'=>'Bezza',      'year'=>2019, 'mileage'=>78000,  'color'=>'White',  'fuel_type'=>'Petrol',  'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ----------------------------------------------------------------
        // SPARE PARTS (15) — 3 intentionally low stock for demo
        // ----------------------------------------------------------------
        DB::table('spare_parts')->insert([
            ['id'=>1,  'name'=>'Engine Oil 5W-30 (4L)',        'brand'=>'Castrol',  'part_number'=>'CO-5W30-4L',  'category'=>'Lubricants',   'unit_price'=>65.00,  'stock'=>48, 'min_stock'=>10, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>2,  'name'=>'Engine Oil Filter',            'brand'=>'Denso',    'part_number'=>'DN-OF-001',   'category'=>'Filters',      'unit_price'=>18.50,  'stock'=>35, 'min_stock'=>10, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>3,  'name'=>'Air Filter Element',           'brand'=>'K&N',      'part_number'=>'KN-AF-221',   'category'=>'Filters',      'unit_price'=>55.00,  'stock'=>20, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>4,  'name'=>'Brake Pad Set (Front)',        'brand'=>'Brembo',   'part_number'=>'BR-FP-44A',   'category'=>'Brakes',       'unit_price'=>185.00, 'stock'=>14, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>5,  'name'=>'Brake Pad Set (Rear)',         'brand'=>'Brembo',   'part_number'=>'BR-RP-44B',   'category'=>'Brakes',       'unit_price'=>145.00, 'stock'=>12, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>6,  'name'=>'Spark Plug (set of 4)',        'brand'=>'NGK',      'part_number'=>'NGK-BKR6E',   'category'=>'Engine',       'unit_price'=>72.00,  'stock'=>25, 'min_stock'=>8,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>7,  'name'=>'Car Battery 65D26L',           'brand'=>'Amaron',   'part_number'=>'AM-65D26L',   'category'=>'Electrical',   'unit_price'=>320.00, 'stock'=>8,  'min_stock'=>3,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>8,  'name'=>'Radiator Coolant (1L)',        'brand'=>'Prestone', 'part_number'=>'PR-RC-1L',    'category'=>'Cooling',      'unit_price'=>22.00,  'stock'=>30, 'min_stock'=>8,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>9,  'name'=>'Transmission Fluid ATF (1L)', 'brand'=>'Toyota',   'part_number'=>'TY-ATF-WS',   'category'=>'Transmission', 'unit_price'=>38.00,  'stock'=>22, 'min_stock'=>6,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>10, 'name'=>'Serpentine Belt',              'brand'=>'Gates',    'part_number'=>'GT-SB-6PK',   'category'=>'Engine',       'unit_price'=>95.00,  'stock'=>4,  'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()], // LOW
            ['id'=>11, 'name'=>'Power Steering Fluid (1L)',    'brand'=>'Petronas', 'part_number'=>'PT-PSF-1L',   'category'=>'Steering',     'unit_price'=>25.00,  'stock'=>18, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>12, 'name'=>'Wiper Blade 22"',              'brand'=>'Bosch',    'part_number'=>'BO-WB-22',    'category'=>'Accessories',  'unit_price'=>35.00,  'stock'=>2,  'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()], // LOW
            ['id'=>13, 'name'=>'Cabin Air Filter',             'brand'=>'Mann',     'part_number'=>'MN-CAF-CU',   'category'=>'Filters',      'unit_price'=>42.00,  'stock'=>16, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>14, 'name'=>'Shock Absorber (Front Pair)',  'brand'=>'Monroe',   'part_number'=>'MR-SA-FP01',  'category'=>'Suspension',   'unit_price'=>480.00, 'stock'=>3,  'min_stock'=>4,  'created_at'=>now(),'updated_at'=>now()], // LOW
            ['id'=>15, 'name'=>'Timing Belt Kit',              'brand'=>'Dayco',    'part_number'=>'DY-TBK-001',  'category'=>'Engine',       'unit_price'=>260.00, 'stock'=>6,  'min_stock'=>3,  'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ----------------------------------------------------------------
        // APPOINTMENTS
        // Status mix: 6 completed, 2 walk-in completed,
        //             3 confirmed (active jobs), 1 walk-in confirmed (today),
        //             4 pending, 2 cancelled
        // ----------------------------------------------------------------
        DB::table('appointments')->insert([
            // --- COMPLETED ---
            ['id'=>1,  'user_id'=>9,  'vehicle_id'=>10, 'date'=>Carbon::now()->subDays(30), 'time'=>'09:00', 'service_type'=>'Oil Change',               'status'=>'completed', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>null,                           'created_at'=>Carbon::now()->subDays(31),'updated_at'=>Carbon::now()->subDays(30)],
            ['id'=>2,  'user_id'=>10, 'vehicle_id'=>12, 'date'=>Carbon::now()->subDays(25), 'time'=>'10:30', 'service_type'=>'Brake Inspection',         'status'=>'completed', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'Rear brakes squeaking',        'created_at'=>Carbon::now()->subDays(26),'updated_at'=>Carbon::now()->subDays(25)],
            ['id'=>3,  'user_id'=>5,  'vehicle_id'=>1,  'date'=>Carbon::now()->subDays(20), 'time'=>'08:30', 'service_type'=>'Full Engine Service',      'status'=>'completed', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'Fleet vehicle monthly check',  'created_at'=>Carbon::now()->subDays(21),'updated_at'=>Carbon::now()->subDays(20)],
            ['id'=>4,  'user_id'=>7,  'vehicle_id'=>5,  'date'=>Carbon::now()->subDays(18), 'time'=>'11:00', 'service_type'=>'Tyre Rotation',            'status'=>'completed', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>null,                           'created_at'=>Carbon::now()->subDays(19),'updated_at'=>Carbon::now()->subDays(18)],
            ['id'=>5,  'user_id'=>11, 'vehicle_id'=>13, 'date'=>Carbon::now()->subDays(15), 'time'=>'14:00', 'service_type'=>'Battery Replacement',      'status'=>'completed', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'Car not starting',             'created_at'=>Carbon::now()->subDays(16),'updated_at'=>Carbon::now()->subDays(15)],
            ['id'=>6,  'user_id'=>8,  'vehicle_id'=>8,  'date'=>Carbon::now()->subDays(12), 'time'=>'09:30', 'service_type'=>'Engine Diagnostics',       'status'=>'completed', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'Check engine light on',        'created_at'=>Carbon::now()->subDays(13),'updated_at'=>Carbon::now()->subDays(12)],
            // --- WALK-IN COMPLETED ---
            ['id'=>7,  'user_id'=>null,'vehicle_id'=>14,'date'=>Carbon::now()->subDays(10), 'time'=>'10:00', 'service_type'=>'Oil Change',               'status'=>'completed', 'is_walkin'=>true,  'walkin_name'=>'Encik Rashdan bin Ali',  'walkin_contact'=>'012-3344556','notes'=>null,                          'created_at'=>Carbon::now()->subDays(10),'updated_at'=>Carbon::now()->subDays(10)],
            ['id'=>8,  'user_id'=>null,'vehicle_id'=>15,'date'=>Carbon::now()->subDays(7),  'time'=>'11:30', 'service_type'=>'Brake Service',            'status'=>'completed', 'is_walkin'=>true,  'walkin_name'=>'Puan Haslinda binti Yusof','walkin_contact'=>'019-7788990','notes'=>'Brake pedal soft',           'created_at'=>Carbon::now()->subDays(7), 'updated_at'=>Carbon::now()->subDays(7)],
            // --- CONFIRMED (active — will have job cards in progress) ---
            ['id'=>9,  'user_id'=>5,  'vehicle_id'=>2,  'date'=>Carbon::now()->subDays(3),  'time'=>'08:00', 'service_type'=>'Full Engine Service',      'status'=>'confirmed', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'Regular 3-month fleet service','created_at'=>Carbon::now()->subDays(4), 'updated_at'=>Carbon::now()->subDays(3)],
            ['id'=>10, 'user_id'=>9,  'vehicle_id'=>11, 'date'=>Carbon::now()->subDays(2),  'time'=>'09:00', 'service_type'=>'Air Conditioning Service', 'status'=>'confirmed', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'AC not cold enough',           'created_at'=>Carbon::now()->subDays(3), 'updated_at'=>Carbon::now()->subDays(2)],
            ['id'=>11, 'user_id'=>8,  'vehicle_id'=>9,  'date'=>Carbon::now()->subDays(1),  'time'=>'13:00', 'service_type'=>'Transmission Service',    'status'=>'confirmed', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'Gear shifting rough',          'created_at'=>Carbon::now()->subDays(2), 'updated_at'=>Carbon::now()->subDays(1)],
            // --- WALK-IN CONFIRMED (today — demo live job) ---
            ['id'=>12, 'user_id'=>null,'vehicle_id'=>14,'date'=>Carbon::now(),              'time'=>'10:30', 'service_type'=>'Suspension Check',         'status'=>'confirmed', 'is_walkin'=>true,  'walkin_name'=>'Encik Rashdan bin Ali',  'walkin_contact'=>'012-3344556','notes'=>'Car vibrating at high speed', 'created_at'=>Carbon::now(),            'updated_at'=>Carbon::now()],
            // --- PENDING (waiting admin action) ---
            ['id'=>13, 'user_id'=>10, 'vehicle_id'=>12, 'date'=>Carbon::now()->addDays(2),  'time'=>'10:00', 'service_type'=>'Full Vehicle Inspection',  'status'=>'pending',   'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>null,                           'created_at'=>Carbon::now(),            'updated_at'=>Carbon::now()],
            ['id'=>14, 'user_id'=>11, 'vehicle_id'=>13, 'date'=>Carbon::now()->addDays(3),  'time'=>'14:30', 'service_type'=>'Suspension Inspection',   'status'=>'pending',   'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'Noise from front suspension', 'created_at'=>Carbon::now(),            'updated_at'=>Carbon::now()],
            ['id'=>15, 'user_id'=>7,  'vehicle_id'=>7,  'date'=>Carbon::now()->addDays(4),  'time'=>'09:00', 'service_type'=>'Oil Change',               'status'=>'pending',   'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>null,                           'created_at'=>Carbon::now(),            'updated_at'=>Carbon::now()],
            ['id'=>16, 'user_id'=>6,  'vehicle_id'=>4,  'date'=>Carbon::now()->addDays(5),  'time'=>'11:00', 'service_type'=>'Engine Diagnostics',       'status'=>'pending',   'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'Fuel consumption increased',  'created_at'=>Carbon::now(),            'updated_at'=>Carbon::now()],
            // --- CANCELLED ---
            ['id'=>17, 'user_id'=>9,  'vehicle_id'=>10, 'date'=>Carbon::now()->subDays(8),  'time'=>'15:00', 'service_type'=>'Tyre Rotation',            'status'=>'cancelled', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>'Customer rescheduled',         'created_at'=>Carbon::now()->subDays(9), 'updated_at'=>Carbon::now()->subDays(8)],
            ['id'=>18, 'user_id'=>10, 'vehicle_id'=>12, 'date'=>Carbon::now()->subDays(5),  'time'=>'09:00', 'service_type'=>'AC Service',               'status'=>'cancelled', 'is_walkin'=>false, 'walkin_name'=>null,                    'walkin_contact'=>null,      'notes'=>null,                           'created_at'=>Carbon::now()->subDays(6), 'updated_at'=>Carbon::now()->subDays(5)],
        ]);

        // ----------------------------------------------------------------
        // JOB CARDS (12)
        // Appointments 1–8 completed, 9–12 active/in-progress
        // ----------------------------------------------------------------
        DB::table('job_cards')->insert([
            // --- COMPLETED ---
            [
                'id'=>1, 'appointment_id'=>1, 'vehicle_id'=>10, 'staff_id'=>2, 'job_type_id'=>2,
                'current_stage'        => 'completed',
                'diagnosis'            => 'Routine oil change. Oil degraded at 8,000 km interval.',
                'symptoms'             => json_encode(['Engine warning light on']),
                'technician_notes'     => 'Castrol 5W-30 replaced. Filter changed. No other issues.',
                'total_cost'           => 113.50,
                'estimated_completion' => Carbon::now()->subDays(30)->addHours(1),
                'created_at'           => Carbon::now()->subDays(30),
                'updated_at'           => Carbon::now()->subDays(30),
            ],
            [
                'id'=>2, 'appointment_id'=>2, 'vehicle_id'=>12, 'staff_id'=>3, 'job_type_id'=>3,
                'current_stage'        => 'completed',
                'diagnosis'            => 'Rear brake pads worn to minimum. Metal on metal contact.',
                'symptoms'             => json_encode(['Squeaking / grinding brakes','Brake pedal soft or spongy']),
                'technician_notes'     => 'Rear pads replaced. Front pads 60% — advised to monitor.',
                'total_cost'           => 205.00,
                'estimated_completion' => Carbon::now()->subDays(25)->addHours(2),
                'created_at'           => Carbon::now()->subDays(25),
                'updated_at'           => Carbon::now()->subDays(25),
            ],
            [
                'id'=>3, 'appointment_id'=>3, 'vehicle_id'=>1, 'staff_id'=>2, 'job_type_id'=>1,
                'current_stage'        => 'completed',
                'diagnosis'            => 'Full service. Valve cover gasket leaking. Fluids topped up.',
                'symptoms'             => json_encode(['Unusual engine noise','Oil leak']),
                'technician_notes'     => 'Gasket replaced. All systems normal post-service.',
                'total_cost'           => 548.00,
                'estimated_completion' => Carbon::now()->subDays(20)->addHours(3),
                'created_at'           => Carbon::now()->subDays(20),
                'updated_at'           => Carbon::now()->subDays(20),
            ],
            [
                'id'=>4, 'appointment_id'=>4, 'vehicle_id'=>5, 'staff_id'=>4, 'job_type_id'=>4,
                'current_stage'        => 'completed',
                'diagnosis'            => 'Tyre rotation performed. Pressure set to spec.',
                'symptoms'             => json_encode(['Unusual tyre wear']),
                'technician_notes'     => 'Front to rear rotation. All 4 balanced. Tread acceptable.',
                'total_cost'           => 80.00,
                'estimated_completion' => Carbon::now()->subDays(18)->addHour(),
                'created_at'           => Carbon::now()->subDays(18),
                'updated_at'           => Carbon::now()->subDays(18),
            ],
            [
                'id'=>5, 'appointment_id'=>5, 'vehicle_id'=>13, 'staff_id'=>3, 'job_type_id'=>5,
                'current_stage'        => 'completed',
                'diagnosis'            => 'Battery fully discharged. Unable to hold charge.',
                'symptoms'             => json_encode(['Battery warning light','Starter motor issue']),
                'technician_notes'     => 'Amaron 65D26L installed. Alternator output normal.',
                'total_cost'           => 350.00,
                'estimated_completion' => Carbon::now()->subDays(15)->addMinutes(45),
                'created_at'           => Carbon::now()->subDays(15),
                'updated_at'           => Carbon::now()->subDays(15),
            ],
            [
                'id'=>6, 'appointment_id'=>6, 'vehicle_id'=>8, 'staff_id'=>2, 'job_type_id'=>9,
                'current_stage'        => 'completed',
                'diagnosis'            => 'Fault code P0301 — cylinder 1 misfire. Spark plugs fouled.',
                'symptoms'             => json_encode(['Engine warning light on','Engine stalling or misfiring','Excessive exhaust smoke']),
                'technician_notes'     => 'All 4 spark plugs replaced. Fault codes cleared. Test drive confirmed resolved.',
                'total_cost'           => 117.00,
                'estimated_completion' => Carbon::now()->subDays(12)->addHour(),
                'created_at'           => Carbon::now()->subDays(12),
                'updated_at'           => Carbon::now()->subDays(12),
            ],
            // --- WALK-IN COMPLETED ---
            [
                'id'=>7, 'appointment_id'=>7, 'vehicle_id'=>14, 'staff_id'=>4, 'job_type_id'=>2,
                'current_stage'        => 'completed',
                'diagnosis'            => 'Oil overdue by 2,000 km. Sludge present.',
                'symptoms'             => json_encode(['Unusual engine noise','Oil leak']),
                'technician_notes'     => 'Engine flushed. Oil and filter replaced. Next service advised in 5,000 km.',
                'total_cost'           => 113.50,
                'estimated_completion' => Carbon::now()->subDays(10)->addHour(),
                'created_at'           => Carbon::now()->subDays(10),
                'updated_at'           => Carbon::now()->subDays(10),
            ],
            [
                'id'=>8, 'appointment_id'=>8, 'vehicle_id'=>15, 'staff_id'=>3, 'job_type_id'=>3,
                'current_stage'        => 'completed',
                'diagnosis'            => 'Front brake pads at minimum. Disc surface scored.',
                'symptoms'             => json_encode(['Squeaking / grinding brakes','Vehicle pulls to one side when braking']),
                'technician_notes'     => 'Front pads and discs replaced. Fluid topped up.',
                'total_cost'           => 245.00,
                'estimated_completion' => Carbon::now()->subDays(7)->addHours(2),
                'created_at'           => Carbon::now()->subDays(7),
                'updated_at'           => Carbon::now()->subDays(7),
            ],
            // --- ACTIVE IN-PROGRESS ---
            [
                'id'=>9, 'appointment_id'=>9, 'vehicle_id'=>2, 'staff_id'=>2, 'job_type_id'=>1,
                'current_stage'        => 'repairing',
                'diagnosis'            => 'Valve cover gasket leaking. Full service in progress.',
                'symptoms'             => json_encode(['Oil leak','Unusual engine noise']),
                'technician_notes'     => 'Replacing gasket now. Oil change queued after.',
                'total_cost'           => 185.00,
                'estimated_completion' => Carbon::now()->addHours(3),
                'created_at'           => Carbon::now()->subDays(3),
                'updated_at'           => Carbon::now(),
            ],
            [
                'id'=>10, 'appointment_id'=>10, 'vehicle_id'=>11, 'staff_id'=>3, 'job_type_id'=>6,
                'current_stage'        => 'diagnosing',
                'diagnosis'            => 'AC compressor weak. Refrigerant low.',
                'symptoms'             => json_encode(['Engine warning light on']),
                'technician_notes'     => null,
                'total_cost'           => 0.00,
                'estimated_completion' => Carbon::now()->addHours(2),
                'created_at'           => Carbon::now()->subDays(2),
                'updated_at'           => Carbon::now(),
            ],
            [
                'id'=>11, 'appointment_id'=>11, 'vehicle_id'=>9, 'staff_id'=>4, 'job_type_id'=>8,
                'current_stage'        => 'waiting_parts',
                'diagnosis'            => 'Transmission fluid degraded. Solenoid valve suspected.',
                'symptoms'             => json_encode(['Gear slipping','Delayed engagement','Unusual transmission noise']),
                'technician_notes'     => 'Waiting for ATF stock. Solenoid valve on order.',
                'total_cost'           => 0.00,
                'estimated_completion' => Carbon::now()->addHours(5),
                'created_at'           => Carbon::now()->subDays(1),
                'updated_at'           => Carbon::now(),
            ],
            [
                'id'=>12, 'appointment_id'=>12, 'vehicle_id'=>14, 'staff_id'=>2, 'job_type_id'=>7,
                'current_stage'        => 'received',
                'diagnosis'            => null,
                'symptoms'             => json_encode([]),
                'technician_notes'     => null,
                'total_cost'           => 0.00,
                'estimated_completion' => Carbon::now()->addHours(2),
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
        ]);

        // ----------------------------------------------------------------
        // JOB CARD PARTS
        // ----------------------------------------------------------------
        DB::table('job_card_parts')->insert([
            // Job 1 — oil change
            ['job_card_id'=>1, 'spare_part_id'=>1, 'quantity'=>1, 'unit_price'=>65.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>1, 'spare_part_id'=>2, 'quantity'=>1, 'unit_price'=>18.50, 'created_at'=>now(),'updated_at'=>now()],
            // Job 2 — brake pads rear
            ['job_card_id'=>2, 'spare_part_id'=>5, 'quantity'=>1, 'unit_price'=>145.00,'created_at'=>now(),'updated_at'=>now()],
            // Job 3 — full service
            ['job_card_id'=>3, 'spare_part_id'=>1, 'quantity'=>1, 'unit_price'=>65.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>3, 'spare_part_id'=>2, 'quantity'=>1, 'unit_price'=>18.50, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>3, 'spare_part_id'=>3, 'quantity'=>1, 'unit_price'=>55.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>3, 'spare_part_id'=>13,'quantity'=>1, 'unit_price'=>42.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>3, 'spare_part_id'=>8, 'quantity'=>3, 'unit_price'=>22.00, 'created_at'=>now(),'updated_at'=>now()],
            // Job 4 — tyre rotation (no parts)
            // Job 5 — battery
            ['job_card_id'=>5, 'spare_part_id'=>7, 'quantity'=>1, 'unit_price'=>320.00,'created_at'=>now(),'updated_at'=>now()],
            // Job 6 — spark plugs
            ['job_card_id'=>6, 'spare_part_id'=>6, 'quantity'=>1, 'unit_price'=>72.00, 'created_at'=>now(),'updated_at'=>now()],
            // Job 7 — walk-in oil change
            ['job_card_id'=>7, 'spare_part_id'=>1, 'quantity'=>1, 'unit_price'=>65.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>7, 'spare_part_id'=>2, 'quantity'=>1, 'unit_price'=>18.50, 'created_at'=>now(),'updated_at'=>now()],
            // Job 8 — walk-in brake
            ['job_card_id'=>8, 'spare_part_id'=>4, 'quantity'=>1, 'unit_price'=>185.00,'created_at'=>now(),'updated_at'=>now()],
            // Job 9 — active, partial
            ['job_card_id'=>9, 'spare_part_id'=>1, 'quantity'=>1, 'unit_price'=>65.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>9, 'spare_part_id'=>2, 'quantity'=>1, 'unit_price'=>18.50, 'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ----------------------------------------------------------------
        // LABOUR CHARGES
        // ----------------------------------------------------------------
        DB::table('labour_charges')->insert([
            ['job_card_id'=>1,  'description'=>'Oil Change Labour',               'charge'=>30.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>2,  'description'=>'Brake Pad Replacement Labour',    'charge'=>60.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>3,  'description'=>'Full Engine Service Labour',      'charge'=>120.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>3,  'description'=>'Wheel Alignment',                 'charge'=>80.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>4,  'description'=>'Tyre Rotation Labour',            'charge'=>40.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>4,  'description'=>'Wheel Balancing',                 'charge'=>40.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>5,  'description'=>'Battery Replacement Labour',      'charge'=>30.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>6,  'description'=>'Engine Diagnostics Scan Labour',  'charge'=>45.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>6,  'description'=>'Spark Plug Replacement Labour',   'charge'=>30.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>7,  'description'=>'Oil Change Labour',               'charge'=>30.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>8,  'description'=>'Brake Pad Replacement Labour',    'charge'=>60.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>9,  'description'=>'Full Engine Service Labour',      'charge'=>120.00, 'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ----------------------------------------------------------------
        // SERVICE HISTORY (one per completed job card)
        // ----------------------------------------------------------------
        DB::table('service_history')->insert([
            ['job_card_id'=>1, 'vehicle_id'=>10, 'service_date'=>Carbon::now()->subDays(30), 'description'=>'Oil & Filter Change',       'cost'=>113.50, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>2, 'vehicle_id'=>12, 'service_date'=>Carbon::now()->subDays(25), 'description'=>'Brake Pad Replacement',     'cost'=>205.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>3, 'vehicle_id'=>1,  'service_date'=>Carbon::now()->subDays(20), 'description'=>'Full Engine Service',        'cost'=>548.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>4, 'vehicle_id'=>5,  'service_date'=>Carbon::now()->subDays(18), 'description'=>'Tyre Rotation & Balancing', 'cost'=>80.00,  'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>5, 'vehicle_id'=>13, 'service_date'=>Carbon::now()->subDays(15), 'description'=>'Battery Replacement',       'cost'=>350.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>6, 'vehicle_id'=>8,  'service_date'=>Carbon::now()->subDays(12), 'description'=>'Engine Diagnostics Scan',   'cost'=>117.00, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>7, 'vehicle_id'=>14, 'service_date'=>Carbon::now()->subDays(10), 'description'=>'Oil & Filter Change',       'cost'=>113.50, 'created_at'=>now(),'updated_at'=>now()],
            ['job_card_id'=>8, 'vehicle_id'=>15, 'service_date'=>Carbon::now()->subDays(7),  'description'=>'Brake Pad Replacement',     'cost'=>245.00, 'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ----------------------------------------------------------------
        // MAINTENANCE ALERTS
        // Mix of urgency, read/unread, corporate fleet + individual
        // ----------------------------------------------------------------
        DB::table('maintenance_alerts')->insert([
            // Corporate fleet alerts
            ['vehicle_id'=>3,  'alert_type'=>'Engine Service Due',     'urgency'=>'high',   'recommendation'=>'Hilux at 112,000 km — full engine service overdue. Book immediately.',            'is_read'=>false, 'created_at'=>Carbon::now()->subDays(5), 'updated_at'=>Carbon::now()->subDays(5)],
            ['vehicle_id'=>8,  'alert_type'=>'Engine Service Due',     'urgency'=>'high',   'recommendation'=>'Sprinter at 135,000 km — engine service critical. Risk of breakdown.',            'is_read'=>false, 'created_at'=>Carbon::now()->subDays(3), 'updated_at'=>Carbon::now()->subDays(3)],
            ['vehicle_id'=>1,  'alert_type'=>'Oil Change Due',         'urgency'=>'medium', 'recommendation'=>'Next oil change due at 90,000 km or within 1 month.',                             'is_read'=>true,  'created_at'=>Carbon::now()->subDays(20),'updated_at'=>Carbon::now()->subDays(18)],
            ['vehicle_id'=>9,  'alert_type'=>'Transmission Service',   'urgency'=>'high',   'recommendation'=>'Transmission fluid degraded. Service in progress — job card #11 open.',           'is_read'=>false, 'created_at'=>Carbon::now()->subDays(1), 'updated_at'=>Carbon::now()->subDays(1)],
            ['vehicle_id'=>4,  'alert_type'=>'Tyre Rotation Due',      'urgency'=>'low',    'recommendation'=>'Tyre rotation recommended. Schedule at next visit.',                               'is_read'=>false, 'created_at'=>Carbon::now()->subDays(7), 'updated_at'=>Carbon::now()->subDays(7)],
            ['vehicle_id'=>6,  'alert_type'=>'Air Filter Replacement', 'urgency'=>'medium', 'recommendation'=>'Air filter replacement due. Book within 2 weeks.',                                'is_read'=>false, 'created_at'=>Carbon::now()->subDays(4), 'updated_at'=>Carbon::now()->subDays(4)],
            // Individual customer alerts
            ['vehicle_id'=>10, 'alert_type'=>'Oil Change Due',         'urgency'=>'medium', 'recommendation'=>'Myvi approaching 30,000 km service interval. Schedule oil change soon.',          'is_read'=>false, 'created_at'=>Carbon::now()->subDays(3), 'updated_at'=>Carbon::now()->subDays(3)],
            ['vehicle_id'=>12, 'alert_type'=>'Road Tax Expiry',        'urgency'=>'high',   'recommendation'=>'Road tax expiring within 30 days. Renew before expiry to avoid summons.',          'is_read'=>false, 'created_at'=>Carbon::now()->subDays(2), 'updated_at'=>Carbon::now()->subDays(2)],
            ['vehicle_id'=>13, 'alert_type'=>'Brake Inspection',       'urgency'=>'medium', 'recommendation'=>'Brake pads approaching wear limit based on last service. Book inspection.',        'is_read'=>true,  'created_at'=>Carbon::now()->subDays(15),'updated_at'=>Carbon::now()->subDays(10)],
            ['vehicle_id'=>11, 'alert_type'=>'Air Conditioning Service','urgency'=>'low',   'recommendation'=>'AC service recommended every 2 years. Last serviced over 18 months ago.',          'is_read'=>false, 'created_at'=>Carbon::now()->subDays(1), 'updated_at'=>Carbon::now()->subDays(1)],
        ]);

        // ----------------------------------------------------------------
        // ACCOUNT REQUESTS
        // Demo: one pending add_pic request from Hafiz to show admin badge
        // ----------------------------------------------------------------
        DB::table('account_requests')->insert([
            [
                'requested_by'   => 5,
                'company_id'     => 1,
                'type'           => 'add_pic',
                'target_name'    => 'Encik Farouk bin Aziz',
                'target_email'   => 'farouk@mulialogistik.com.my',
                'target_phone'   => '011-98765432',
                'target_user_id' => null,
                'status'         => 'pending',
                'notes'          => 'New fleet manager joining next month.',
                'created_at'     => Carbon::now()->subHours(3),
                'updated_at'     => Carbon::now()->subHours(3),
            ],
            [
                'requested_by'   => 7,
                'company_id'     => 2,
                'type'           => 'remove_pic',
                'target_name'    => null,
                'target_email'   => null,
                'target_phone'   => null,
                'target_user_id' => null,
                'status'         => 'rejected',
                'notes'          => 'Test request — rejected by admin.',
                'created_at'     => Carbon::now()->subDays(5),
                'updated_at'     => Carbon::now()->subDays(4),
            ],
        ]);
    }
}