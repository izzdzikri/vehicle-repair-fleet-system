@extends('layouts.app')
@section('page-title', 'Company Management')

@section('content')

@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
    class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm border border-green-200">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm border border-red-200">
    {{ session('error') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT COLUMN: Company Info + PICs --}}
    <div class="space-y-6">

        {{-- Company Info --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                    <i data-lucide="building-2" class="w-6 h-6 text-blue-600"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-gray-800">{{ $company->name ?? '—' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Reg No: {{ $company->registration_no ?? '—' }}</p>
                    <p class="text-xs text-gray-500">{{ $company->address ?? '—' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $company->contact_email ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $company->contact_phone ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- PICs List --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-3 flex-wrap gap-2">
                <h2 class="text-base font-semibold text-gray-700">Persons In Charge</h2>
                <div class="flex gap-1 text-xs flex-wrap">
                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                        Primary: {{ $pics->where('pic_role','primary')->count() }}/2
                    </span>
                    <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                        Viewer: {{ $pics->where('pic_role','secondary')->count() }}/10
                    </span>
                    <span class="bg-purple-100 text-purple-600 px-2 py-0.5 rounded-full">
                        Total: {{ $pics->count() }}/12
                    </span>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($pics->sortBy(fn($p) => $p->pic_role === 'primary' ? 0 : 1) as $pic)
                <div class="flex items-center gap-3 p-3 border rounded-lg
                    {{ $pic->id === auth()->id() ? 'border-blue-300 bg-blue-50' : 'border-gray-200' }}">
                    <img src="{{ $pic->avatar_url }}"
                        class="w-10 h-10 rounded-full object-cover shrink-0 border-2
                        {{ $pic->pic_role === 'primary' ? 'border-blue-400' : 'border-gray-200' }}">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1 flex-wrap">
                            <p class="text-sm font-medium text-gray-800">{{ $pic->name }}</p>
                            @if($pic->id === auth()->id())
                            <span class="text-xs bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded-full">You</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400">{{ $pic->email }}</p>
                        @if($pic->contact_no)
                        <p class="text-xs text-gray-400">{{ $pic->contact_no }}</p>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap
                            {{ $pic->pic_role === 'primary'
                                ? 'bg-blue-100 text-blue-700'
                                : 'bg-gray-100 text-gray-500' }}">
                            {{ $pic->pic_role === 'primary' ? '★ Primary' : '◎ Viewer' }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full whitespace-nowrap
                            {{ $pic->status === 'active'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($pic->status) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Role explanation --}}
            <div class="mt-4 p-3 bg-gray-50 rounded-lg border text-xs text-gray-500 space-y-1.5">
                <p>
                    <span class="font-semibold text-blue-600">★ Primary (max 2)</span>
                    — Full access. Can book appointments, add vehicles, and submit account requests.
                </p>
                <p>
                    <span class="font-semibold text-gray-500">◎ Viewer / Secondary (max 10)</span>
                    — Read-only. Can view fleet, appointments, alerts, and service history. Cannot book or submit changes.
                </p>
                <p class="text-gray-400">Maximum 12 PICs per company. Admin approval required for changes.</p>
            </div>
        </div>

    </div>

    {{-- MIDDLE COLUMN: Account Management --}}
    <div class="lg:col-span-1">

        @if(auth()->user()->isPrimaryPic())
        <div class="bg-white rounded-lg shadow p-6" x-data="{ tab: 'add' }">
            <h2 class="text-base font-semibold text-gray-700 mb-4">Account Management</h2>

            {{-- Tabs --}}
            <div class="flex gap-2 mb-5 flex-wrap">
                <button @click="tab='add'"
                    :class="tab==='add' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600'"
                    class="px-4 py-1.5 rounded text-sm font-medium">
                    Add New PIC
                </button>
                <button @click="tab='remove'"
                    :class="tab==='remove' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600'"
                    class="px-4 py-1.5 rounded text-sm font-medium">
                    Remove PIC
                </button>
                <button @click="tab='close'"
                    :class="tab==='close' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600'"
                    class="px-4 py-1.5 rounded text-sm font-medium">
                    Close Account
                </button>
            </div>

            {{-- Add PIC --}}
            <div x-show="tab==='add'">
                @php
                    $primaryCount   = $pics->where('pic_role','primary')->count();
                    $secondaryCount = $pics->where('pic_role','secondary')->count();
                @endphp

                @if($pics->count() >= 12)
                <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-600">
                    Maximum 12 PICs reached. Remove someone before adding.
                </div>
                @else
                <p class="text-xs text-gray-400 mb-3">
                    Submit a request to add a new person under {{ $company->name ?? 'your company' }}.
                    Admin will approve before the account is created.
                </p>
                <form method="POST" action="/client/account-requests" class="space-y-3">
                    @csrf
                    <input type="hidden" name="type" value="add_pic">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="target_name"
                            placeholder="e.g. Encik Farouk bin Aziz"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="target_email"
                            placeholder="farouk@company.com"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="target_phone"
                            placeholder="011-12345678"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select name="pic_role"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                            @if($primaryCount < 2)
                            <option value="primary">★ Primary — Full access ({{ $primaryCount }}/2 used)</option>
                            @endif
                            @if($secondaryCount < 10)
                            <option value="secondary">◎ Viewer — View only ({{ $secondaryCount }}/10 used)</option>
                            @endif
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Primary can book and manage. Viewer can only see fleet data.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                        <input type="text" name="notes"
                            placeholder="e.g. New fleet manager"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                        Submit Request
                    </button>
                </form>
                @endif
            </div>

            {{-- Remove PIC --}}
            <div x-show="tab==='remove'" style="display:none">
                <p class="text-xs text-gray-400 mb-3">
                    Request to remove a person in charge. Admin must approve.
                </p>
                @if($pics->where('id', '!=', auth()->id())->count() === 0)
                <p class="text-sm text-gray-400 italic">No other PICs to remove.</p>
                @else
                <form method="POST" action="/client/account-requests" class="space-y-3">
                    @csrf
                    <input type="hidden" name="type" value="remove_pic">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select PIC to Remove *</label>
                        <select name="target_user_id"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                            <option value="">Select person</option>
                            @foreach($pics->where('id', '!=', auth()->id()) as $pic)
                            <option value="{{ $pic->id }}">
                                {{ $pic->name }}
                                ({{ $pic->pic_role === 'primary' ? '★ Primary' : '◎ Viewer' }})
                                — {{ $pic->email }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                        <input type="text" name="notes"
                            placeholder="e.g. Staff has left the company"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <button type="submit"
                        class="bg-orange-500 text-white px-5 py-2 rounded hover:bg-orange-600 text-sm font-medium">
                        Submit Removal Request
                    </button>
                </form>
                @endif
            </div>

            {{-- Close Account --}}
            <div x-show="tab==='close'" style="display:none">
                <div class="p-4 bg-red-50 border border-red-200 rounded mb-4">
                    <p class="text-sm font-semibold text-red-700">⚠ Warning</p>
                    <p class="text-sm text-red-600 mt-1">
                        This will deactivate all {{ $pics->count() }} user(s) under
                        {{ $company->name ?? 'your company' }}. Requires admin approval.
                    </p>
                </div>
                <form method="POST" action="/client/account-requests" class="space-y-3">
                    @csrf
                    <input type="hidden" name="type" value="close_account">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
                        <textarea name="notes" rows="3"
                            placeholder="Please provide a reason for closing the account..."
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required></textarea>
                    </div>
                    <button type="submit"
                        class="bg-red-600 text-white px-5 py-2 rounded hover:bg-red-700 text-sm font-medium">
                        Submit Close Account Request
                    </button>
                </form>
            </div>
        </div>

        @else
        {{-- Secondary/Viewer sees read-only notice --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-700 mb-3">Account Management</h2>
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500">
                <p class="font-medium text-gray-600 mb-1">◎ You are a Viewer (Secondary PIC)</p>
                <p>Account management requests can only be submitted by a Primary PIC.</p>
                @php $primary = $pics->where('pic_role','primary')->first(); @endphp
                @if($primary)
                <p class="mt-2 text-xs text-gray-400">
                    Primary PIC: <span class="font-medium text-gray-600">{{ $primary->name }}</span>
                    — {{ $primary->email }}
                </p>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- RIGHT COLUMN: Request History --}}
    <div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-700 mb-4">
                Request History
                <span class="text-sm font-normal text-gray-400 ml-1">{{ $requests->count() }} total</span>
            </h2>

            @if($requests->isEmpty())
            <p class="text-sm text-gray-400 text-center py-6">No requests submitted yet.</p>
            @else
            <div class="space-y-3">
                @foreach($requests as $req)
                @php
                    $typeLabel = match($req->type) {
                        'add_pic'        => 'Add New PIC',
                        'remove_pic'     => 'Remove PIC',
                        'close_account'  => 'Close Account',
                        'delete_account' => 'Delete Account',
                        default          => ucfirst($req->type),
                    };
                    $typeColor = match($req->type) {
                        'add_pic'        => 'bg-green-100 text-green-700',
                        'remove_pic'     => 'bg-orange-100 text-orange-700',
                        'close_account'  => 'bg-red-100 text-red-700',
                        default          => 'bg-gray-100 text-gray-600',
                    };
                    $statusColor = match($req->status) {
                        'pending'  => 'bg-yellow-100 text-yellow-700',
                        'approved' => 'bg-green-100 text-green-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        default    => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <div class="border rounded-lg p-3
                    {{ $req->status === 'pending' ? 'border-yellow-300 bg-yellow-50' : 'border-gray-200' }}">
                    <div class="flex items-start justify-between gap-2 flex-wrap mb-1">
                        <div class="flex gap-1 flex-wrap">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $typeColor }}">
                                {{ $typeLabel }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusColor }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap">
                            {{ $req->created_at->format('d M Y') }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">
                        By: <span class="font-medium">{{ $req->requester->name ?? '—' }}</span>
                    </p>
                    @if($req->target_name)
                    <p class="text-xs text-gray-600">
                        For: <span class="font-medium">{{ $req->target_name }}</span>
                        @if($req->target_email)
                        <span class="text-gray-400">({{ $req->target_email }})</span>
                        @endif
                    </p>
                    @endif
                    @if($req->targetUser)
                    <p class="text-xs text-red-600">
                        Remove: {{ $req->targetUser->name }}
                    </p>
                    @endif
                    @if($req->notes)
                    <p class="text-xs text-gray-400 mt-1 italic">{{ $req->notes }}</p>
                    @endif
                    @if($req->status === 'pending')
                    <p class="text-xs text-yellow-600 font-medium mt-1">Awaiting admin approval</p>
                    @endif
                    @if($req->status === 'approved' && $req->type === 'add_pic')
                    <p class="text-xs text-green-600 mt-1">
                        ✓ Account created. Temp password: <span class="font-mono">password123</span>
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

</div>

@endsection