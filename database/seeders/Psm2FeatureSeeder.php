<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Psm2FeatureSeeder extends Seeder
{
    public function run(): void
    {
        // ----------------------------------------------------------------
        // Backfill completed_at for job cards seeded before this column
        // existed, so the Staff Performance report has real data to show.
        // ----------------------------------------------------------------
        $completedJobs = DB::table('job_cards')->where('current_stage', 'completed')->get();
        foreach ($completedJobs as $job) {
            // Stagger the completion time so some jobs land "on time" and
            // a couple land "overdue" for demo variety.
            $offsetHours = match ($job->id % 4) {
                0 => -2, // finished early
                1 => 1,  // finished a bit after estimate (overdue)
                2 => -1, // finished slightly early
                default => 0, // finished right on time
            };
            $base = $job->estimated_completion ?? $job->created_at;
            DB::table('job_cards')->where('id', $job->id)->update([
                'completed_at' => Carbon::parse($base)->addHours($offsetHours),
            ]);
        }

        // ----------------------------------------------------------------
        // Staff salary + hire date (users 2, 3, 4 are the seeded staff)
        // ----------------------------------------------------------------
        DB::table('users')->where('id', 2)->update(['monthly_salary' => 2800.00, 'hire_date' => '2023-03-01']);
        DB::table('users')->where('id', 3)->update(['monthly_salary' => 2600.00, 'hire_date' => '2024-01-15']);
        DB::table('users')->where('id', 4)->update(['monthly_salary' => 2500.00, 'hire_date' => '2024-07-01']);

        // ----------------------------------------------------------------
        // TRUNCATE all tables this seeder owns.
        //
        // Wrapped in FOREIGN_KEY_CHECKS=0/1 (same convention as
        // FreshSeeder.php) because MySQL/InnoDB refuses to TRUNCATE any
        // table that another table has a foreign key pointing at,
        // regardless of truncate order or whether the referencing table
        // is empty. Disabling checks for this block sidesteps that
        // entirely instead of having to hand-order every truncate.
        // ----------------------------------------------------------------
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('staff_attendance')->truncate();
        DB::table('leave_requests')->truncate();
        DB::table('salary_payments')->truncate();
        DB::table('payments')->truncate();
        DB::table('invoices')->truncate();
        DB::table('purchase_orders')->truncate();
        DB::table('suppliers')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ----------------------------------------------------------------
        // Staff attendance — last 10 working days for each staff member
        // ----------------------------------------------------------------
        $staffIds = [2, 3, 4];
        foreach ($staffIds as $staffId) {
            for ($i = 10; $i >= 1; $i--) {
                $date = Carbon::now()->subDays($i);
                if ($date->isWeekend()) continue;

                // Staff 4 has one absence recorded, for variety in the report.
                $status = ($staffId === 4 && $i === 3) ? 'absent' : 'present';

                DB::table('staff_attendance')->insert([
                    'staff_id'   => $staffId,
                    'date'       => $date->toDateString(),
                    'clock_in'   => $status === 'present' ? '08:0' . rand(0, 5) . ':00' : null,
                    'clock_out'  => $status === 'present' ? '17:' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00' : null,
                    'status'     => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ----------------------------------------------------------------
        // Leave requests
        // ----------------------------------------------------------------
        DB::table('leave_requests')->insert([
            [
                'staff_id'    => 3,
                'start_date'  => Carbon::now()->addDays(5)->toDateString(),
                'end_date'    => Carbon::now()->addDays(6)->toDateString(),
                'reason'      => 'Family event out of town.',
                'status'      => 'pending',
                'approved_by' => null,
                'admin_notes' => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'staff_id'    => 2,
                'start_date'  => Carbon::now()->subDays(20)->toDateString(),
                'end_date'    => Carbon::now()->subDays(19)->toDateString(),
                'reason'      => 'Medical appointment.',
                'status'      => 'approved',
                'approved_by' => 1,
                'admin_notes' => null,
                'created_at'  => now()->subDays(21),
                'updated_at'  => now()->subDays(20),
            ],
        ]);

        // ----------------------------------------------------------------
        // Salary payments — last 2 months for each staff member
        // ----------------------------------------------------------------
        $salaries = [2 => 2800.00, 3 => 2600.00, 4 => 2500.00];
        foreach ($salaries as $staffId => $amount) {
            foreach ([1, 2] as $monthsAgo) {
                $period = Carbon::now()->subMonths($monthsAgo);
                DB::table('salary_payments')->insert([
                    'staff_id'    => $staffId,
                    'period'      => $period->format('Y-m'),
                    'amount'      => $amount,
                    'method'      => 'bank_transfer',
                    'paid_at'     => $period->copy()->endOfMonth()->toDateString(),
                    'notes'       => null,
                    'recorded_by' => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        // ----------------------------------------------------------------
        // Suppliers
        // ----------------------------------------------------------------
        DB::table('suppliers')->insert([
            [
                'id' => 1, 'name' => 'AutoParts Sdn Bhd', 'contact_person' => 'Encik Faizal Rahman',
                'phone' => '07-2223344', 'email' => 'sales@autoparts.com.my',
                'address' => 'No 5, Jalan Perindustrian 2, Senai, Johor',
                'notes' => null, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 2, 'name' => 'Johor Motor Supplies', 'contact_person' => 'Puan Aina Zulkifli',
                'phone' => '07-3334455', 'email' => 'orders@jmsupplies.my',
                'address' => 'Lot 22, Kawasan Perindustrian Larkin, Johor Bahru',
                'notes' => 'Preferred supplier for brake components.', 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 3, 'name' => 'Prima Auto Distribution', 'contact_person' => 'Encik Wong Kah Meng',
                'phone' => '06-5556677', 'email' => 'wong@primaautodist.com',
                'address' => 'Batu Pahat, Johor',
                'notes' => null, 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        // ----------------------------------------------------------------
        // Purchase orders — a couple already progressed to "ordered"/
        // "received" so those states aren't empty on first load. Any other
        // part still at/under min stock gets auto-drafted the first time
        // the Purchase Orders page is visited.
        // ----------------------------------------------------------------
        DB::table('purchase_orders')->insert([
            [
                'supplier_id' => 1, 'spare_part_id' => 10, // Serpentine Belt (seeded low stock)
                'quantity' => 20, 'unit_cost' => 62.00, 'status' => 'ordered',
                'auto_generated' => true, 'ordered_at' => now()->subDays(2), 'received_at' => null,
                'notes' => null, 'created_by' => 1, 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(2),
            ],
            [
                'supplier_id' => 2, 'spare_part_id' => 14, // Shock Absorber (seeded low stock)
                'quantity' => 8, 'unit_cost' => 410.00, 'status' => 'received',
                'auto_generated' => true, 'ordered_at' => now()->subDays(10), 'received_at' => now()->subDays(6),
                'notes' => null, 'created_by' => 1, 'created_at' => now()->subDays(11), 'updated_at' => now()->subDays(6),
            ],
        ]);
        // Bump stock for the received PO above, matching what the app would do.
        DB::table('spare_parts')->where('id', 14)->increment('stock', 8);

        // ----------------------------------------------------------------
        // Invoices + payments for a few already-completed job cards
        // ----------------------------------------------------------------
        $invoiceSeed = [
            // job_card_id => [tax_rate, fully paid?]
            1 => ['tax_rate' => 0, 'paid' => true],
            2 => ['tax_rate' => 6, 'paid' => true],
            5 => ['tax_rate' => 0, 'paid' => false], // demo the "partial" status
        ];

        $invId = 1;
        foreach ($invoiceSeed as $jobCardId => $meta) {
            $job = DB::table('job_cards')->find($jobCardId);
            if (!$job) continue;

            $partsCost  = DB::table('job_card_parts')->where('job_card_id', $jobCardId)
                ->sum(DB::raw('quantity * unit_price'));
            $labourCost = DB::table('labour_charges')->where('job_card_id', $jobCardId)->sum('charge');
            $subtotal   = $partsCost + $labourCost;
            $taxAmount  = round($subtotal * ($meta['tax_rate'] / 100), 2);
            $total      = $subtotal + $taxAmount;

            $appointment = DB::table('appointments')->find($job->appointment_id);
            $customerName = 'Walk-in Customer';
            if ($appointment) {
                if ($appointment->is_walkin) {
                    $customerName = $appointment->walkin_name ?? 'Walk-in Customer';
                } else {
                    $customer = DB::table('users')->find($appointment->user_id);
                    $customerName = $customer->name ?? '—';
                }
            }

            $number     = 'INV-' . now()->format('Y') . '-' . str_pad($invId, 5, '0', STR_PAD_LEFT);
            $amountPaid = $meta['paid'] ? $total : round($total * 0.5, 2);
            $status     = $total <= 0
                ? 'paid'
                : ($amountPaid >= $total ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid'));

            DB::table('invoices')->insert([
                'id'             => $invId,
                'job_card_id'    => $jobCardId,
                'invoice_number' => $number,
                'customer_name'  => $customerName,
                'subtotal'       => $subtotal,
                'tax_rate'       => $meta['tax_rate'],
                'tax_amount'     => $taxAmount,
                'total'          => $total,
                'amount_paid'    => $amountPaid,
                'status'         => $status,
                'due_date'       => null,
                'notes'          => null,
                'generated_by'   => 1,
                'created_at'     => now()->subDays(5),
                'updated_at'     => now(),
            ]);

            if ($amountPaid > 0) {
                DB::table('payments')->insert([
                    'invoice_id'   => $invId,
                    'amount'       => $amountPaid,
                    'method'       => 'cash',
                    'reference_no' => null,
                    'paid_at'      => now()->subDays(4),
                    'recorded_by'  => 1,
                    'notes'        => null,
                    'created_at'   => now()->subDays(4),
                    'updated_at'   => now()->subDays(4),
                ]);
            }

            $invId++;
        }
    }
}