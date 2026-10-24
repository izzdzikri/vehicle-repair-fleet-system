@extends('layouts.app')
@section('page-title', 'Payslip ' . $salaryPayment->slip_number)

@section('content')

@php
    $base = (float) ($salaryPayment->base_salary ?: $salaryPayment->amount);
    $allow = (float) $salaryPayment->allowances;
    $deduct = (float) $salaryPayment->deductions;
    $gross = $base + $allow;
    $net = (float) $salaryPayment->amount;
    $periodCarbon = \Carbon\Carbon::createFromFormat('Y-m', $salaryPayment->period);
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    {{-- Screen Action Bar (Hidden when printing) --}}
    <div class="flex justify-between items-center no-print flex-wrap gap-3">
        <a href="javascript:history.back()"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Back to Payroll</span>
        </a>

        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                class="flex items-center gap-1.5 text-xs font-semibold bg-slate-900 text-white px-4 py-2 rounded-xl hover:bg-slate-800 transition shadow-xs">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print Payslip Voucher</span>
            </button>
        </div>
    </div>

    {{-- Formal Payslip Document Card --}}
    <div class="bg-white rounded-2xl border border-gray-200/90 shadow-sm p-8 sm:p-10 text-gray-800 print:border-0 print:shadow-none print:p-0">
        
        {{-- Header & Corporate Branding --}}
        <div class="flex justify-between items-start pb-6 border-b-2 border-gray-900 flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">TERAJU SETIA ENTERPRISE</h1>
                <p class="text-xs font-semibold text-gray-500 mt-0.5">Commercial Fleet Maintenance & Automotive Workshop Services</p>
                <p class="text-xs text-gray-500 mt-1">
                    No. 1, Jalan Malinja 2, Taman Perindustrian Matahari, 86000 Kluang, Johor Darul Ta'zim<br>
                    SSM Reg: <span class="font-mono font-semibold">JM-0918234-X</span> &bull; Tel: <span class="font-mono">07-787 8728</span> &bull; Email: accounts@teraju.my
                </p>
            </div>

            <div class="text-right">
                <span class="inline-block bg-slate-900 text-white px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wider">
                    PAYSLIP VOUCHER
                </span>
                <p class="font-mono text-sm font-extrabold text-gray-900 mt-2">{{ $salaryPayment->slip_number }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Issue Date: {{ $salaryPayment->paid_at->format('d M Y') }}</p>
            </div>
        </div>

        {{-- Employee & Payment Period Details Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-6 border-b border-gray-100 text-xs">
            <div>
                <span class="text-gray-400 font-bold uppercase tracking-wider block text-[10px]">Employee Name</span>
                <p class="font-extrabold text-sm text-gray-900 mt-1">{{ $salaryPayment->staff->name }}</p>
                <p class="text-gray-500 mt-0.5">ID: #EMP-{{ str_pad($salaryPayment->staff->id, 4, '0', STR_PAD_LEFT) }}</p>
            </div>

            <div>
                <span class="text-gray-400 font-bold uppercase tracking-wider block text-[10px]">Staff Designation</span>
                <p class="font-bold text-gray-900 mt-1">
                    {{ $salaryPayment->staff->staff_role ?? ($salaryPayment->staff->role === 'coordinator' ? 'Service Coordinator' : 'Workshop Mechanic') }}
                </p>
                <p class="text-gray-500 mt-0.5 uppercase tracking-wider text-[10px] font-bold text-blue-700">
                    Dept: {{ ucfirst($salaryPayment->staff->role) }}
                </p>
            </div>

            <div>
                <span class="text-gray-400 font-bold uppercase tracking-wider block text-[10px]">Pay Period Month</span>
                <p class="font-extrabold text-sm text-blue-900 mt-1">{{ $periodCarbon->format('F Y') }}</p>
                <p class="text-gray-500 mt-0.5">Monthly Payroll</p>
            </div>

            <div>
                <span class="text-gray-400 font-bold uppercase tracking-wider block text-[10px]">Disbursement Mode</span>
                <p class="font-bold text-gray-900 mt-1 capitalize">{{ str_replace('_', ' ', $salaryPayment->method) }}</p>
                <p class="text-gray-500 mt-0.5">{{ $salaryPayment->paid_at->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- Earnings & Deductions Tables --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-6 border-b border-gray-100 text-xs">
            
            {{-- Earnings (Pendapatan) --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-200 font-bold text-gray-800 uppercase tracking-wider text-[11px] flex justify-between">
                    <span>Earnings (Pendapatan)</span>
                    <span>Amount (RM)</span>
                </div>
                <div class="p-4 space-y-2.5">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700 font-medium">Basic Monthly Salary</span>
                        <span class="font-mono font-bold text-gray-900">{{ number_format($base, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700 font-medium">Allowances & Overtime</span>
                        <span class="font-mono font-bold text-gray-900">{{ number_format($allow, 2) }}</span>
                    </div>
                    <div class="pt-2 border-t border-gray-100 flex justify-between items-center font-bold text-gray-900">
                        <span>Total Gross Earnings</span>
                        <span class="font-mono text-sm text-blue-700">RM {{ number_format($gross, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Deductions (Potongan) --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-200 font-bold text-gray-800 uppercase tracking-wider text-[11px] flex justify-between">
                    <span>Deductions (Potongan)</span>
                    <span>Amount (RM)</span>
                </div>
                <div class="p-4 space-y-2.5">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700 font-medium">Statutory / Advance Deductions</span>
                        <span class="font-mono font-bold text-gray-900">{{ number_format($deduct, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-gray-400">
                        <span>Other Deductions</span>
                        <span class="font-mono">0.00</span>
                    </div>
                    <div class="pt-2 border-t border-gray-100 flex justify-between items-center font-bold text-gray-900">
                        <span>Total Deductions</span>
                        <span class="font-mono text-sm text-rose-600">RM {{ number_format($deduct, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Net Pay Highlight Box --}}
        <div class="my-6 p-5 bg-slate-900 text-white rounded-xl flex items-center justify-between flex-wrap gap-4">
            <div>
                <span class="text-xs uppercase tracking-widest text-slate-400 font-bold block">Net Salary Payable (Gaji Bersih Diterima)</span>
                <p class="text-xs text-slate-300 mt-0.5">Disbursed via {{ strtoupper(str_replace('_', ' ', $salaryPayment->method)) }}</p>
                @if($salaryPayment->notes)
                <p class="text-[11px] text-blue-300 font-mono mt-1">Ref / Notes: {{ $salaryPayment->notes }}</p>
                @endif
            </div>

            <div class="text-right">
                <span class="text-3xl sm:text-4xl font-black font-mono text-emerald-400 tracking-tight">
                    RM {{ number_format($net, 2) }}
                </span>
            </div>
        </div>

        {{-- Legal Acknowledgment & Signature Blocks --}}
        <div class="grid grid-cols-2 gap-8 pt-8 mt-6 border-t border-gray-200 text-xs">
            <div>
                <p class="text-gray-400 uppercase tracking-wider text-[10px] font-bold mb-12">Authorized By (Management / Accounts):</p>
                <div class="border-t border-gray-300 pt-2">
                    <p class="font-bold text-gray-900">{{ $salaryPayment->recorder->name ?? 'Ahmad Firdaus bin Ismail' }}</p>
                    <p class="text-gray-500 text-[11px]">Teraju Setia Enterprise &bull; Date: {{ $salaryPayment->paid_at->format('d M Y') }}</p>
                </div>
            </div>

            <div>
                <p class="text-gray-400 uppercase tracking-wider text-[10px] font-bold mb-12">Received & Acknowledged By Employee:</p>
                <div class="border-t border-gray-300 pt-2">
                    <p class="font-bold text-gray-900">{{ $salaryPayment->staff->name }}</p>
                    <p class="text-gray-500 text-[11px]">Signature &bull; Date: ___________________</p>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center text-[10px] text-gray-400 border-t border-gray-100 pt-4">
            This is a computer-generated official payroll voucher from Teraju Setia Enterprise Workshop Management System.
        </div>
    </div>
</div>
@endsection
