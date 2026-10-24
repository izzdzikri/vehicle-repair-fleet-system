@extends('layouts.app')
@section('page-title', 'My Salary & Payslips')

@section('content')

@php
    $actionPrefix = auth()->user()->role === 'coordinator' ? '/coordinator' : '/staff';
    $user = auth()->user();
@endphp

{{-- Subnavigation Tabs for Staff & Coordinator --}}
<div class="flex gap-2 mb-6 flex-wrap">
    <a href="{{ $actionPrefix }}/staff-management/attendance" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">My Attendance</a>
    <a href="{{ $actionPrefix }}/staff-management/leave" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">My Leave Requests</a>
    <a href="{{ $actionPrefix }}/staff-management/salary" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white shadow-xs">My Salary & Payslips</a>
</div>

{{-- Header & Salary Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Contract Monthly Salary</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="wallet" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-gray-900 tracking-tight">
                RM {{ number_format($user->monthly_salary ?? 0, 2) }}
            </p>
            <p class="text-xs text-gray-400 font-medium mt-1">
                {{ $user->staff_role ?? ucfirst($user->role) }} base compensation
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-emerald-200/80 shadow-xs p-5 bg-emerald-50/20 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Total Disbursed ({{ now()->year }})</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-emerald-700 tracking-tight">
                RM {{ number_format($currentYearTotal, 2) }}
            </p>
            <p class="text-xs text-emerald-600 font-medium mt-1">cumulative net pay this year</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Payslip Vouchers</span>
            <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center">
                <i data-lucide="receipt" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-gray-900 tracking-tight">
                {{ $payments->count() }}
            </p>
            <p class="text-xs text-gray-400 font-medium mt-1">verified payroll records</p>
        </div>
    </div>
</div>

{{-- Personal Payslip Table --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center flex-wrap gap-2">
        <div>
            <h2 class="text-base font-bold text-gray-900 tracking-tight">My Historical Salary Vouchers</h2>
            <p class="text-xs text-gray-500 mt-0.5">Formal monthly remuneration slips issued by Teraju Setia Enterprise</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
            {{ $payments->count() }} vouchers
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                    <th class="px-5 py-3.5">Voucher Reference</th>
                    <th class="px-5 py-3.5">Payroll Month</th>
                    <th class="px-5 py-3.5">Gross & Deductions</th>
                    <th class="px-5 py-3.5">Net Disbursed</th>
                    <th class="px-5 py-3.5">Disbursement Mode & Date</th>
                    <th class="px-5 py-3.5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $p)
                <tr class="hover:bg-gray-50/60 transition">
                    <td class="px-5 py-4">
                        <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                            {{ $p->slip_number }}
                        </span>
                    </td>

                    <td class="px-5 py-4 text-xs font-bold text-gray-900">
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
                            class="inline-flex items-center gap-1.5 text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 px-3.5 py-1.5 rounded-lg shadow-xs transition">
                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                            <span>View / Print Payslip</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center">
                        <x-empty-state icon="credit-card" title="No payslips recorded yet" subtitle="When management processes monthly salary disbursements, your printable vouchers will appear here." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
