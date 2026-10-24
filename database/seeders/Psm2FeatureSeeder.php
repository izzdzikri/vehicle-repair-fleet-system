<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\MaintenancePredictionService;
use App\Models\MaintenanceAlert;

class Psm2FeatureSeeder extends Seeder
{
    public function run(): void
    {
        // ----------------------------------------------------------------
        // 1. TRUNCATE PSM2 FEATURE TABLES
        // ----------------------------------------------------------------
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('staff_attendance')->truncate();
        DB::table('leave_requests')->truncate();
        DB::table('salary_payments')->truncate();
        DB::table('payments')->truncate();
        DB::table('invoices')->truncate();
        DB::table('purchase_orders')->truncate();
        DB::table('suppliers')->truncate();
        DB::table('activity_logs')->truncate();
        DB::table('notifications')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ----------------------------------------------------------------
        // 2. SUPPLIERS (5 Major Malaysian Automotive Distributors)
        // ----------------------------------------------------------------
        DB::table('suppliers')->insert([
            [
                'id'             => 1,
                'name'           => 'AutoParts Sdn Bhd',
                'contact_person' => 'Encik Faizal Rahman',
                'phone'          => '07-2223344',
                'email'          => 'sales@autoparts.com.my',
                'address'        => 'No 5, Jalan Perindustrian 2, Senai Industrial Park, 81400 Senai, Johor',
                'notes'          => 'Primary supplier for Castrol lubricants, filters, and standard powertrain consumables.',
                'created_at'     => now()->subMonths(18),
                'updated_at'     => now(),
            ],
            [
                'id'             => 2,
                'name'           => 'Johor Motor Supplies Sdn Bhd',
                'contact_person' => 'Puan Aina Zulkifli',
                'phone'          => '07-3334455',
                'email'          => 'orders@jmsupplies.my',
                'address'        => 'Lot 22, Kawasan Perindustrian Larkin, 80350 Johor Bahru, Johor',
                'notes'          => 'Authorized Brembo, Akebono brake distributor and steering component supplier.',
                'created_at'     => now()->subMonths(16),
                'updated_at'     => now(),
            ],
            [
                'id'             => 3,
                'name'           => 'Prima Auto Distribution Hub',
                'contact_person' => 'Encik Wong Kah Meng',
                'phone'          => '06-5556677',
                'email'          => 'wong@primaautodist.com',
                'address'        => 'No 88, Jalan Kluang, 83000 Batu Pahat, Johor',
                'notes'          => 'Specialist in commercial suspension, Monroe shock absorbers, and heavy leaf springs.',
                'created_at'     => now()->subMonths(14),
                'updated_at'     => now(),
            ],
            [
                'id'             => 4,
                'name'           => 'UMW Industrial & Automotive Supplies',
                'contact_person' => 'Tuan Haji Azman bin Latiff',
                'phone'          => '03-51632000',
                'email'          => 'industrial.fleet@umw.com.my',
                'address'        => 'Persiaran Raja Muda, Seksyen 15, 40200 Shah Alam, Selangor',
                'notes'          => 'Direct distributor for Toyota Genuine ATF WS, Shell Rimula diesel drums, and heavy truck filters.',
                'created_at'     => now()->subMonths(12),
                'updated_at'     => now(),
            ],
            [
                'id'             => 5,
                'name'           => 'Denso & Bosch Regional Hub',
                'contact_person' => 'Chew Chee Kong',
                'phone'          => '07-3558811',
                'email'          => 'southern@denso-hub.my',
                'address'        => 'Plo 18, Kawasan Perindustrian Tebrau II, 81100 Johor Bahru, Johor',
                'notes'          => 'Authorized distributor for Denso AC compressors, R134a refrigerant, spark plugs, and electronics.',
                'created_at'     => now()->subMonths(10),
                'updated_at'     => now(),
            ],
        ]);

        // ----------------------------------------------------------------
        // 3. PURCHASE ORDERS (8 Orders demonstrating lifecycle & off-season savings)
        // ----------------------------------------------------------------
        DB::table('purchase_orders')->insert([
            // Received POs (Historical procurement with off-season volume discounts recorded in notes)
            [
                'id'             => 1,
                'supplier_id'    => 1,
                'spare_part_id'  => 1, // Castrol Engine Oil 5W-30
                'quantity'       => 40,
                'unit_cost'      => 52.00, // standard wholesale tariff RM 68.00
                'status'         => 'received',
                'auto_generated' => false,
                'ordered_at'     => now()->subDays(45),
                'received_at'    => now()->subDays(40),
                'notes'          => 'Mid-year fleet lubricant bulk replenishment. Secured 23.5% off-season volume pricing rebate.',
                'created_by'     => 1,
                'created_at'     => now()->subDays(46),
                'updated_at'     => now()->subDays(40),
            ],
            [
                'id'             => 2,
                'supplier_id'    => 2,
                'spare_part_id'  => 4, // Brembo Front Brake Pads
                'quantity'       => 20,
                'unit_cost'      => 148.00, // standard retail RM 185.00
                'status'         => 'received',
                'auto_generated' => false,
                'ordered_at'     => now()->subDays(30),
                'received_at'    => now()->subDays(26),
                'notes'          => 'Pre-monsoon brake component consignment. Off-season wholesale rate achieved.',
                'created_by'     => 1,
                'created_at'     => now()->subDays(31),
                'updated_at'     => now()->subDays(26),
            ],
            [
                'id'             => 3,
                'supplier_id'    => 4,
                'spare_part_id'  => 16, // Shell Rimula Heavy Duty Diesel Oil
                'quantity'       => 25,
                'unit_cost'      => 66.00, // standard retail RM 85.00
                'status'         => 'received',
                'auto_generated' => false,
                'ordered_at'     => now()->subDays(20),
                'received_at'    => now()->subDays(16),
                'notes'          => 'Heavy commercial fleet drum procurement under corporate logistics agreement.',
                'created_by'     => 1,
                'created_at'     => now()->subDays(21),
                'updated_at'     => now()->subDays(16),
            ],
            [
                'id'             => 4,
                'supplier_id'    => 5,
                'spare_part_id'  => 21, // Refrigerant R134a
                'quantity'       => 6,
                'unit_cost'      => 225.00, // standard retail RM 290.00
                'status'         => 'received',
                'auto_generated' => false,
                'ordered_at'     => now()->subDays(14),
                'received_at'    => now()->subDays(10),
                'notes'          => 'Southern Cold Chain freezer maintenance preparation.',
                'created_by'     => 1,
                'created_at'     => now()->subDays(15),
                'updated_at'     => now()->subDays(10),
            ],
            // Ordered POs (Currently in transit)
            [
                'id'             => 5,
                'supplier_id'    => 4,
                'spare_part_id'  => 9, // Toyota ATF WS Transmission Fluid
                'quantity'       => 25,
                'unit_cost'      => 29.50, // standard retail RM 39.00
                'status'         => 'ordered',
                'auto_generated' => true,
                'ordered_at'     => now()->subDays(2),
                'received_at'    => null,
                'notes'          => 'Automated replenishment for fleet transmission schedules. Awaiting courier delivery from Shah Alam.',
                'created_by'     => 1,
                'created_at'     => now()->subDays(3),
                'updated_at'     => now()->subDays(2),
            ],
            [
                'id'             => 6,
                'supplier_id'    => 2,
                'spare_part_id'  => 18, // Brembo Brake Disc Rotors
                'quantity'       => 10,
                'unit_cost'      => 245.00, // standard retail RM 310.00
                'status'         => 'ordered',
                'auto_generated' => false,
                'ordered_at'     => now()->subDays(1),
                'received_at'    => null,
                'notes'          => 'Expedited brake rotor stock for commercial transport clients.',
                'created_by'     => 1,
                'created_at'     => now()->subDays(2),
                'updated_at'     => now()->subDays(1),
            ],
            // Draft POs (Awaiting admin approval / auto-drafted from low stock)
            [
                'id'             => 7,
                'supplier_id'    => 1,
                'spare_part_id'  => 10, // Serpentine Drive Belt 6PK (stock = 3, min = 6)
                'quantity'       => 20,
                'unit_cost'      => 68.00, // standard retail RM 95.00
                'status'         => 'draft',
                'auto_generated' => true,
                'ordered_at'     => null,
                'received_at'    => null,
                'notes'          => 'Auto-generated reorder — current stock (3) is below minimum safety threshold (6).',
                'created_by'     => 1,
                'created_at'     => now()->subHours(12),
                'updated_at'     => now()->subHours(12),
            ],
            [
                'id'             => 8,
                'supplier_id'    => 3,
                'spare_part_id'  => 14, // Front Shock Absorber Pair (stock = 2, min = 4)
                'quantity'       => 8,
                'unit_cost'      => 365.00, // standard retail RM 480.00
                'status'         => 'draft',
                'auto_generated' => true,
                'ordered_at'     => null,
                'received_at'    => null,
                'notes'          => 'Auto-generated reorder — current stock (2) is below minimum safety threshold (4).',
                'created_by'     => 1,
                'created_at'     => now()->subHours(8),
                'updated_at'     => now()->subHours(8),
            ],
        ]);

        // ----------------------------------------------------------------
        // 4. INVOICES & PAYMENTS (44 Invoices for completed workshop jobs)
        // Sequential numbers: INV-2026-00001 through INV-2026-00044
        // Status distribution: 36 Paid, 4 Partial, 4 Unpaid
        // ----------------------------------------------------------------
        $invoiceRows = [];
        $paymentRows = [];

        for ($jobId = 1; $jobId <= 44; $jobId++) {
            $job = DB::table('job_cards')->find($jobId);
            if (!$job) continue;

            $partsCost  = (float) DB::table('job_card_parts')->where('job_card_id', $jobId)->sum(DB::raw('quantity * unit_price'));
            $labourCost = (float) DB::table('labour_charges')->where('job_card_id', $jobId)->sum('charge');
            $subtotal   = $partsCost + $labourCost;
            if ($subtotal <= 0) $subtotal = (float) $job->total_cost;

            $taxRate   = ($jobId % 3 === 0) ? 6.00 : 0.00; // 6% SST on selected commercial invoices
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total     = $subtotal + $taxAmount;

            // Resolve customer / company name
            $customerName = 'Walk-in Customer';
            $appointment = DB::table('appointments')->find($job->appointment_id);
            if ($appointment) {
                if ($appointment->is_walkin) {
                    $customerName = $appointment->walkin_name ?? 'Walk-in Customer';
                } elseif ($appointment->user_id) {
                    $user = DB::table('users')->find($appointment->user_id);
                    if ($user) {
                        if ($user->company_id) {
                            $company = DB::table('companies')->find($user->company_id);
                            $customerName = $company ? $company->name : $user->name;
                        } else {
                            $customerName = $user->name;
                        }
                    }
                }
            }

            // Status mix:
            // Jobs 1–36: fully paid
            // Jobs 37–40: partial (50% paid)
            // Jobs 41–44: unpaid
            if ($jobId <= 36) {
                $status = 'paid';
                $amountPaid = $total;
            } elseif ($jobId <= 40) {
                $status = 'partial';
                $amountPaid = round($total * 0.5, 2);
            } else {
                $status = 'unpaid';
                $amountPaid = 0.00;
            }

            $invNumber = 'INV-' . now()->format('Y') . '-' . str_pad($jobId, 5, '0', STR_PAD_LEFT);
            $createdAt = Carbon::parse($job->created_at)->addHours(2);
            $dueDate   = $createdAt->copy()->addDays(30)->toDateString();

            $invoiceRows[] = [
                'id'             => $jobId,
                'job_card_id'    => $jobId,
                'invoice_number' => $invNumber,
                'customer_name'  => $customerName,
                'subtotal'       => $subtotal,
                'tax_rate'       => $taxRate,
                'tax_amount'     => $taxAmount,
                'total'          => $total,
                'amount_paid'    => $amountPaid,
                'status'         => $status,
                'due_date'       => $dueDate,
                'notes'          => ($status === 'partial') ? 'First 50% deposit received. Balance due upon monthly statement.' : null,
                'generated_by'   => 1,
                'created_at'     => $createdAt,
                'updated_at'     => $createdAt,
            ];

            if ($amountPaid > 0) {
                $methods = ['bank_transfer', 'online', 'card', 'cash'];
                $method  = $methods[$jobId % 4];
                $refNo   = match ($method) {
                    'bank_transfer' => 'MBB-TRF-' . (884000 + $jobId),
                    'online'        => 'FPX-' . now()->format('Ymd') . '-' . (20000 + $jobId),
                    'card'          => 'VISA-AUTH-' . (40000 + $jobId),
                    default         => 'CASH-REC-' . (1000 + $jobId),
                };

                $paymentRows[] = [
                    'invoice_id'   => $jobId,
                    'amount'       => $amountPaid,
                    'method'       => $method,
                    'reference_no' => $refNo,
                    'paid_at'      => $createdAt->copy()->addHours(1),
                    'recorded_by'  => 1,
                    'notes'        => 'Payment verified and posted to Teraju general ledger.',
                    'created_at'   => $createdAt->copy()->addHours(1),
                    'updated_at'   => $createdAt->copy()->addHours(1),
                ];
            }
        }

        DB::table('invoices')->insert($invoiceRows);
        DB::table('payments')->insert($paymentRows);

        // ----------------------------------------------------------------
        // 5. STAFF ATTENDANCE (Past 30 working days across all 5 technicians)
        // ----------------------------------------------------------------
        $staffIds = [2, 3, 4, 5, 6];
        $attendanceRows = [];

        foreach ($staffIds as $sId) {
            for ($daysAgo = 35; $daysAgo >= 1; $daysAgo--) {
                $d = Carbon::now()->subDays($daysAgo);
                if ($d->isWeekend()) continue;

                // Add realistic variety (present with varied clock-in, 1 absence, 1 half-day)
                $status = 'present';
                $notes  = null;

                if ($sId === 4 && $daysAgo === 12) {
                    $status = 'leave';
                    $notes  = 'Medical leave approved.';
                } elseif ($sId === 6 && $daysAgo === 5) {
                    $status = 'half_day';
                    $notes  = 'Technical training session in afternoon.';
                }

                $clockIn  = null;
                $clockOut = null;
                if ($status === 'present') {
                    $minIn    = str_pad(rand(50, 59), 2, '0', STR_PAD_LEFT);
                    $hourIn   = (rand(0, 10) > 8) ? '08:0' . rand(0, 8) : '07:' . $minIn;
                    $clockIn  = $hourIn . ':00';
                    $clockOut = '17:' . str_pad(rand(10, 55), 2, '0', STR_PAD_LEFT) . ':00';
                } elseif ($status === 'half_day') {
                    $clockIn  = '08:00:00';
                    $clockOut = '13:00:00';
                }

                $attendanceRows[] = [
                    'staff_id'   => $sId,
                    'date'       => $d->toDateString(),
                    'clock_in'   => $clockIn,
                    'clock_out'  => $clockOut,
                    'status'     => $status,
                    'notes'      => $notes,
                    'created_at' => $d,
                    'updated_at' => $d,
                ];
            }
        }
        DB::table('staff_attendance')->insert($attendanceRows);

        // ----------------------------------------------------------------
        // 6. LEAVE REQUESTS
        // ----------------------------------------------------------------
        DB::table('leave_requests')->insert([
            [
                'staff_id'    => 4,
                'start_date'  => Carbon::now()->subDays(12)->toDateString(),
                'end_date'    => Carbon::now()->subDays(12)->toDateString(),
                'reason'      => 'Medical leave — dental extraction.',
                'status'      => 'approved',
                'approved_by' => 1,
                'admin_notes' => 'Medical certificate verified and filed.',
                'created_at'  => Carbon::now()->subDays(13),
                'updated_at'  => Carbon::now()->subDays(12),
            ],
            [
                'staff_id'    => 2,
                'start_date'  => Carbon::now()->subDays(25)->toDateString(),
                'end_date'    => Carbon::now()->subDays(24)->toDateString(),
                'reason'      => 'Family emergency outstation in Melaka.',
                'status'      => 'approved',
                'approved_by' => 1,
                'admin_notes' => 'Approved by workshop director Ahmad Firdaus.',
                'created_at'  => Carbon::now()->subDays(27),
                'updated_at'  => Carbon::now()->subDays(26),
            ],
            [
                'staff_id'    => 3,
                'start_date'  => Carbon::now()->addDays(5)->toDateString(),
                'end_date'    => Carbon::now()->addDays(6)->toDateString(),
                'reason'      => 'Sister wedding in Batu Pahat.',
                'status'      => 'pending',
                'approved_by' => null,
                'admin_notes' => null,
                'created_at'  => Carbon::now()->subDays(1),
                'updated_at'  => Carbon::now()->subDays(1),
            ],
        ]);

        // ----------------------------------------------------------------
        // 7. SALARY PAYMENTS (Past 3 months for all technicians)
        // ----------------------------------------------------------------
        $salaries = [
            2 => 3400.00,
            3 => 2800.00,
            4 => 2700.00,
            5 => 3300.00,
            6 => 2200.00,
        ];

        $salaryRows = [];
        foreach ($salaries as $staffId => $amount) {
            foreach ([1, 2, 3] as $m) {
                $period = Carbon::now()->subMonths($m);
                $salaryRows[] = [
                    'staff_id'    => $staffId,
                    'period'      => $period->format('Y-m'),
                    'amount'      => $amount,
                    'method'      => 'bank_transfer',
                    'paid_at'     => $period->copy()->endOfMonth()->toDateString(),
                    'notes'       => 'Direct bank transfer salary disbursement via Maybank Business.',
                    'recorded_by' => 1,
                    'created_at'  => $period->copy()->endOfMonth(),
                    'updated_at'  => $period->copy()->endOfMonth(),
                ];
            }
        }
        DB::table('salary_payments')->insert($salaryRows);

        // ----------------------------------------------------------------
        // 8. ACTIVITY LOGS (Realistic workshop audit trail)
        // ----------------------------------------------------------------
        DB::table('activity_logs')->insert([
            ['user_id'=>1, 'action'=>'auth.login',           'description'=>'Admin logged in from workshop management terminal.', 'subject_type'=>null, 'subject_id'=>null, 'created_at'=>now()->subHours(1), 'updated_at'=>now()->subHours(1)],
            ['user_id'=>1, 'action'=>'invoice.paid',         'description'=>'Invoice INV-2026-00028 marked as paid in full.',     'subject_type'=>'App\Models\Invoice', 'subject_id'=>28, 'created_at'=>now()->subHours(2), 'updated_at'=>now()->subHours(2)],
            ['user_id'=>2, 'action'=>'job_card.stage_change','description'=>'Job Card #52 moved to quality_check stage.',        'subject_type'=>'App\Models\JobCard', 'subject_id'=>52, 'created_at'=>now()->subHours(3), 'updated_at'=>now()->subHours(3)],
            ['user_id'=>7, 'action'=>'job_card.checkin',     'description'=>'Coordinator Aisyah added progress note on Job Card #47.', 'subject_type'=>'App\Models\JobCard', 'subject_id'=>47, 'created_at'=>now()->subHours(4), 'updated_at'=>now()->subHours(4)],
            ['user_id'=>1, 'action'=>'purchase_order.draft', 'description'=>'Auto-drafted Purchase Order #7 for Serpentine Belts.','subject_type'=>'App\Models\PurchaseOrder', 'subject_id'=>7, 'created_at'=>now()->subHours(12), 'updated_at'=>now()->subHours(12)],
            ['user_id'=>1, 'action'=>'prediction.verified',  'description'=>'Admin verified predicted maintenance alert for vehicle JQP 1122.', 'subject_type'=>'App\Models\MaintenanceAlert', 'subject_id'=>1, 'created_at'=>now()->subDays(1), 'updated_at'=>now()->subDays(1)],
            ['user_id'=>9, 'action'=>'account_request.submit','description'=>'Mulia Logistik submitted add PIC request for Encik Farouk.', 'subject_type'=>'App\Models\AccountRequest', 'subject_id'=>1, 'created_at'=>now()->subHours(6), 'updated_at'=>now()->subHours(6)],
        ]);

        // ----------------------------------------------------------------
        // 9. NOTIFICATIONS
        // ----------------------------------------------------------------
        DB::table('notifications')->insert([
            [
                'user_id'    => 1,
                'title'      => 'Low Stock Alert',
                'message'    => 'Serpentine Drive Belt 6PK has reached minimum reorder level (3 units remaining).',
                'url'        => '/admin/purchase-orders',
                'is_read'    => false,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'user_id'    => 1,
                'title'      => 'Pending PIC Account Request',
                'message'    => 'Syarikat Logistik Mulia requested to add Encik Farouk bin Aziz as fleet PIC.',
                'url'        => '/admin/companies/1',
                'is_read'    => false,
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6),
            ],
            [
                'user_id'    => 2,
                'title'      => 'New Job Assigned',
                'message'    => 'You have been assigned to JQP 1122 (Isuzu D-Max) for Oil & Filter Change.',
                'url'        => '/staff/job-cards/45',
                'is_read'    => true,
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(1),
            ],
            [
                'user_id'    => 9,
                'title'      => 'Vehicle Intake Confirmed',
                'message'    => 'Your fleet vehicle JQP 1122 was received at Teraju Workshop for scheduled maintenance.',
                'url'        => '/client/job-cards/45',
                'is_read'    => false,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'user_id'    => 11,
                'title'      => 'Quality Inspection Passed',
                'message'    => 'WXY 2211 (Toyota Vios) has passed final quality check and is ready for collection.',
                'url'        => '/client/job-cards/52',
                'is_read'    => false,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'user_id'    => 21,
                'title'      => 'Diagnostic Complete',
                'message'    => 'Diagnostic scanner completed on VBB 7712 (Proton X50). Repair estimate ready.',
                'url'        => '/customer/appointments/48',
                'is_read'    => true,
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(2),
            ],
        ]);

        // ----------------------------------------------------------------
        // 10. PREDICTIVE MAINTENANCE ENGINE & PROOFREADING DATA
        // Run the prediction scanner dynamically across the 34 vehicles and 100+ trip logs!
        // Then verify and override a subset to populate the admin proofread screen.
        // ----------------------------------------------------------------
        DB::table('maintenance_alerts')->truncate();

        // Run the prediction engine
        $predictionService = app(MaintenancePredictionService::class);
        $predictionService->runAll();

        // Mark a subset of generated predicted alerts as verified and overridden for demonstration
        $alerts = MaintenanceAlert::where('source', 'predicted')->orderBy('id')->get();

        if ($alerts->count() >= 1) {
            $alert1 = $alerts[0];
            $alert1->update([
                'is_verified' => true,
                'verified_by' => 1,
                'verified_at' => now()->subDays(3),
                'admin_notes' => 'Confirmed during bi-weekly fleet audit with Mulia Logistik PIC Hafiz. Scheduled for workshop intake next Tuesday.',
            ]);
        }

        if ($alerts->count() >= 2) {
            $alert2 = $alerts[1];
            $alert2->update([
                'is_verified'         => true,
                'verified_by'         => 1,
                'verified_at'         => now()->subDays(2),
                'admin_notes'         => 'Driver reported brake shudder under heavy cargo loads on Karak highway. Advanced predicted due date forward by 6 days.',
                'overridden_due_date' => now()->addDays(8),
                'overridden_urgency'  => 'high',
            ]);
        }

        if ($alerts->count() >= 3) {
            $alert3 = $alerts[2];
            $alert3->update([
                'is_verified' => true,
                'verified_by' => 1,
                'verified_at' => now()->subDays(1),
                'admin_notes' => 'Confirmed with Cold Chain logistics manager Kevin Lee. Freezer compressor service priority before upcoming interstate dairy delivery.',
            ]);
        }

        if ($alerts->count() >= 4) {
            $alert4 = $alerts[3];
            $alert4->update([
                'is_verified'         => true,
                'verified_by'         => 1,
                'verified_at'         => now()->subHours(8),
                'admin_notes'         => 'Customer Ain acknowledged via WhatsApp. Combining service with upcoming annual inspection.',
                'overridden_urgency'  => 'medium',
            ]);
        }
    }
}
