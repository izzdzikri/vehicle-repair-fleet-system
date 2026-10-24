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
        // TRUNCATE ALL TABLES
        // ----------------------------------------------------------------
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('job_card_checkins')->truncate();
        DB::table('notifications')->truncate();
        DB::table('job_feedbacks')->truncate();
        DB::table('activity_logs')->truncate();
        DB::table('settings')->truncate();
        DB::table('chat_sessions')->truncate();
        DB::table('account_requests')->truncate();
        DB::table('maintenance_alerts')->truncate();
        DB::table('service_history')->truncate();
        DB::table('labour_charges')->truncate();
        DB::table('job_card_parts')->truncate();
        DB::table('payments')->truncate();
        DB::table('invoices')->truncate();
        DB::table('purchase_orders')->truncate();
        DB::table('salary_payments')->truncate();
        DB::table('leave_requests')->truncate();
        DB::table('staff_attendance')->truncate();
        DB::table('job_cards')->truncate();
        DB::table('appointments')->truncate();
        DB::table('spare_parts')->truncate();
        DB::table('job_types')->truncate();
        DB::table('suppliers')->truncate();
        DB::table('trip_logs')->truncate();
        DB::table('vehicles')->truncate();
        DB::table('users')->truncate();
        DB::table('companies')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ----------------------------------------------------------------
        // 1. COMPANIES (6 Corporate Fleet Clients)
        // ----------------------------------------------------------------
        DB::table('companies')->insert([
            [
                'id'               => 1,
                'name'             => 'Syarikat Logistik Mulia Sdn Bhd',
                'registration_no'  => 'SA0012345-X',
                'address'          => 'No 12, Jalan Perindustrian 4, Kawasan Perindustrian Senai, 81400 Senai, Johor',
                'email'            => 'ops@mulialogistik.com.my',
                'phone'            => '07-5551234',
                'person_in_charge' => 'Encik Hafiz bin Roslan',
                'created_at'       => now()->subMonths(18),
                'updated_at'       => now(),
            ],
            [
                'id'               => 2,
                'name'             => 'Perdana Fleet Services Sdn Bhd',
                'registration_no'  => 'KL0098765-P',
                'address'          => 'Unit 3-A, Menara Perdana, Kompleks Perdagangan Cheras, 56000 Kuala Lumpur',
                'email'            => 'fleet@perdanafleet.my',
                'phone'            => '03-9876543',
                'person_in_charge' => 'Puan Rozita binti Azman',
                'created_at'       => now()->subMonths(16),
                'updated_at'       => now(),
            ],
            [
                'id'               => 3,
                'name'             => 'Nusantara Express Sdn Bhd',
                'registration_no'  => 'JH0056321-K',
                'address'          => 'Lot 7, Kawasan Perindustrian Pengkalan Chepa II, 16100 Kota Bharu, Kelantan',
                'email'            => 'admin@nusantaraexpress.com',
                'phone'            => '09-7412580',
                'person_in_charge' => 'Encik Zulkifli bin Daud',
                'created_at'       => now()->subMonths(15),
                'updated_at'       => now(),
            ],
            [
                'id'               => 4,
                'name'             => 'Southern Cold Chain Logistics Sdn Bhd',
                'registration_no'  => '1284567-T',
                'address'          => 'Plo 45, Jalan Keluli 3, Kawasan Perindustrian Pasir Gudang, 81700 Pasir Gudang, Johor',
                'email'            => 'ops@southerncoldchain.my',
                'phone'            => '07-2518800',
                'person_in_charge' => 'Kevin Lee Wei Ming',
                'created_at'       => now()->subMonths(12),
                'updated_at'       => now(),
            ],
            [
                'id'               => 5,
                'name'             => 'Mega Port Haulage & Marine Sdn Bhd',
                'registration_no'  => '1092834-M',
                'address'          => 'Level 2, Container Terminal Complex, Pelabuhan Tanjung Pelepas, 81560 Gelang Patah, Johor',
                'email'            => 'logistics@megaporthaulage.com',
                'phone'            => '07-5079100',
                'person_in_charge' => 'Captain Razali bin Hamzah',
                'created_at'       => now()->subMonths(10),
                'updated_at'       => now(),
            ],
            [
                'id'               => 6,
                'name'             => 'Apex Swift Courier Sdn Bhd',
                'registration_no'  => '1402931-A',
                'address'          => 'No 28, Jalan Impian Emas 5/1, Taman Impian Emas, 81300 Skudai, Johor',
                'email'            => 'fleet@apexswift.my',
                'phone'            => '07-5582300',
                'person_in_charge' => 'Michelle Wong Shu Fen',
                'created_at'       => now()->subMonths(8),
                'updated_at'       => now(),
            ],
        ]);

        // ----------------------------------------------------------------
        // 2. USERS (26 Users: Admin, Staff, Coordinators, Corporate, Individual)
        // ----------------------------------------------------------------
        $pw = Hash::make('password');
        DB::table('users')->insert([
            // Admin (1)
            [
                'id'          => 1,
                'name'        => 'Ahmad Firdaus bin Ismail',
                'email'       => 'admin@teraju.my',
                'password'    => $pw,
                'role'        => 'admin',
                'status'      => 'active',
                'contact_no'  => '013-3421100',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => null,
                'staff_role'  => 'Workshop Director',
                'permissions' => null,
                'monthly_salary' => 6500.00,
                'hire_date'   => '2021-01-01',
                'created_at'  => now()->subMonths(24),
                'updated_at'  => now(),
            ],
            // Staff / Mechanics (2–6)
            [
                'id'          => 2,
                'name'        => 'Mohd Ridhwan bin Sulaiman',
                'email'       => 'ridhwan@teraju.my',
                'password'    => $pw,
                'role'        => 'staff',
                'status'      => 'active',
                'contact_no'  => '011-22334455',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => json_encode([1, 2, 9, 10]),
                'staff_role'  => 'Senior Master Mechanic',
                'permissions' => json_encode(['job_cards.manage_all' => true]),
                'monthly_salary' => 3400.00,
                'hire_date'   => '2022-03-01',
                'created_at'  => now()->subMonths(20),
                'updated_at'  => now(),
            ],
            [
                'id'          => 3,
                'name'        => 'Siti Nabilah binti Kamal',
                'email'       => 'nabilah@teraju.my',
                'password'    => $pw,
                'role'        => 'staff',
                'status'      => 'active',
                'contact_no'  => '010-55667788',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => json_encode([3, 4, 6]),
                'staff_role'  => 'Brake & Climate Specialist',
                'permissions' => null,
                'monthly_salary' => 2800.00,
                'hire_date'   => '2023-01-15',
                'created_at'  => now()->subMonths(18),
                'updated_at'  => now(),
            ],
            [
                'id'          => 4,
                'name'        => 'Hairul Azwan bin Musa',
                'email'       => 'hairul@teraju.my',
                'password'    => $pw,
                'role'        => 'staff',
                'status'      => 'active',
                'contact_no'  => '012-87654321',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => json_encode([5, 7, 8]),
                'staff_role'  => 'Suspension Specialist',
                'permissions' => null,
                'monthly_salary' => 2700.00,
                'hire_date'   => '2023-07-01',
                'created_at'  => now()->subMonths(14),
                'updated_at'  => now(),
            ],
            [
                'id'          => 5,
                'name'        => 'Chong Wei Lun',
                'email'       => 'wei.lun@teraju.my',
                'password'    => $pw,
                'role'        => 'staff',
                'status'      => 'active',
                'contact_no'  => '017-3344991',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => json_encode([1, 6, 9]),
                'staff_role'  => 'Diagnostic Specialist',
                'permissions' => null,
                'monthly_salary' => 3300.00,
                'hire_date'   => '2022-11-01',
                'created_at'  => now()->subMonths(16),
                'updated_at'  => now(),
            ],
            [
                'id'          => 6,
                'name'        => 'Muhammad Faiz bin Yusof',
                'email'       => 'faiz@teraju.my',
                'password'    => $pw,
                'role'        => 'staff',
                'status'      => 'active',
                'contact_no'  => '018-9922114',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => json_encode([2, 4, 5]),
                'staff_role'  => 'Junior Apprentice',
                'permissions' => null,
                'monthly_salary' => 2200.00,
                'hire_date'   => '2024-02-15',
                'created_at'  => now()->subMonths(9),
                'updated_at'  => now(),
            ],
            // Coordinators (7–8)
            [
                'id'          => 7,
                'name'        => 'Nur Aisyah binti Kamarul',
                'email'       => 'aisyah@teraju.my',
                'password'    => $pw,
                'role'        => 'coordinator',
                'status'      => 'active',
                'contact_no'  => '019-2245566',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => null,
                'staff_role'  => 'Lead Service Coordinator',
                'permissions' => null,
                'monthly_salary' => 2600.00,
                'hire_date'   => '2023-05-10',
                'created_at'  => now()->subMonths(16),
                'updated_at'  => now(),
            ],
            [
                'id'          => 8,
                'name'        => 'Azreen binti Mansor',
                'email'       => 'azreen@teraju.my',
                'password'    => $pw,
                'role'        => 'coordinator',
                'status'      => 'active',
                'contact_no'  => '013-7711223',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => null,
                'staff_role'  => 'Customer Coordinator',
                'permissions' => null,
                'monthly_salary' => 2400.00,
                'hire_date'   => '2024-01-08',
                'created_at'  => now()->subMonths(10),
                'updated_at'  => now(),
            ],
            // Corporate Fleet PICs (9–20)
            // Company 1: Mulia Logistik
            [
                'id'          => 9,
                'name'        => 'Encik Hafiz bin Roslan',
                'email'       => 'hafiz@mulialogistik.com.my',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '019-3312200',
                'company_id'  => 1,
                'avatar'      => null,
                'pic_role'    => 'primary',
                'specialties' => null,
                'staff_role'  => 'Fleet Director',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(18),
                'updated_at'  => now(),
            ],
            [
                'id'          => 10,
                'name'        => 'Puan Fifi binti Ahmad',
                'email'       => 'fifi@mulialogistik.com.my',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '013-4186881',
                'company_id'  => 1,
                'avatar'      => null,
                'pic_role'    => 'secondary',
                'specialties' => null,
                'staff_role'  => 'Fleet Dispatcher',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(16),
                'updated_at'  => now(),
            ],
            // Company 2: Perdana Fleet
            [
                'id'          => 11,
                'name'        => 'Puan Rozita binti Azman',
                'email'       => 'rozita@perdanafleet.my',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '017-6654321',
                'company_id'  => 2,
                'avatar'      => null,
                'pic_role'    => 'primary',
                'specialties' => null,
                'staff_role'  => 'General Fleet Manager',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(16),
                'updated_at'  => now(),
            ],
            [
                'id'          => 12,
                'name'        => 'Khairul Annuar bin Salleh',
                'email'       => 'khairul@perdanafleet.my',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '012-7788112',
                'company_id'  => 2,
                'avatar'      => null,
                'pic_role'    => 'secondary',
                'specialties' => null,
                'staff_role'  => 'Fleet Supervisor',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(14),
                'updated_at'  => now(),
            ],
            // Company 3: Nusantara Express
            [
                'id'          => 13,
                'name'        => 'Encik Zulkifli bin Daud',
                'email'       => 'zulkifli@nusantaraexpress.com',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '013-9988776',
                'company_id'  => 3,
                'avatar'      => null,
                'pic_role'    => 'primary',
                'specialties' => null,
                'staff_role'  => 'Operations Manager',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(15),
                'updated_at'  => now(),
            ],
            [
                'id'          => 14,
                'name'        => 'Norlida binti Osman',
                'email'       => 'norlida@nusantaraexpress.com',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '019-8812345',
                'company_id'  => 3,
                'avatar'      => null,
                'pic_role'    => 'secondary',
                'specialties' => null,
                'staff_role'  => 'Transport Coordinator',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(12),
                'updated_at'  => now(),
            ],
            // Company 4: Southern Cold Chain
            [
                'id'          => 15,
                'name'        => 'Kevin Lee Wei Ming',
                'email'       => 'kevin@southerncoldchain.my',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '016-2244888',
                'company_id'  => 4,
                'avatar'      => null,
                'pic_role'    => 'primary',
                'specialties' => null,
                'staff_role'  => 'Logistics Director',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(12),
                'updated_at'  => now(),
            ],
            [
                'id'          => 16,
                'name'        => 'Nur Syahirah binti Azmi',
                'email'       => 'syahirah@southerncoldchain.my',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '011-55889922',
                'company_id'  => 4,
                'avatar'      => null,
                'pic_role'    => 'secondary',
                'specialties' => null,
                'staff_role'  => 'Cold Chain Dispatcher',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(10),
                'updated_at'  => now(),
            ],
            // Company 5: Mega Port Haulage
            [
                'id'          => 17,
                'name'        => 'Captain Razali bin Hamzah',
                'email'       => 'razali@megaporthaulage.com',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '019-7100222',
                'company_id'  => 5,
                'avatar'      => null,
                'pic_role'    => 'primary',
                'specialties' => null,
                'staff_role'  => 'Head of Port Transport',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(10),
                'updated_at'  => now(),
            ],
            [
                'id'          => 18,
                'name'        => 'Farhan bin Razali',
                'email'       => 'farhan@megaporthaulage.com',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '012-3499881',
                'company_id'  => 5,
                'avatar'      => null,
                'pic_role'    => 'secondary',
                'specialties' => null,
                'staff_role'  => 'Field Maintenance Lead',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(8),
                'updated_at'  => now(),
            ],
            // Company 6: Apex Swift Courier
            [
                'id'          => 19,
                'name'        => 'Michelle Wong Shu Fen',
                'email'       => 'michelle@apexswift.my',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '012-6611099',
                'company_id'  => 6,
                'avatar'      => null,
                'pic_role'    => 'primary',
                'specialties' => null,
                'staff_role'  => 'Fleet Administrator',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(8),
                'updated_at'  => now(),
            ],
            [
                'id'          => 20,
                'name'        => 'Azman bin Kassim',
                'email'       => 'azman@apexswift.my',
                'password'    => $pw,
                'role'        => 'corporate',
                'status'      => 'active',
                'contact_no'  => '017-8899001',
                'company_id'  => 6,
                'avatar'      => null,
                'pic_role'    => 'secondary',
                'specialties' => null,
                'staff_role'  => 'Depot Supervisor',
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(6),
                'updated_at'  => now(),
            ],
            // Individual Customers (21–26)
            [
                'id'          => 21,
                'name'        => 'Nurul Ain binti Zakaria',
                'email'       => 'ain@gmail.com',
                'password'    => $pw,
                'role'        => 'individual',
                'status'      => 'active',
                'contact_no'  => '011-34567890',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => null,
                'staff_role'  => null,
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(15),
                'updated_at'  => now(),
            ],
            [
                'id'          => 22,
                'name'        => 'Faizal bin Rahman',
                'email'       => 'faizal@gmail.com',
                'password'    => $pw,
                'role'        => 'individual',
                'status'      => 'active',
                'contact_no'  => '012-45678901',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => null,
                'staff_role'  => null,
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(14),
                'updated_at'  => now(),
            ],
            [
                'id'          => 23,
                'name'        => 'Khairul Nizam bin Hassan',
                'email'       => 'nizam@gmail.com',
                'password'    => $pw,
                'role'        => 'individual',
                'status'      => 'active',
                'contact_no'  => '016-78901234',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => null,
                'staff_role'  => null,
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(12),
                'updated_at'  => now(),
            ],
            [
                'id'          => 24,
                'name'        => 'Salmah binti Othman',
                'email'       => 'salmah@gmail.com',
                'password'    => $pw,
                'role'        => 'individual',
                'status'      => 'active',
                'contact_no'  => '014-22334455',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => null,
                'staff_role'  => null,
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(10),
                'updated_at'  => now(),
            ],
            [
                'id'          => 25,
                'name'        => 'Kevin Tan Chee Keong',
                'email'       => 'kevin.tan@gmail.com',
                'password'    => $pw,
                'role'        => 'individual',
                'status'      => 'active',
                'contact_no'  => '012-5599221',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => null,
                'staff_role'  => null,
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(8),
                'updated_at'  => now(),
            ],
            [
                'id'          => 26,
                'name'        => 'Kumaravel a/l Muthusamy',
                'email'       => 'kumar@gmail.com',
                'password'    => $pw,
                'role'        => 'individual',
                'status'      => 'active',
                'contact_no'  => '018-4455667',
                'company_id'  => null,
                'avatar'      => null,
                'pic_role'    => null,
                'specialties' => null,
                'staff_role'  => null,
                'permissions' => null,
                'monthly_salary' => null,
                'hire_date'   => null,
                'created_at'  => now()->subMonths(7),
                'updated_at'  => now(),
            ],
        ]);

        // ----------------------------------------------------------------
        // 3. JOB TYPES (10 Standard Workshop Services)
        // ----------------------------------------------------------------
        DB::table('job_types')->insert([
            ['id'=>1,  'name'=>'Full Engine Service',        'category'=>'Engine',       'estimated_minutes'=>180, 'base_price'=>140.00, 'interval_km'=>10000, 'interval_months'=>12, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>2,  'name'=>'Oil & Filter Change',        'category'=>'Engine',       'estimated_minutes'=>45,  'base_price'=>35.00,  'interval_km'=>5000,  'interval_months'=>6,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>3,  'name'=>'Brake Pad Replacement',      'category'=>'Brakes',       'estimated_minutes'=>90,  'base_price'=>65.00,  'interval_km'=>20000, 'interval_months'=>12, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>4,  'name'=>'Tyre Rotation & Balancing',  'category'=>'Tyres',        'estimated_minutes'=>60,  'base_price'=>40.00,  'interval_km'=>10000, 'interval_months'=>6,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>5,  'name'=>'Battery Replacement',        'category'=>'Electrical',   'estimated_minutes'=>30,  'base_price'=>30.00,  'interval_km'=>null,  'interval_months'=>24, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>6,  'name'=>'Air Conditioning Service',   'category'=>'Cooling',      'estimated_minutes'=>120, 'base_price'=>85.00,  'interval_km'=>null,  'interval_months'=>18, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>7,  'name'=>'Suspension Inspection',      'category'=>'Suspension',   'estimated_minutes'=>75,  'base_price'=>60.00,  'interval_km'=>25000, 'interval_months'=>24, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>8,  'name'=>'Transmission Fluid Change',  'category'=>'Transmission', 'estimated_minutes'=>60,  'base_price'=>70.00,  'interval_km'=>40000, 'interval_months'=>24, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>9,  'name'=>'Engine Diagnostics Scan',    'category'=>'Engine',       'estimated_minutes'=>45,  'base_price'=>50.00,  'interval_km'=>null,  'interval_months'=>null,'created_at'=>now(),'updated_at'=>now()],
            ['id'=>10, 'name'=>'Full Vehicle Inspection',    'category'=>'General',      'estimated_minutes'=>150, 'base_price'=>110.00, 'interval_km'=>30000, 'interval_months'=>12, 'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ----------------------------------------------------------------
        // 4. VEHICLES (34 Vehicles: 24 Fleet, 8 Individual, 2 Walk-in)
        // ----------------------------------------------------------------
        DB::table('vehicles')->insert([
            // Mulia Logistik (Co 1, user 9 & 10)
            ['id'=>1,  'user_id'=>9,  'plate_number'=>'JQP 1122', 'brand'=>'Isuzu',      'model'=>'D-Max 3.0 4x4',     'year'=>2020, 'mileage'=>88400,  'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(18),'updated_at'=>now()],
            ['id'=>2,  'user_id'=>9,  'plate_number'=>'JQP 1133', 'brand'=>'Isuzu',      'model'=>'D-Max 1.9 BluePower','year'=>2021, 'mileage'=>64800,  'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(16),'updated_at'=>now()],
            ['id'=>3,  'user_id'=>9,  'plate_number'=>'JKL 4456', 'brand'=>'Toyota',     'model'=>'Hilux 2.8 GR',      'year'=>2019, 'mileage'=>114200, 'color'=>'Silver',      'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(18),'updated_at'=>now()],
            ['id'=>4,  'user_id'=>10, 'plate_number'=>'JDE 7890', 'brand'=>'Nissan',     'model'=>'Navara Pro-4X',     'year'=>2022, 'mileage'=>43600,  'color'=>'Black',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(14),'updated_at'=>now()],
            // Perdana Fleet Services (Co 2, user 11 & 12)
            ['id'=>5,  'user_id'=>11, 'plate_number'=>'WXY 2211', 'brand'=>'Toyota',     'model'=>'Vios 1.5G',         'year'=>2021, 'mileage'=>56200,  'color'=>'White',       'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(16),'updated_at'=>now()],
            ['id'=>6,  'user_id'=>11, 'plate_number'=>'WXY 2222', 'brand'=>'Toyota',     'model'=>'Vios 1.5G',         'year'=>2021, 'mileage'=>59100,  'color'=>'White',       'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(16),'updated_at'=>now()],
            ['id'=>7,  'user_id'=>11, 'plate_number'=>'WPQ 8833', 'brand'=>'Honda',      'model'=>'City 1.5V',         'year'=>2020, 'mileage'=>73400,  'color'=>'Modern Steel','fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(15),'updated_at'=>now()],
            ['id'=>8,  'user_id'=>12, 'plate_number'=>'VBD 4910', 'brand'=>'Toyota',     'model'=>'Corolla Altis 1.8G','year'=>2022, 'mileage'=>38500,  'color'=>'Silver',      'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(12),'updated_at'=>now()],
            // Nusantara Express (Co 3, user 13 & 14)
            ['id'=>9,  'user_id'=>13, 'plate_number'=>'KB 1234 A','brand'=>'Mercedes',   'model'=>'Sprinter 516 CDI',  'year'=>2019, 'mileage'=>136800, 'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(15),'updated_at'=>now()],
            ['id'=>10, 'user_id'=>13, 'plate_number'=>'KB 5678 B','brand'=>'Hino',       'model'=>'300 Series Lorry',  'year'=>2020, 'mileage'=>99500,  'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(14),'updated_at'=>now()],
            ['id'=>11, 'user_id'=>14, 'plate_number'=>'DDA 3390', 'brand'=>'Isuzu',      'model'=>'ELF 3.5 Ton Lorry', 'year'=>2019, 'mileage'=>152000, 'color'=>'Blue',        'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(14),'updated_at'=>now()],
            ['id'=>12, 'user_id'=>14, 'plate_number'=>'DDF 7712', 'brand'=>'Ford',       'model'=>'Transit 2.2 Turbo', 'year'=>2021, 'mileage'=>81300,  'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(11),'updated_at'=>now()],
            // Southern Cold Chain Logistics (Co 4, user 15 & 16)
            ['id'=>13, 'user_id'=>15, 'plate_number'=>'JTG 3301', 'brand'=>'Isuzu',      'model'=>'Forward 5-Ton Freezer','year'=>2018,'mileage'=>184000,'color'=>'White',      'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(12),'updated_at'=>now()],
            ['id'=>14, 'user_id'=>15, 'plate_number'=>'JTG 3302', 'brand'=>'Mitsubishi', 'model'=>'Canter Chiller Truck','year'=>2020,'mileage'=>108500,'color'=>'White',      'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(12),'updated_at'=>now()],
            ['id'=>15, 'user_id'=>16, 'plate_number'=>'JVD 8900', 'brand'=>'Toyota',     'model'=>'Hiace Freezer Van', 'year'=>2021, 'mileage'=>74200,  'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(10),'updated_at'=>now()],
            ['id'=>16, 'user_id'=>16, 'plate_number'=>'JVF 6120', 'brand'=>'Isuzu',      'model'=>'D-Max Cold Box',    'year'=>2022, 'mileage'=>49800,  'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(9), 'updated_at'=>now()],
            // Mega Port Haulage & Marine (Co 5, user 17 & 18)
            ['id'=>17, 'user_id'=>17, 'plate_number'=>'JPU 9001', 'brand'=>'Ford',       'model'=>'Ranger Escort 2.0', 'year'=>2020, 'mileage'=>91400,  'color'=>'Orange',      'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(10),'updated_at'=>now()],
            ['id'=>18, 'user_id'=>17, 'plate_number'=>'JPU 9002', 'brand'=>'Toyota',     'model'=>'Hilux Rogue 2.8',   'year'=>2021, 'mileage'=>83000,  'color'=>'Dark Grey',   'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(9), 'updated_at'=>now()],
            ['id'=>19, 'user_id'=>18, 'plate_number'=>'JQK 5510', 'brand'=>'Isuzu',      'model'=>'Giga Prime Mover',  'year'=>2018, 'mileage'=>245000, 'color'=>'Red',         'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(10),'updated_at'=>now()],
            ['id'=>20, 'user_id'=>18, 'plate_number'=>'JQK 5520', 'brand'=>'Mitsubishi', 'model'=>'Triton VGT Adv',    'year'=>2021, 'mileage'=>69500,  'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(8), 'updated_at'=>now()],
            // Apex Swift Courier (Co 6, user 19 & 20)
            ['id'=>21, 'user_id'=>19, 'plate_number'=>'JWS 4101', 'brand'=>'Toyota',     'model'=>'Hiace Panel Van',   'year'=>2020, 'mileage'=>118000, 'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(8), 'updated_at'=>now()],
            ['id'=>22, 'user_id'=>19, 'plate_number'=>'JWS 4102', 'brand'=>'Toyota',     'model'=>'Hiace Panel Van',   'year'=>2021, 'mileage'=>94200,  'color'=>'White',       'fuel_type'=>'Diesel', 'created_at'=>now()->subMonths(7), 'updated_at'=>now()],
            ['id'=>23, 'user_id'=>20, 'plate_number'=>'JWT 8812', 'brand'=>'Daihatsu',   'model'=>'Gran Max 1.5 Panel','year'=>2022, 'mileage'=>52000,  'color'=>'Silver',      'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(6), 'updated_at'=>now()],
            ['id'=>24, 'user_id'=>20, 'plate_number'=>'JWV 2033', 'brand'=>'Nissan',     'model'=>'NV200 Van',         'year'=>2020, 'mileage'=>76400,  'color'=>'White',       'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(6), 'updated_at'=>now()],
            // Individual Customers (user 21–26)
            ['id'=>25, 'user_id'=>21, 'plate_number'=>'VKK 3456', 'brand'=>'Perodua',    'model'=>'Myvi 1.5 AV',       'year'=>2022, 'mileage'=>29400,  'color'=>'Cranberry Red','fuel_type'=>'Petrol','created_at'=>now()->subMonths(15),'updated_at'=>now()],
            ['id'=>26, 'user_id'=>21, 'plate_number'=>'VBB 7712', 'brand'=>'Proton',     'model'=>'X50 1.5 TGDi',      'year'=>2023, 'mileage'=>13800,  'color'=>'Ocean Blue',  'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(10),'updated_at'=>now()],
            ['id'=>27, 'user_id'=>22, 'plate_number'=>'PBD 9921', 'brand'=>'Proton',     'model'=>'Saga 1.3 Premium',  'year'=>2018, 'mileage'=>96200,  'color'=>'Ruby Red',    'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(14),'updated_at'=>now()],
            ['id'=>28, 'user_id'=>23, 'plate_number'=>'BCG 4411', 'brand'=>'Honda',      'model'=>'Civic 1.5 VTEC Turbo','year'=>2021,'mileage'=>42300,  'color'=>'Lunar Silver', 'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(12),'updated_at'=>now()],
            ['id'=>29, 'user_id'=>24, 'plate_number'=>'JTF 5566', 'brand'=>'Perodua',    'model'=>'Bezza 1.3X',        'year'=>2019, 'mileage'=>79800,  'color'=>'Ivory White', 'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(10),'updated_at'=>now()],
            ['id'=>30, 'user_id'=>25, 'plate_number'=>'VHA 8192', 'brand'=>'Toyota',     'model'=>'Corolla Cross Hybrid','year'=>2022,'mileage'=>34100,  'color'=>'Platinum White','fuel_type'=>'Hybrid','created_at'=>now()->subMonths(8), 'updated_at'=>now()],
            ['id'=>31, 'user_id'=>26, 'plate_number'=>'JUR 1988', 'brand'=>'Proton',     'model'=>'X70 1.8 TGDi',      'year'=>2020, 'mileage'=>68500,  'color'=>'Snow White',  'fuel_type'=>'Petrol', 'created_at'=>now()->subMonths(7), 'updated_at'=>now()],
            ['id'=>32, 'user_id'=>22, 'plate_number'=>'WVN 6023', 'brand'=>'Mazda',      'model'=>'CX-5 2.0 High',     'year'=>2021, 'mileage'=>48900,  'color'=>'Soul Red Crystal','fuel_type'=>'Petrol','created_at'=>now()->subMonths(9),'updated_at'=>now()],
            // Walk-in Customers (user_id = null)
            ['id'=>33, 'user_id'=>null,'plate_number'=>'SAB 6677', 'brand'=>'Toyota',     'model'=>'Fortuner 2.8 VRZ',  'year'=>2020, 'mileage'=>62500,  'color'=>'Phantom Brown','fuel_type'=>'Diesel','created_at'=>now()->subMonths(6), 'updated_at'=>now()],
            ['id'=>34, 'user_id'=>null,'plate_number'=>'BNX 3310', 'brand'=>'Perodua',    'model'=>'Alza 1.5 AV',       'year'=>2022, 'mileage'=>31000,  'color'=>'Elegant Black','fuel_type'=>'Petrol','created_at'=>now()->subMonths(4), 'updated_at'=>now()],
        ]);

        // ----------------------------------------------------------------
        // 5. SPARE PARTS (25 Items across all workshop categories)
        // 4 items intentionally at or below min_stock to showcase low-stock alerts
        // ----------------------------------------------------------------
        DB::table('spare_parts')->insert([
            ['id'=>1,  'name'=>'Engine Oil 5W-30 Synthetic (4L)', 'brand'=>'Castrol',       'part_number'=>'CO-5W30-4L',  'category'=>'Lubricants',   'unit_price'=>68.00,  'stock'=>52, 'min_stock'=>12, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>2,  'name'=>'Engine Oil Filter (Standard)',     'brand'=>'Denso',         'part_number'=>'DN-OF-001',   'category'=>'Filters',      'unit_price'=>19.50,  'stock'=>38, 'min_stock'=>10, 'created_at'=>now(),'updated_at'=>now()],
            ['id'=>3,  'name'=>'Air Filter Element (OEM)',         'brand'=>'Bosch',         'part_number'=>'BO-AF-221',   'category'=>'Filters',      'unit_price'=>55.00,  'stock'=>24, 'min_stock'=>6,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>4,  'name'=>'Brake Pad Set (Front)',            'brand'=>'Brembo',        'part_number'=>'BR-FP-44A',   'category'=>'Brakes',       'unit_price'=>185.00, 'stock'=>16, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>5,  'name'=>'Brake Pad Set (Rear)',             'brand'=>'Brembo',        'part_number'=>'BR-RP-44B',   'category'=>'Brakes',       'unit_price'=>145.00, 'stock'=>14, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>6,  'name'=>'Spark Plug Iridium (Set of 4)',    'brand'=>'NGK',           'part_number'=>'NGK-IX-79',   'category'=>'Engine',       'unit_price'=>78.00,  'stock'=>26, 'min_stock'=>8,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>7,  'name'=>'Car Battery 65D26L Maintenance-Free','brand'=>'Amaron',      'part_number'=>'AM-65D26L',   'category'=>'Electrical',   'unit_price'=>320.00, 'stock'=>9,  'min_stock'=>3,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>8,  'name'=>'Radiator Long-Life Coolant (1L)',  'brand'=>'Prestone',      'part_number'=>'PR-RC-1L',    'category'=>'Cooling',      'unit_price'=>22.00,  'stock'=>34, 'min_stock'=>8,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>9,  'name'=>'Transmission Fluid ATF WS (1L)',   'brand'=>'Toyota Genuine','part_number'=>'TY-ATF-WS',   'category'=>'Transmission', 'unit_price'=>39.00,  'stock'=>22, 'min_stock'=>6,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>10, 'name'=>'Serpentine Drive Belt 6PK',        'brand'=>'Gates',         'part_number'=>'GT-SB-6PK',   'category'=>'Engine',       'unit_price'=>95.00,  'stock'=>3,  'min_stock'=>6,  'created_at'=>now(),'updated_at'=>now()], // LOW STOCK
            ['id'=>11, 'name'=>'Power Steering Fluid (1L)',        'brand'=>'Petronas',      'part_number'=>'PT-PSF-1L',   'category'=>'Steering',     'unit_price'=>26.00,  'stock'=>19, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>12, 'name'=>'Wiper Blade Pair 22"/18"',         'brand'=>'Bosch Aerotwin','part_number'=>'BO-WB-22',    'category'=>'Accessories',  'unit_price'=>45.00,  'stock'=>2,  'min_stock'=>6,  'created_at'=>now(),'updated_at'=>now()], // LOW STOCK
            ['id'=>13, 'name'=>'Cabin Pollen Air Filter',          'brand'=>'Mann Filter',   'part_number'=>'MN-CAF-CU',   'category'=>'Filters',      'unit_price'=>42.00,  'stock'=>18, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>14, 'name'=>'Front Shock Absorber Pair',        'brand'=>'Monroe',        'part_number'=>'MR-SA-FP01',  'category'=>'Suspension',   'unit_price'=>480.00, 'stock'=>2,  'min_stock'=>4,  'created_at'=>now(),'updated_at'=>now()], // LOW STOCK
            ['id'=>15, 'name'=>'Timing Belt Kit with Tensioner',    'brand'=>'Dayco',         'part_number'=>'DY-TBK-001',  'category'=>'Engine',       'unit_price'=>260.00, 'stock'=>7,  'min_stock'=>3,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>16, 'name'=>'Heavy-Duty Diesel Oil 15W-40 (5L)','brand'=>'Shell Rimula',  'part_number'=>'SH-15W40-5L', 'category'=>'Lubricants',   'unit_price'=>85.00,  'stock'=>30, 'min_stock'=>8,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>17, 'name'=>'Commercial Truck Battery N100',    'brand'=>'Century',       'part_number'=>'CY-N100',     'category'=>'Electrical',   'unit_price'=>450.00, 'stock'=>5,  'min_stock'=>2,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>18, 'name'=>'Brake Disc Rotor Front Pair',      'brand'=>'Brembo',        'part_number'=>'BR-DR-01',    'category'=>'Brakes',       'unit_price'=>310.00, 'stock'=>8,  'min_stock'=>3,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>19, 'name'=>'Diesel Fuel Filter Element',       'brand'=>'Denso',         'part_number'=>'DN-FF-04',    'category'=>'Filters',      'unit_price'=>48.00,  'stock'=>22, 'min_stock'=>6,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>20, 'name'=>'Air Conditioning Compressor Unit', 'brand'=>'Denso OEM',     'part_number'=>'DN-AC-99',    'category'=>'Cooling',      'unit_price'=>680.00, 'stock'=>3,  'min_stock'=>2,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>21, 'name'=>'Refrigerant R134a (13.6kg Cylinder)','brand'=>'Chemours',    'part_number'=>'CH-R134A',    'category'=>'Cooling',      'unit_price'=>290.00, 'stock'=>5,  'min_stock'=>2,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>22, 'name'=>'Heavy Gear Oil 80W-90 (1L)',       'brand'=>'Castrol',       'part_number'=>'CS-GO-8090',  'category'=>'Transmission', 'unit_price'=>32.00,  'stock'=>16, 'min_stock'=>5,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>23, 'name'=>'Wheel Bearing Hub Assembly',       'brand'=>'SKF',           'part_number'=>'SK-WB-11',    'category'=>'Suspension',   'unit_price'=>210.00, 'stock'=>7,  'min_stock'=>3,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>24, 'name'=>'DOT 4 High Performance Brake Fluid','brand'=>'Bosch',        'part_number'=>'BO-BF-DOT4',  'category'=>'Brakes',       'unit_price'=>24.00,  'stock'=>32, 'min_stock'=>8,  'created_at'=>now(),'updated_at'=>now()],
            ['id'=>25, 'name'=>'Halogen / LED Headlight Bulb H4',  'brand'=>'Philips',       'part_number'=>'PH-LED-H4',   'category'=>'Electrical',   'unit_price'=>110.00, 'stock'=>12, 'min_stock'=>4,  'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ----------------------------------------------------------------
        // 6. APPOINTMENTS, JOB CARDS, PARTS, LABOUR & SERVICE HISTORIES
        // Comprehensive multi-month history across the fleet:
        // - 44 Completed Historical Jobs (creating multi-visit chains for vehicles)
        // - 8 Active Jobs currently in the workshop across stages
        // - 6 Upcoming / Pending Appointments
        // - 2 Cancelled
        // ----------------------------------------------------------------

        // Master historical specification:
        // [appt_id, vehicle_id, user_id, is_walkin, walkin_name, walkin_contact, days_ago, job_type_id, staff_id, total_cost, diagnosis, symptoms, tech_notes, parts_used [part_id, qty, price], labour [desc, charge]]
        $historicalSpecs = [
            // Vehicle 1: Isuzu D-Max (Mulia) - 3 service chain
            [1, 1, 9, false, null, null, 165, 1, 2, 235.50, 'Annual full engine service and fluid exchange.', ['Oil leak'], 'Flushed old oil, replaced filter, checked powertrain mounts.', [[1,1,68.00],[2,1,19.50],[3,1,55.00]], [['Full Engine Service Labour',93.00]]],
            [2, 1, 9, false, null, null, 95, 2, 2, 122.50, 'Scheduled 5,000 km oil & filter change.', [], 'Castrol 5W-30 filled. Filter renewed. Tyre pressure adjusted.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],
            [3, 1, 9, false, null, null, 65, 3, 3, 250.00, 'Front brake pads worn to 2mm indicator.', ['Squeaking / grinding brakes'], 'Front Brembo brake pads installed. Rotors deglazed.', [[4,1,185.00]], [['Brake Replacement Labour',65.00]]],

            // Vehicle 2: Isuzu D-Max (Mulia) - 3 service chain
            [4, 2, 9, false, null, null, 170, 2, 6, 122.50, 'Regular periodic oil change.', [], 'Oil and filter replaced. Engine bay degreased.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],
            [5, 2, 9, false, null, null, 105, 4, 3, 40.00,  'Tread wear inspection and tyre rotation.', ['Unusual tyre wear'], 'Front to rear cross rotation completed. Dynamic balancing ok.', [], [['Tyre Rotation & Balancing',40.00]]],
            [6, 2, 9, false, null, null, 18,  2, 6, 122.50, 'Fleet preventative oil change.', [], 'Engine oil refreshed, drain plug washer replaced.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],

            // Vehicle 3: Toyota Hilux (Mulia) - 2 service chain
            [7, 3, 9, false, null, null, 102, 1, 2, 320.50, 'Major 100,000 km engine service.', ['Unusual engine noise'], 'Replaced oil, fuel filter, air filter and serpentine drive belt.', [[16,1,85.00],[19,1,48.00],[10,1,95.00]], [['Heavy Engine Service Labour',92.50]]],
            [8, 3, 9, false, null, null, 14, 7, 4, 540.00, 'Front suspension vibration on rough logging site roads.', ['Suspension noise'], 'Replaced front Monroe shock absorbers. Bushings re-torqued.', [[14,1,480.00]], [['Suspension Labour',60.00]]],

            // Vehicle 4: Nissan Navara (Mulia)
            [9, 4, 10, false, null, null, 42, 2, 6, 122.50, 'Supervisory pickup regular maintenance.', [], 'Engine oil and Denso filter renewed.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],

            // Vehicle 5: Toyota Vios (Perdana Fleet) - 3 service chain
            [10, 5, 11, false, null, null, 160, 2, 2, 122.50, 'Routine sales fleet oil service.', [], 'Replaced oil and filter.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],
            [11, 5, 11, false, null, null, 110, 4, 3, 40.00,  'Tyre rotation and wheel balancing at 50,000 km.', [], 'Four-wheel rotation and balancing completed.', [], [['Tyre Rotation & Balancing',40.00]]],
            [12, 5, 11, false, null, null, 12,  2, 6, 122.50, 'Routine oil service.', [], 'Full synthetic Castrol 5W-30 replaced.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],

            // Vehicle 6: Toyota Vios (Perdana Fleet)
            [13, 6, 11, false, null, null, 38, 2, 2, 122.50, 'Executive company car service.', [], 'Lubricant service performed.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],

            // Vehicle 7: Honda City (Perdana Fleet) - 2 service chain
            [14, 7, 11, false, null, null, 125, 1, 5, 235.50, 'Full engine tune up and inspection.', ['Engine light on'], 'Cleared minor sensor code. Air and oil filters replaced.', [[1,1,68.00],[2,1,19.50],[3,1,55.00]], [['Full Service Labour',93.00]]],
            [15, 7, 11, false, null, null, 70, 8, 4, 187.00, 'Transmission ATF fluid change.', ['Gear shifting rough'], 'Toyota ATF WS flushed and renewed.', [[9,3,117.00]], [['Transmission Service Labour',70.00]]],

            // Vehicle 9: Mercedes Sprinter (Nusantara Express) - 3 service chain
            [16, 9, 13, false, null, null, 172, 1, 2, 345.00, 'Heavy interstate delivery van full service.', ['Excessive exhaust smoke'], 'Fuel filter, heavy duty oil, and air filter element renewed.', [[16,2,170.00],[19,1,48.00]], [['Commercial Van Service Labour',127.00]]],
            [17, 9, 13, false, null, null, 92, 3, 3, 210.00, 'Rear brake pad renewal.', ['Squeaking brakes'], 'Brembo rear pads fitted.', [[5,1,145.00]], [['Brake Replacement Labour',65.00]]],
            [18, 9, 13, false, null, null, 22, 9, 5, 128.00, 'OBD diagnostic scan for turbo boost limp mode.', ['Engine light on','Lack of power'], 'Wastegate actuator cleaned and calibrated. ECU test ok.', [], [['Diagnostic Scan & Calibration',128.00]]],

            // Vehicle 10: Hino 300 Lorry (Nusantara Express) - 2 service chain
            [19, 10, 13, false, null, null, 135, 2, 6, 150.00, '3-Ton commercial truck oil service.', [], 'Shell Rimula 15W-40 heavy diesel oil filled.', [[16,1,85.00],[2,1,19.50]], [['Commercial Oil Labour',45.50]]],
            [20, 10, 13, false, null, null, 31, 3, 3, 250.00, 'Front brake pads worn from heavy cargo braking.', ['Brake spongy'], 'Heavy duty front pads replaced.', [[4,1,185.00]], [['Brake Labour',65.00]]],

            // Vehicle 11: Isuzu ELF (Nusantara Express)
            [21, 11, 14, false, null, null, 46, 5, 4, 480.00, 'Commercial battery dead after overnight layover.', ['Starter motor issue'], 'Installed Century N100 commercial truck battery.', [[17,1,450.00]], [['Battery Installation Labour',30.00]]],

            // Vehicle 13: Isuzu Forward Freezer (Southern Cold Chain) - 3 service chain
            [22, 13, 15, false, null, null, 140, 1, 2, 380.00, 'Freezer truck major preventative service.', [], 'Engine oil, coolant flush, fuel and air filters replaced.', [[16,2,170.00],[8,2,44.00],[19,1,48.00]], [['Freezer Fleet Service Labour',118.00]]],
            [23, 13, 15, false, null, null, 20, 6, 3, 460.00, 'Refrigeration unit compressor top-up and cab AC service.', ['AC not cold'], 'Refrigerant R134a topped up and compressor oil renewed.', [[21,1,290.00]], [['Chiller & AC Labour',170.00]]],
            [24, 13, 15, false, null, null, 9, 8, 4, 198.00, 'Heavy differential and transmission gear oil service.', [], 'Differential gear oil and transmission fluid renewed.', [[22,4,128.00]], [['Gear Oil Flush Labour',70.00]]],

            // Vehicle 14: Fuso Canter Chiller (Southern Cold Chain) - 2 service chain
            [25, 14, 15, false, null, null, 100, 2, 6, 150.00, 'Cold chain local delivery truck oil service.', [], 'Engine oil and filter replaced.', [[16,1,85.00],[2,1,19.50]], [['Oil Labour',45.50]]],
            [26, 14, 15, false, null, null, 78, 3, 3, 250.00, 'Brake overhaul before interstate distribution.', ['Squeaking brakes'], 'Front Brembo brake pads installed.', [[4,1,185.00]], [['Brake Labour',65.00]]],

            // Vehicle 15: Toyota Hiace Freezer (Southern Cold Chain)
            [27, 15, 16, false, null, null, 34, 2, 2, 122.50, 'Hiace refrigerated van oil change.', [], 'Synthetic oil renewed.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],

            // Vehicle 17: Ford Ranger Escort (Mega Port Haulage) - 3 service chain
            [28, 17, 17, false, null, null, 130, 2, 6, 122.50, 'Port terminal escort vehicle routine service.', [], 'Engine oil and filter replaced.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],
            [29, 17, 17, false, null, null, 82, 7, 4, 540.00, 'Suspension check after heavy container yard driving.', ['Vibration at high speed'], 'Shock absorber replacement and wheel alignment.', [[14,1,480.00]], [['Suspension Labour',60.00]]],
            [30, 17, 17, false, null, null, 5,  1, 2, 235.50, 'Major 90,000 km full engine service.', [], 'Castrol oil, oil filter, air filter element replaced.', [[1,1,68.00],[2,1,19.50],[3,1,55.00]], [['Full Service Labour',93.00]]],

            // Vehicle 18: Toyota Hilux Rogue (Mega Port Haulage)
            [31, 18, 17, false, null, null, 112, 2, 2, 122.50, 'Site manager vehicle oil service.', [], 'Castrol 5W-30 synthetic oil change.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],

            // Vehicle 19: Isuzu Giga Prime Mover (Mega Port Haulage)
            [32, 19, 18, false, null, null, 40, 1, 2, 450.00, 'Heavy 240,000 km prime mover engine overhaul check.', ['Heavy oil consumption'], 'Heavy duty diesel oil 15W-40, primary and secondary filters renewed.', [[16,3,255.00],[19,2,96.00]], [['Prime Mover Service Labour',99.00]]],

            // Vehicle 21: Toyota Hiace Courier (Apex Swift) - 3 service chain
            [33, 21, 19, false, null, null, 142, 1, 2, 235.50, 'High-mileage courier van full service.', [], 'Oil, filter, and engine tune up completed.', [[1,1,68.00],[2,1,19.50],[3,1,55.00]], [['Full Service Labour',93.00]]],
            [34, 21, 19, false, null, null, 108, 2, 6, 122.50, '10,000 km courier oil refresh.', [], 'Quick lube service completed.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],
            [35, 21, 19, false, null, null, 11,  3, 3, 250.00, 'Courier multi-drop braking wear.', ['Brake squeal'], 'Replaced front Brembo brake pads.', [[4,1,185.00]], [['Brake Labour',65.00]]],

            // Vehicle 22: Toyota Hiace Courier (Apex Swift)
            [36, 22, 19, false, null, null, 50, 2, 6, 122.50, 'Routine oil service.', [], 'Castrol 5W-30 oil and filter.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],

            // Vehicle 25: Perodua Myvi (Ain - Individual) - 2 service chain
            [37, 25, 21, false, null, null, 155, 2, 6, 122.50, '20,000 km major service.', [], 'Engine oil and Denso filter renewed.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],
            [38, 25, 21, false, null, null, 62, 2, 6, 164.50, 'Routine service with cabin air filter replacement.', [], 'Oil, filter, and Mann cabin pollen filter installed.', [[1,1,68.00],[2,1,19.50],[13,1,42.00]], [['Oil & Cabin Labour',35.00]]],

            // Vehicle 27: Proton Saga (Faizal - Individual) - 3 service chain
            [39, 27, 22, false, null, null, 138, 1, 2, 235.50, 'Full engine service.', ['Engine stalling or misfiring'], 'Castrol 5W-30 oil, oil filter, air filter renewed.', [[1,1,68.00],[2,1,19.50],[3,1,55.00]], [['Full Service Labour',93.00]]],
            [40, 27, 22, false, null, null, 74, 5, 3, 350.00, 'Battery died at shopping mall.', ['Starter motor issue'], 'Installed Amaron 65D26L maintenance-free battery.', [[7,1,320.00]], [['Battery Labour',30.00]]],
            [41, 27, 22, false, null, null, 16,  3, 3, 210.00, 'Rear brake grinding.', ['Grinding noise'], 'Rear brake pads replaced.', [[5,1,145.00]], [['Brake Labour',65.00]]],

            // Vehicle 28: Honda Civic (Nizam - Individual) - 2 service chain
            [42, 28, 23, false, null, null, 115, 2, 6, 122.50, 'Civic Turbo synthetic oil service.', [], 'Castrol 5W-30 synthetic and filter replaced.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],
            [43, 28, 23, false, null, null, 28,  6, 3, 375.00, 'AC blowing lukewarm air on hot afternoons.', ['AC warm'], 'Flushed refrigerant, vacuum leak test ok, recharged R134a.', [[21,1,290.00]], [['Aircon Service Labour',85.00]]],

            // Vehicle 30: Toyota Corolla Cross (Kevin Tan - Individual)
            [44, 30, 25, false, null, null, 3, 2, 6, 122.50, 'Hybrid engine oil service.', [], '0W-20/5W-30 compatible synthetic oil service.', [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]],
        ];

        // Seed Appointments & Completed Job Cards from specification
        $jobCardPartsRows = [];
        $labourChargeRows = [];
        $serviceHistoryRows = [];

        foreach ($historicalSpecs as $spec) {
            [$id, $vehId, $usrId, $isWalkin, $walkName, $walkContact, $daysAgo, $jtId, $staffId, $totCost, $diag, $symps, $notes, $parts, $labours] = $spec;

            $date = Carbon::now()->subDays($daysAgo);

            // Appointment
            DB::table('appointments')->insert([
                'id'             => $id,
                'user_id'        => $usrId,
                'vehicle_id'     => $vehId,
                'date'           => $date->toDateString(),
                'time'           => '09:00',
                'service_type'   => DB::table('job_types')->where('id', $jtId)->value('name'),
                'status'         => 'completed',
                'is_walkin'      => $isWalkin,
                'walkin_name'    => $walkName,
                'walkin_contact' => $walkContact,
                'notes'          => null,
                'created_at'     => $date->copy()->subDays(1),
                'updated_at'     => $date,
            ]);

            // Job Card
            DB::table('job_cards')->insert([
                'id'                   => $id,
                'appointment_id'       => $id,
                'vehicle_id'           => $vehId,
                'staff_id'             => $staffId,
                'job_type_id'          => $jtId,
                'current_stage'        => 'completed',
                'diagnosis'            => $diag,
                'symptoms'             => json_encode($symps),
                'technician_notes'     => $notes,
                'total_cost'           => $totCost,
                'estimated_completion' => $date->copy()->addHours(2),
                'completed_at'         => $date->copy()->addHours(2),
                'created_at'           => $date,
                'updated_at'           => $date,
            ]);

            // Parts
            foreach ($parts as $p) {
                [$partId, $qty, $uPrice] = $p;
                $jobCardPartsRows[] = [
                    'job_card_id'   => $id,
                    'spare_part_id' => $partId,
                    'quantity'      => $qty,
                    'unit_price'    => $uPrice,
                    'created_at'    => $date,
                    'updated_at'    => $date,
                ];
            }

            // Labour
            foreach ($labours as $l) {
                [$lDesc, $charge] = $l;
                $labourChargeRows[] = [
                    'job_card_id' => $id,
                    'description' => $lDesc,
                    'charge'      => $charge,
                    'remark'      => null,
                    'created_at'  => $date,
                    'updated_at'  => $date,
                ];
            }

            // Service History
            $serviceHistoryRows[] = [
                'job_card_id'  => $id,
                'vehicle_id'   => $vehId,
                'service_date' => $date->toDateString(),
                'description'  => DB::table('job_types')->where('id', $jtId)->value('name'),
                'cost'         => $totCost,
                'created_at'   => $date,
                'updated_at'   => $date,
            ];
        }

        // Insert collected sub-rows
        if (!empty($jobCardPartsRows))   DB::table('job_card_parts')->insert($jobCardPartsRows);
        if (!empty($labourChargeRows))   DB::table('labour_charges')->insert($labourChargeRows);
        if (!empty($serviceHistoryRows)) DB::table('service_history')->insert($serviceHistoryRows);

        // ----------------------------------------------------------------
        // 7. ACTIVE / CURRENT WORKSHOP JOBS (8 Jobs across all stages)
        // ----------------------------------------------------------------
        $activeSpecs = [
            // Stage: received
            [
                45, 1, 9, false, null, null, 0, '10:00', 2, 2, 'received',
                null, ['Routine scheduled intake'], null, 122.50,
                [[1,1,68.00],[2,1,19.50]], [['Oil Change Labour',35.00]]
            ],
            [
                46, 33, null, true, 'Dato Sri Tan Wee Kiat', '012-7711999', 0, '10:30', 10, 6, 'received',
                'Walk-in vehicle comprehensive safety and suspension inspection.', ['Vibration at high speed'], null, 110.00,
                [], [['Full Inspection Labour',110.00]]
            ],
            // Stage: diagnosing
            [
                47, 15, 16, false, null, null, 1, '09:00', 6, 3, 'diagnosing',
                'Refrigeration unit manifold pressure testing. Low pressure suction line drop detected.', ['AC warm','Chiller failure'], 'Pressure gauge attached: 15 psi low side, 180 psi high side. Leak check underway.', 375.00,
                [[21,1,290.00]], [['AC Diagnostic Labour',85.00]]
            ],
            [
                48, 26, 21, false, null, null, 1, '11:00', 9, 5, 'diagnosing',
                'OBD-II error code P0171 (System Too Lean Bank 1). Inspecting mass airflow sensor.', ['Engine light on','Engine hesitation'], 'Live sensor stream indicates erratic MAF readings at 2500 RPM.', 50.00,
                [], [['Diagnostics Scan Labour',50.00]]
            ],
            // Stage: waiting_parts
            [
                49, 10, 13, false, null, null, 2, '08:30', 8, 4, 'waiting_parts',
                'Transmission fluid heavily burnt. Solenoid gasket pack and fluid required.', ['Gear slipping','Delayed engagement'], 'Awaiting delivery of specialized ATF WS transmission fluid and filter.', 237.00,
                [[9,4,156.00]], [['Transmission Flush Labour',81.00]]
            ],
            // Stage: repairing
            [
                50, 18, 17, false, null, null, 2, '09:30', 7, 4, 'repairing',
                'Front strut oil leaking heavily onto wheel arch. Bushing torn.', ['Knocking noise over bumps'], 'Technician Hairul currently disassembling strut assembly to install Monroe pair.', 540.00,
                [[14,1,480.00]], [['Suspension Labour',60.00]]
            ],
            [
                51, 23, 20, false, null, null, 1, '14:00', 3, 6, 'repairing',
                'Front brake pads down to backing plate. Caliper slider pins dry.', ['Squeaking / grinding brakes'], 'Apprentice Faiz installing Brembo front pads and lubricating slide pins.', 250.00,
                [[4,1,185.00]], [['Brake Replacement Labour',65.00]]
            ],
            // Stage: quality_check
            [
                52, 5, 11, false, null, null, 1, '15:30', 1, 2, 'quality_check',
                'Full engine 60,000 km service completed. Undergoing quality road test.', [], 'Fluid levels double-checked. Road test underway along Senai Highway.', 235.50,
                [[1,1,68.00],[2,1,19.50],[3,1,55.00]], [['Engine Service Labour',93.00]]
            ],
        ];

        foreach ($activeSpecs as $a) {
            [$id, $vehId, $usrId, $isWalkin, $walkName, $walkContact, $daysAgo, $time, $jtId, $staffId, $stage, $diag, $symps, $notes, $totCost, $parts, $labours] = $a;
            $dt = Carbon::now()->subDays($daysAgo);

            DB::table('appointments')->insert([
                'id'             => $id,
                'user_id'        => $usrId,
                'vehicle_id'     => $vehId,
                'date'           => $dt->toDateString(),
                'time'           => $time,
                'service_type'   => DB::table('job_types')->where('id', $jtId)->value('name'),
                'status'         => 'confirmed',
                'is_walkin'      => $isWalkin,
                'walkin_name'    => $walkName,
                'walkin_contact' => $walkContact,
                'notes'          => 'Active workshop service.',
                'created_at'     => $dt->copy()->subDays(1),
                'updated_at'     => now(),
            ]);

            DB::table('job_cards')->insert([
                'id'                   => $id,
                'appointment_id'       => $id,
                'vehicle_id'           => $vehId,
                'staff_id'             => $staffId,
                'job_type_id'          => $jtId,
                'current_stage'        => $stage,
                'diagnosis'            => $diag,
                'symptoms'             => json_encode($symps),
                'technician_notes'     => $notes,
                'total_cost'           => $totCost,
                'estimated_completion' => now()->addHours(rand(2, 6)),
                'completed_at'         => null,
                'created_at'           => $dt,
                'updated_at'           => now(),
            ]);

            foreach ($parts as $p) {
                DB::table('job_card_parts')->insert([
                    'job_card_id'   => $id,
                    'spare_part_id' => $p[0],
                    'quantity'      => $p[1],
                    'unit_price'    => $p[2],
                    'created_at'    => $dt,
                    'updated_at'    => now(),
                ]);
            }

            foreach ($labours as $l) {
                DB::table('labour_charges')->insert([
                    'job_card_id' => $id,
                    'description' => $l[0],
                    'charge'      => $l[1],
                    'remark'      => null,
                    'created_at'  => $dt,
                    'updated_at'  => now(),
                ]);
            }
        }

        // ----------------------------------------------------------------
        // 8. UPCOMING & CANCELLED APPOINTMENTS (Demo booking queue)
        // ----------------------------------------------------------------
        DB::table('appointments')->insert([
            // Upcoming Pending (waiting admin confirmation)
            ['id'=>53, 'user_id'=>9,  'vehicle_id'=>3,  'date'=>Carbon::now()->addDays(2)->toDateString(), 'time'=>'09:00', 'service_type'=>'Oil & Filter Change',     'status'=>'pending',   'is_walkin'=>false, 'walkin_name'=>null, 'walkin_contact'=>null, 'notes'=>'Fleet routine service.', 'created_at'=>now(), 'updated_at'=>now()],
            ['id'=>54, 'user_id'=>11, 'vehicle_id'=>7,  'date'=>Carbon::now()->addDays(3)->toDateString(), 'time'=>'10:30', 'service_type'=>'Brake Pad Replacement',   'status'=>'pending',   'is_walkin'=>false, 'walkin_name'=>null, 'walkin_contact'=>null, 'notes'=>null,                   'created_at'=>now(), 'updated_at'=>now()],
            ['id'=>55, 'user_id'=>13, 'vehicle_id'=>11, 'date'=>Carbon::now()->addDays(3)->toDateString(), 'time'=>'14:00', 'service_type'=>'Full Vehicle Inspection', 'status'=>'pending',   'is_walkin'=>false, 'walkin_name'=>null, 'walkin_contact'=>null, 'notes'=>'PUSPAKOM prep check.', 'created_at'=>now(), 'updated_at'=>now()],
            ['id'=>56, 'user_id'=>15, 'vehicle_id'=>13, 'date'=>Carbon::now()->addDays(4)->toDateString(), 'time'=>'08:30', 'service_type'=>'Full Engine Service',     'status'=>'pending',   'is_walkin'=>false, 'walkin_name'=>null, 'walkin_contact'=>null, 'notes'=>'Heavy freezer lorry.',  'created_at'=>now(), 'updated_at'=>now()],
            ['id'=>57, 'user_id'=>21, 'vehicle_id'=>25, 'date'=>Carbon::now()->addDays(5)->toDateString(), 'time'=>'11:00', 'service_type'=>'Tyre Rotation & Balancing','status'=>'pending',   'is_walkin'=>false, 'walkin_name'=>null, 'walkin_contact'=>null, 'notes'=>null,                   'created_at'=>now(), 'updated_at'=>now()],
            // Cancelled Appointments
            ['id'=>58, 'user_id'=>22, 'vehicle_id'=>27, 'date'=>Carbon::now()->subDays(6)->toDateString(), 'time'=>'15:00', 'service_type'=>'Battery Replacement',     'status'=>'cancelled', 'is_walkin'=>false, 'walkin_name'=>null, 'walkin_contact'=>null, 'notes'=>'Customer cancelled.',  'created_at'=>now()->subDays(7), 'updated_at'=>now()->subDays(6)],
            ['id'=>59, 'user_id'=>23, 'vehicle_id'=>28, 'date'=>Carbon::now()->subDays(4)->toDateString(), 'time'=>'09:30', 'service_type'=>'Air Conditioning Service','status'=>'cancelled', 'is_walkin'=>false, 'walkin_name'=>null, 'walkin_contact'=>null, 'notes'=>'Rescheduled.',          'created_at'=>now()->subDays(5), 'updated_at'=>now()->subDays(4)],
        ]);

        // ----------------------------------------------------------------
        // 9. TRIP LOGS (100+ Trips for accurate daily km rate learning)
        // Feeds MaintenancePredictionService's usage rate estimator with high statistical confidence.
        // ----------------------------------------------------------------
        $tripLogs = [];
        // Heavy commercial fleet vehicles: 60 - 180 km/day
        $heavyVehicles = [1, 2, 3, 4, 9, 10, 11, 13, 14, 15, 17, 18, 21, 22];
        foreach ($heavyVehicles as $vId) {
            for ($d = 30; $d >= 1; $d -= rand(2, 4)) {
                $km = rand(70, 195);
                $terrain = match ($vId % 3) {
                    0 => 'highway',
                    1 => 'mixed',
                    default => 'urban',
                };
                $tripLogs[] = [
                    'vehicle_id'   => $vId,
                    'trip_date'    => Carbon::now()->subDays($d)->toDateString(),
                    'distance_km'  => $km,
                    'terrain_type' => $terrain,
                    'notes'        => 'Logistics freight & depot run (' . $km . ' km)',
                    'created_at'   => Carbon::now()->subDays($d),
                    'updated_at'   => Carbon::now()->subDays($d),
                ];
            }
        }

        // Passenger cars & individual commuters: 20 - 65 km/day
        $commuterVehicles = [5, 6, 7, 8, 25, 26, 27, 28, 29, 30, 31];
        foreach ($commuterVehicles as $vId) {
            for ($d = 28; $d >= 2; $d -= rand(3, 5)) {
                $km = rand(22, 60);
                $tripLogs[] = [
                    'vehicle_id'   => $vId,
                    'trip_date'    => Carbon::now()->subDays($d)->toDateString(),
                    'distance_km'  => $km,
                    'terrain_type' => 'urban',
                    'notes'        => 'Daily commuting and corporate transit (' . $km . ' km)',
                    'created_at'   => Carbon::now()->subDays($d),
                    'updated_at'   => Carbon::now()->subDays($d),
                ];
            }
        }
        DB::table('trip_logs')->insert($tripLogs);

        // ----------------------------------------------------------------
        // 10. JOB FEEDBACKS (20 Ratings to populate Staff Performance report)
        // ----------------------------------------------------------------
        $feedbacks = [
            [1, 5, 'Exceptional attention to detail. Lorry is running like brand new.'],
            [2, 5, 'Quick 45-minute turnaround. Highly recommended for commercial fleets.'],
            [3, 4, 'Brakes feel very crisp. A little wait during peak morning hour, but good job.'],
            [4, 5, 'Smooth oil change service. Good communication from coordinator.'],
            [7, 5, 'Master technician Ridhwan diagnosed the power loss immediately.'],
            [10, 5, 'Great corporate fleet arrangement. Invoice was prompt and accurate.'],
            [11, 4, 'Tyres balanced nicely. No more high speed steering wobble.'],
            [12, 5, 'Regular service on time.'],
            [14, 5, 'Chong Wei Lun cleared the intermittent check engine light thoroughly.'],
            [16, 5, 'Sprinter van back on our interstate delivery schedule without delay.'],
            [18, 5, 'Diagnostics spot on. Resolved boost issue.'],
            [22, 5, 'Critical freezer truck service handled with maximum priority.'],
            [24, 4, 'Good work on heavy transmission fluids.'],
            [28, 5, 'Escort pickup suspension overhauled cleanly.'],
            [33, 5, 'Hiace courier van runs smoothly. Satisfied with workshop.'],
            [37, 5, 'Very polite staff and clean customer lounge. Myvi running great.'],
            [39, 4, 'Proton Saga engine service done well.'],
            [40, 5, 'Mechanic helped install battery promptly during breakdown emergency.'],
            [42, 5, 'Civic Turbo feels responsive post-service. Castrol 5W-30 authentic.'],
            [43, 5, 'Aircon is freezing cold again! Siti Nabilah is a true specialist.'],
        ];

        foreach ($feedbacks as $fb) {
            DB::table('job_feedbacks')->insert([
                'job_card_id' => $fb[0],
                'rating'      => $fb[1],
                'comment'     => $fb[2],
                'created_at'  => Carbon::now()->subDays(rand(5, 60)),
                'updated_at'  => now(),
            ]);
        }

        // ----------------------------------------------------------------
        // 11. JOB CARD CHECK-INS (Coordinator floor monitoring notes)
        // ----------------------------------------------------------------
        DB::table('job_card_checkins')->insert([
            [
                'job_card_id'    => 47,
                'coordinator_id' => 7,
                'note'           => 'Checked with Siti Nabilah on Hiace Chiller — manifold gauges indicate slow low-pressure leak. UV dye applied, client PIC Kevin notified.',
                'created_at'     => Carbon::now()->subHours(4),
                'updated_at'     => Carbon::now()->subHours(4),
            ],
            [
                'job_card_id'    => 48,
                'coordinator_id' => 8,
                'note'           => 'Chong Wei Lun hooked up scanner on Proton X50. Fault traced to MAF sensor wiring harness. Preparing repair quote for Ain.',
                'created_at'     => Carbon::now()->subHours(3),
                'updated_at'     => Carbon::now()->subHours(3),
            ],
            [
                'job_card_id'    => 49,
                'coordinator_id' => 7,
                'note'           => 'Waiting on ATF WS replenishment delivery from AutoParts Sdn Bhd. Followed up with supplier driver — expected by 2:30 PM.',
                'created_at'     => Carbon::now()->subHours(5),
                'updated_at'     => Carbon::now()->subHours(5),
            ],
            [
                'job_card_id'    => 50,
                'coordinator_id' => 7,
                'note'           => 'Hairul has removed damaged front struts on Hilux Rogue. Monroe pair being torqued down. Alignment scheduled immediately after.',
                'created_at'     => Carbon::now()->subHours(2),
                'updated_at'     => Carbon::now()->subHours(2),
            ],
            [
                'job_card_id'    => 51,
                'coordinator_id' => 8,
                'note'           => 'Faiz completing Brembo front pad replacement on Apex courier van. Calipers greased. Road test pending.',
                'created_at'     => Carbon::now()->subHour(),
                'updated_at'     => Carbon::now()->subHour(),
            ],
            [
                'job_card_id'    => 52,
                'coordinator_id' => 7,
                'note'           => 'Ridhwan out on road test for Perdana Fleet Vios. Quality checklist 90% signed off.',
                'created_at'     => Carbon::now()->subMinutes(30),
                'updated_at'     => Carbon::now()->subMinutes(30),
            ],
        ]);

        // ----------------------------------------------------------------
        // 12. ACCOUNT REQUESTS (Corporate PIC Management)
        // ----------------------------------------------------------------
        DB::table('account_requests')->insert([
            [
                'requested_by'   => 9,
                'company_id'     => 1,
                'type'           => 'add_pic',
                'target_name'    => 'Encik Farouk bin Aziz',
                'target_email'   => 'farouk@mulialogistik.com.my',
                'target_phone'   => '011-98765432',
                'target_user_id' => null,
                'pic_role'       => 'secondary',
                'status'         => 'pending',
                'notes'          => 'New regional logistics executive joining Senai hub next month.',
                'created_at'     => Carbon::now()->subHours(6),
                'updated_at'     => Carbon::now()->subHours(6),
            ],
            [
                'requested_by'   => 15,
                'company_id'     => 4,
                'type'           => 'add_pic',
                'target_name'    => 'Nur Syahirah binti Azmi',
                'target_email'   => 'syahirah@southerncoldchain.my',
                'target_phone'   => '011-55889922',
                'target_user_id' => 16,
                'pic_role'       => 'secondary',
                'status'         => 'approved',
                'notes'          => 'Cold chain operations dispatcher account approved.',
                'created_at'     => Carbon::now()->subMonths(2),
                'updated_at'     => Carbon::now()->subMonths(2),
            ],
            [
                'requested_by'   => 11,
                'company_id'     => 2,
                'type'           => 'remove_pic',
                'target_name'    => null,
                'target_email'   => null,
                'target_phone'   => null,
                'target_user_id' => null,
                'pic_role'       => null,
                'status'         => 'rejected',
                'notes'          => 'Request cancelled by user — PIC still actively monitoring Cheras fleet.',
                'created_at'     => Carbon::now()->subDays(10),
                'updated_at'     => Carbon::now()->subDays(8),
            ],
        ]);

        // ----------------------------------------------------------------
        // 13. SETTINGS
        // ----------------------------------------------------------------
        DB::table('settings')->insert([
            ['key' => 'daily_appointment_capacity', 'value' => '25', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
