@extends('layouts.app')
@section('page-title', 'Staff Payroll & Salary')

@section('content')

@php
    $isAdmin = auth()->user()->role === 'admin';
    $currentCarbon = \Carbon\Carbon::createFromFormat('Y-m', $period);
@endphp

@if($isAdmin)
{{-- Admin Subnavigation Tabs --}}
<div class="flex gap-2 mb-6 flex-wrap">
    <a href="/admin/staff-management/roster" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Team Directory & Positions</a>
    <a href="/admin/staff-management/attendance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Attendance Daily</a>
    <a href="/admin/staff-management/leave" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 flex items-center gap-1.5">
        <span>Leave Requests</span>
        @php $pendingLeave = \App\Models\LeaveRequest::where('status', 'pending')->count(); @endphp
        @if($pendingLeave > 0)
        <span class="w-5 h-5 rounded-full bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $pendingLeave }}</span>
        @endif
    </a>
    <a href="/admin/staff-management/performance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Performance & KPIs</a>
    <a href="/staff-management/salary" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white shadow-xs">Payroll & Salary</a>
</div>
@else
<div class="mb-6">
    <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Staff Salary Disbursements</h2>
    <p class="text-sm text-gray-500 mt-1">Record monthly payroll compensation and generate printable employee payslip vouchers</p>
</div>
@endif

{{-- Payroll Metrics Summary Cards for Selected Month --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Monthly Budget</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="wallet" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-gray-900 tracking-tight">RM {{ number_format($stats['totalObligation'], 2) }}</p>
            <p class="text-xs text-gray-400 font-medium mt-1">{{ count($staff) }} workshop personnel</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-emerald-200/80 shadow-xs p-5 bg-emerald-50/20 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Disbursed ({{ $currentCarbon->format('M Y') }})</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <i data-lucide="check" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-emerald-700 tracking-tight">RM {{ number_format($stats['disbursedThisMonth'], 2) }}</p>
            <p class="text-xs text-emerald-600 font-medium mt-1">{{ $stats['paidStaffCount'] }} staff compensated</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-amber-200/80 shadow-xs p-5 bg-amber-50/20 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Pending Payout</span>
            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                <i data-lucide="clock" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-amber-700 tracking-tight">{{ $stats['pendingStaffCount'] }}</p>
            <p class="text-xs text-amber-600 font-medium mt-1">staff pending disbursement</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Payout Progress</span>
            <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center">
                <i data-lucide="percent" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $stats['percentDisbursed'] }}%</p>
            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-emerald-600 h-1.5 rounded-full" style="width: {{ $stats['percentDisbursed'] }}%"></div>
            </div>
        </div>
    </div>
</div>

{{-- Record Salary Disbursement Form --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 mb-6"
    x-data="{
        staffId: '',
        period: '{{ $period }}',
        baseSalary: 0,
        allowances: 0,
        deductions: 0,
        amount: 0,
        method: 'bank_transfer',
        salaries: {{ $staff->mapWithKeys(fn($s) => [$s->id => (float)($s->monthly_salary ?? 0)])->toJson() }},
        onStaffChange() {
            if (this.staffId && this.salaries[this.staffId] !== undefined) {
                this.baseSalary = parseFloat(this.salaries[this.staffId]) || 0;
            } else {
                this.baseSalary = 0;
            }
            this.recalculate();
        },
        recalculate() {
            const base = parseFloat(this.baseSalary) || 0;
            const allow = parseFloat(this.allowances) || 0;
            const deduct = parseFloat(this.deductions) || 0;
            this.amount = Math.max(0, base + allow - deduct).toFixed(2);
        }
    }">
    
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 flex-wrap gap-2">
        <div>
            <h2 class="text-base font-bold text-gray-900 tracking-tight">Record Salary Disbursement & Generate Payslip</h2>
            <p class="text-xs text-gray-500 mt-0.5">Issue monthly payroll vouchers for workshop technicians and coordinators</p>
        </div>
    </div>

    <form method="POST" action="/staff-management/salary" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Staff Personnel *</label>
                <select name="staff_id" x-model="staffId" @change="onStaffChange()"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
                    <option value="">Select staff / coordinator</option>
                    @foreach($staff as $s)
                    <option value="{{ $s->id }}">
                        {{ $s->name }} ({{ $s->staff_role ?? ucfirst($s->role) }}) — RM {{ number_format($s->monthly_salary ?? 0, 2) }}/mo
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Payroll Month *</label>
                <input type="month" name="period" x-model="period"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Disbursement Method *</label>
                <select name="method" x-model="method"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
                    <option value="bank_transfer">Bank Transfer (DuitNow/EFT)</option>
                    <option value="cash">Cash (Tunai)</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Payment Date *</label>
                <input type="date" name="paid_at" value="{{ now()->toDateString() }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs" required>
            </div>
        </div>

        {{-- Financial Breakdown Row --}}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-xl border border-gray-200/80">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Basic Salary (RM)</label>
                <input type="number" name="base_salary" x-model="baseSalary" @input="recalculate()" step="0.01" min="0"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Allowances / OT (RM)</label>
                <input type="number" name="allowances" x-model="allowances" @input="recalculate()" step="0.01" min="0"
                    placeholder="0.00"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Deductions / Adv (RM)</label>
                <input type="number" name="deductions" x-model="deductions" @input="recalculate()" step="0.01" min="0"
                    placeholder="0.00"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label class="block text-xs font-bold text-emerald-800 uppercase tracking-wider mb-1">Net Payable (RM) *</label>
                <input type="number" name="amount" x-model="amount" step="0.01" min="0.01" readonly
                    class="w-full border border-emerald-300 rounded-lg px-3 py-1.5 text-base font-extrabold text-emerald-700 bg-emerald-50/50 font-mono focus:outline-none cursor-not-allowed">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Remarks / Payment Reference</label>
            <input type="text" name="notes" placeholder="e.g. Maybank Ref #MBB982138, Attendance bonus included"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit"
                class="bg-slate-900 text-white px-6 py-2.5 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
                <i data-lucide="receipt" class="w-4 h-4"></i>
                <span>Log Salary Disbursement & Issue Payslip</span>
            </button>
        </div>
    </form>
</div>

{{-- Historical Disbursement Ledger --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-4">
        <div>
            <h2 class="text-base font-bold text-gray-900 tracking-tight">Payroll Disbursement & Payslip Ledger</h2>
            <p class="text-xs text-gray-500 mt-0.5">Historical verification of employee payments and printable vouchers</p>
        </div>

        {{-- Month Filter --}}
        <form method="GET" action="/staff-management/salary" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-gray-500">Filter Month:</label>
            <input type="month" name="period" value="{{ $period }}" onchange="this.form.submit()"
                class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs font-bold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer shadow-xs">
            @if($period !== now()->format('Y-m'))
            <a href="/staff-management/salary" class="text-xs text-blue-600 hover:underline">Current Month</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                    <th class="px-5 py-3.5">Voucher #</th>
                    <th class="px-5 py-3.5">Staff Recipient</th>
                    <th class="px-5 py-3.5">Payroll Month</th>
                    <th class="px-5 py-3.5">Breakdown (Base + Allow - Deduct)</th>
                    <th class="px-5 py-3.5">Net Disbursed</th>
                    <th class="px-5 py-3.5">Method & Date</th>
                    <th class="px-5 py-3.5 text-right">Payslip</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $p)
                @php
                    $isPeriodMatch = $p->period === $period;
                @endphp
                <tr class="hover:bg-gray-50/60 transition {{ $isPeriodMatch ? 'bg-blue-50/10' : '' }}">
                    <td class="px-5 py-4">
                        <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                            {{ $p->slip_number }}
                        </span>
                    </td>

                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-slate-900 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($p->staff->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-sm text-gray-900">{{ $p->staff->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $p->staff->staff_role ?? ucfirst($p->staff->role ?? '') }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-xs font-bold text-gray-800">
                        {{ \Carbon\Carbon::createFromFormat('Y-m', $p->period)->format('F Y') }}
                    </td>

                    <td class="px-5 py-4 text-xs text-gray-600 font-mono">
                        RM {{ number_format($p->base_salary ?: $p->amount, 2) }}
                        @if($p->allowances > 0)
                        <span class="text-emerald-600 font-semibold">+{{ number_format($p->allowances, 2) }}</span>
                        @endif
                        @if($p->deductions > 0)
                        <span class="text-rose-600 font-semibold">-{{ number_format($p->deductions, 2) }}</span>
                        @endif
                    </td>

                    <td class="px-5 py-4 font-bold text-sm text-emerald-700 font-mono">
                        RM {{ number_format($p->amount, 2) }}
                    </td>

                    <td class="px-5 py-4 text-xs text-gray-600">
                        <p class="font-semibold text-gray-900 capitalize">{{ str_replace('_', ' ', $p->method) }}</p>
                        <p class="text-gray-400 mt-0.5">{{ $p->paid_at->format('d M Y') }}</p>
                    </td>

                    <td class="px-5 py-4 text-right">
                        <a href="/staff-management/salary/{{ $p->id }}/payslip"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-xs transition">
                            <i data-lucide="printer" class="w-3.5 h-3.5 text-blue-600"></i>
                            <span>View Payslip</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center">
                        <x-empty-state icon="credit-card" title="No salary payments logged" subtitle="Log payroll disbursements using the form above." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
