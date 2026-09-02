@extends('layouts.app')
@section('page-title', 'Staff Salary')

@section('content')

@if(auth()->user()->role === 'admin')
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="/admin/staff-management/attendance" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Attendance</a>
    <a href="/admin/staff-management/leave" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Leave Requests</a>
    <a href="/admin/staff-management/performance" class="px-4 py-2 rounded text-sm font-medium bg-white border text-gray-600 hover:bg-gray-50">Performance</a>
    <a href="/staff-management/salary" class="px-4 py-2 rounded text-sm font-medium bg-blue-600 text-white">Salary</a>
</div>
@else
<div class="mb-4">
    <h2 class="text-lg font-semibold text-gray-700">Salary Payments</h2>
    <p class="text-xs text-gray-400 mt-1">Log and review staff salary payments.</p>
</div>
@endif

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Log Salary Payment</h2>
    <form method="POST" action="/staff-management/salary"
        x-data="{
            staffId: '',
            amount: '',
            salaries: {{ $staff->mapWithKeys(fn($s) => [$s->id => $s->monthly_salary])->toJson() }},
            onStaffChange() {
                if (this.salaries[this.staffId]) {
                    this.amount = this.salaries[this.staffId];
                }
            }
        }"
        class="grid grid-cols-1 md:grid-cols-5 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Staff *</label>
            <select name="staff_id" x-model="staffId" @change="onStaffChange()"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">Select staff</option>
                @foreach($staff as $s)
                <option value="{{ $s->id }}">{{ $s->name }}{{ $s->monthly_salary ? ' (RM '.number_format($s->monthly_salary,2).'/mo)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Period *</label>
            <input type="month" name="period"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount (RM) *</label>
            <input type="number" name="amount" x-model="amount" step="0.01" min="0.01"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
            <select name="method" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Cash</option>
                <option value="cheque">Cheque</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Paid On *</label>
            <input type="date" name="paid_at" value="{{ now()->toDateString() }}"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div class="md:col-span-5">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded text-sm hover:bg-blue-700">
                Log Payment
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-700">
            Payment History <span class="text-sm font-normal text-gray-400 ml-1">{{ $payments->count() }} records</span>
        </h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">Staff</th>
                <th class="px-4 py-3">Period</th>
                <th class="px-4 py-3">Amount</th>
                <th class="px-4 py-3">Method</th>
                <th class="px-4 py-3">Paid On</th>
                <th class="px-4 py-3">Recorded By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $p->staff->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500">{{ \Carbon\Carbon::createFromFormat('Y-m', $p->period)->format('F Y') }}</td>
                <td class="px-4 py-3 font-medium text-green-700">RM {{ number_format($p->amount, 2) }}</td>
                <td class="px-4 py-3 text-gray-500">{{ ucfirst(str_replace('_',' ',$p->method)) }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $p->paid_at->format('d M Y') }}</td>
                <td class="px-4 py-3 text-gray-400 text-xs">{{ $p->recorder->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="py-8 text-center text-gray-400">No salary payments recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection