@extends('layouts.app')
@section('page-title', 'Company Management')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT COLUMN: Company Info + PICs --}}
    <div class="space-y-6">

        {{-- Company Info Card --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <i data-lucide="building-2" class="w-6 h-6"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900 truncate">{{ $company->name ?? '—' }}</h2>
                    <p class="text-xs font-semibold text-gray-500 mt-0.5">Reg No: {{ $company->registration_no ?? '—' }}</p>
                    <p class="text-xs text-gray-600 mt-2 flex items-start gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-gray-400 shrink-0 mt-0.5"></i>
                        <span>{{ $company->address ?? 'No physical address specified' }}</span>
                    </p>
                    <div class="mt-3 pt-3 border-t border-gray-100 space-y-1">
                        <p class="text-xs text-gray-600 flex items-center gap-1.5">
                            <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-400 shrink-0"></i>
                            <span>{{ $company->contact_email ?? '—' }}</span>
                        </p>
                        <p class="text-xs text-gray-600 flex items-center gap-1.5">
                            <i data-lucide="phone" class="w-3.5 h-3.5 text-gray-400 shrink-0"></i>
                            <span class="font-mono">{{ $company->contact_phone ?? '—' }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- PICs List --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
            <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Persons In Charge</h2>
                    <p class="text-xs font-medium text-gray-500">Authorized corporate accounts</p>
                </div>
                <div class="flex gap-1.5 text-xs flex-wrap">
                    <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-md font-bold">
                        Primary: {{ $pics->where('pic_role','primary')->count() }}/2
                    </span>
                    <span class="bg-gray-100 text-gray-700 border border-gray-200 px-2 py-0.5 rounded-md font-bold">
                        Viewer: {{ $pics->where('pic_role','secondary')->count() }}/10
                    </span>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($pics->sortBy(fn($p) => $p->pic_role === 'primary' ? 0 : 1) as $pic)
                <div class="flex items-center gap-3 p-3.5 border rounded-xl transition
                    {{ $pic->id === auth()->id() ? 'border-blue-300 bg-blue-50/40' : 'border-gray-200/80 hover:bg-gray-50/50' }}">
                    <img src="{{ $pic->avatar_url }}"
                        class="w-10 h-10 rounded-full object-cover shrink-0 border-2
                        {{ $pic->pic_role === 'primary' ? 'border-blue-400' : 'border-gray-200' }}">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <p class="text-sm font-bold text-gray-900">{{ $pic->name }}</p>
                            @if($pic->id === auth()->id())
                            <span class="text-xs bg-blue-600 text-white font-bold px-1.5 py-0.2 rounded">You</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 truncate">{{ $pic->email }}</p>
                        @if($pic->contact_no)
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $pic->contact_no }}</p>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded-full font-bold whitespace-nowrap
                            {{ $pic->pic_role === 'primary'
                                ? 'bg-blue-50 text-blue-700 border border-blue-200'
                                : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                            {{ $pic->pic_role === 'primary' ? '★ Primary' : '◎ Viewer' }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold whitespace-nowrap
                            {{ $pic->status === 'active'
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'bg-rose-50 text-rose-700' }}">
                            {{ ucfirst($pic->status) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Role explanation --}}
            <div class="mt-5 p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-600 space-y-2">
                <p>
                    <span class="font-bold text-blue-700">★ Primary (Max 2):</span>
                    Full access to book appointments, manage fleet vehicles, and submit requests.
                </p>
                <p>
                    <span class="font-bold text-gray-700">◎ Viewer / Secondary (Max 10):</span>
                    Read-only monitoring access to fleet statistics, schedules, and alerts.
                </p>
            </div>
        </div>

    </div>

    {{-- MIDDLE COLUMN: Account Management --}}
    <div class="lg:col-span-1">

        @if(auth()->user()->isPrimaryPic())
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6" x-data="{ tab: 'add' }">
            <h2 class="text-base font-bold text-gray-900 mb-1">Account Management</h2>
            <p class="text-xs font-medium text-gray-500 mb-4">Submit requests for administrator approval</p>

            {{-- Tabs --}}
            <div class="flex gap-1.5 mb-5 p-1 bg-gray-100 rounded-lg">
                <button @click="tab='add'"
                    :class="tab==='add' ? 'bg-white text-gray-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="flex-1 py-1.5 rounded-md text-xs transition text-center">
                    Add PIC
                </button>
                <button @click="tab='remove'"
                    :class="tab==='remove' ? 'bg-white text-amber-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="flex-1 py-1.5 rounded-md text-xs transition text-center">
                    Remove PIC
                </button>
                <button @click="tab='close'"
                    :class="tab==='close' ? 'bg-white text-rose-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="flex-1 py-1.5 rounded-md text-xs transition text-center">
                    Close Account
                </button>
            </div>

            {{-- Add PIC Form --}}
            <div x-show="tab==='add'">
                @php
                    $primaryCount   = $pics->where('pic_role','primary')->count();
                    $secondaryCount = $pics->where('pic_role','secondary')->count();
                @endphp

                @if($pics->count() >= 12)
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-sm font-medium text-rose-700">
                    Maximum limit of 12 Persons In Charge reached. You must remove an existing member before adding a new one.
                </div>
                @else
                <p class="text-xs text-gray-500 mb-4">
                    Submit a request to add a new person under <strong class="text-gray-800">{{ $company->name ?? 'your company' }}</strong>. An administrator will verify and approve the account.
                </p>
                <form method="POST" action="/client/account-requests" class="space-y-3.5">
                    @csrf
                    <input type="hidden" name="type" value="add_pic">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="target_name"
                            placeholder="e.g. Encik Farouk bin Aziz"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Email Address *</label>
                        <input type="email" name="target_email"
                            placeholder="farouk@company.com"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="target_phone"
                            placeholder="011-12345678"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Access Role *</label>
                        <select name="pic_role"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                            @if($primaryCount < 2)
                            <option value="primary">★ Primary — Full access ({{ $primaryCount }}/2 assigned)</option>
                            @endif
                            @if($secondaryCount < 10)
                            <option value="secondary">◎ Viewer — View only ({{ $secondaryCount }}/10 assigned)</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Remarks (Optional)</label>
                        <input type="text" name="notes"
                            placeholder="e.g. Appointed new logistics manager"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <button type="submit"
                        class="w-full bg-slate-900 text-white px-5 py-2.5 rounded-xl hover:bg-slate-800 text-xs font-bold shadow-xs mt-2 transition">
                        Submit PIC Request
                    </button>
                </form>
                @endif
            </div>

            {{-- Remove PIC Form --}}
            <div x-show="tab==='remove'" style="display:none">
                <p class="text-xs text-gray-500 mb-4">
                    Select an authorized PIC to remove from {{ $company->name ?? 'your company' }}. Requires administrative confirmation.
                </p>
                @if($pics->where('id', '!=', auth()->id())->count() === 0)
                <p class="text-sm font-medium text-gray-500 italic py-4">No other PIC accounts available to remove.</p>
                @else
                <form method="POST" action="/client/account-requests" class="space-y-3.5">
                    @csrf
                    <input type="hidden" name="type" value="remove_pic">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Select PIC to Remove *</label>
                        <select name="target_user_id"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required>
                            <option value="">Select individual...</option>
                            @foreach($pics->where('id', '!=', auth()->id()) as $pic)
                            <option value="{{ $pic->id }}">
                                {{ $pic->name }} ({{ $pic->pic_role === 'primary' ? 'Primary' : 'Viewer' }}) — {{ $pic->email }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Reason for Removal (Optional)</label>
                        <input type="text" name="notes"
                            placeholder="e.g. Personnel no longer with organization"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <button type="submit"
                        class="w-full bg-amber-600 text-white px-5 py-2.5 rounded-lg hover:bg-amber-700 text-sm font-semibold shadow-xs mt-2">
                        Submit Removal Request
                    </button>
                </form>
                @endif
            </div>

            {{-- Close Account --}}
            <div x-show="tab==='close'" style="display:none">
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl mb-4">
                    <p class="text-sm font-bold text-rose-800 flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i> Account Closure Warning
                    </p>
                    <p class="text-xs text-rose-700 mt-1">
                        Submitting this request will deactivate access for all {{ $pics->count() }} corporate member(s) under {{ $company->name ?? 'your organization' }}.
                    </p>
                </div>
                <form method="POST" action="/client/account-requests" class="space-y-3.5">
                    @csrf
                    <input type="hidden" name="type" value="close_account">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Reason for Closure *</label>
                        <textarea name="notes" rows="3"
                            placeholder="Please provide justification for closing corporate fleet account..."
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            required></textarea>
                    </div>
                    <button type="submit"
                        class="w-full bg-rose-600 text-white px-5 py-2.5 rounded-lg hover:bg-rose-700 text-sm font-semibold shadow-xs">
                        Confirm Account Closure Request
                    </button>
                </form>
            </div>
        </div>

        @else
        {{-- Secondary/Viewer sees read-only notice --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
            <h2 class="text-base font-bold text-gray-900 mb-2">Account Management</h2>
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-600">
                <p class="font-bold text-gray-900 mb-1">◎ Read-Only Access (Viewer PIC)</p>
                <p class="text-xs text-gray-500">Corporate account modifications and PIC requests can only be performed by a designated Primary PIC.</p>
                @php $primary = $pics->where('pic_role','primary')->first(); @endphp
                @if($primary)
                <div class="mt-3 pt-3 border-t border-gray-200 text-xs">
                    <span class="text-gray-500">Primary Contact:</span>
                    <span class="font-bold text-gray-800 ml-1">{{ $primary->name }}</span>
                    <span class="text-gray-500 font-mono block mt-0.5">{{ $primary->email }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- RIGHT COLUMN: Request History --}}
    <div>
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-bold text-gray-900">Request History</h2>
                <span class="text-xs font-semibold px-2 py-0.5 bg-gray-100 text-gray-600 rounded-md">{{ $requests->count() }} total</span>
            </div>

            @if($requests->isEmpty())
            <div class="text-center py-8">
                <i data-lucide="history" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
                <p class="text-sm font-medium text-gray-500">No account requests submitted yet.</p>
            </div>
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
                        'add_pic'        => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                        'remove_pic'     => 'bg-amber-50 text-amber-700 border border-amber-200',
                        'close_account'  => 'bg-rose-50 text-rose-700 border border-rose-200',
                        default          => 'bg-gray-100 text-gray-700 border border-gray-200',
                    };
                    $statusColor = match($req->status) {
                        'pending'  => 'bg-amber-50 text-amber-700 border border-amber-200',
                        'approved' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                        'rejected' => 'bg-rose-50 text-rose-700 border border-rose-200',
                        default    => 'bg-gray-100 text-gray-700 border border-gray-200',
                    };
                @endphp
                <div class="border rounded-xl p-3.5 transition
                    {{ $req->status === 'pending' ? 'border-amber-300 bg-amber-50/40' : 'border-gray-200/80 bg-white' }}">
                    <div class="flex items-start justify-between gap-2 flex-wrap mb-1.5">
                        <div class="flex gap-1.5 flex-wrap">
                            <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ $typeColor }}">
                                {{ $typeLabel }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ $statusColor }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </div>
                        <span class="text-xs font-medium text-gray-400 whitespace-nowrap">
                            {{ $req->created_at->format('d M Y') }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">
                        Requested by: <span class="font-bold text-gray-800">{{ $req->requester->name ?? '—' }}</span>
                    </p>
                    @if($req->target_name)
                    <p class="text-xs text-gray-600">
                        Target: <span class="font-bold text-gray-800">{{ $req->target_name }}</span>
                        @if($req->target_email)
                        <span class="text-gray-500">({{ $req->target_email }})</span>
                        @endif
                    </p>
                    @endif
                    @if($req->targetUser)
                    <p class="text-xs text-rose-700 font-medium">
                        Removal Target: {{ $req->targetUser->name }}
                    </p>
                    @endif
                    @if($req->notes)
                    <p class="text-xs text-gray-500 mt-1 italic bg-gray-50 p-1.5 rounded border border-gray-100">
                        "{{ $req->notes }}"
                    </p>
                    @endif
                    @if($req->status === 'pending')
                    <p class="text-xs text-amber-700 font-bold mt-2 flex items-center gap-1">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i> Awaiting administrator approval
                    </p>
                    @endif
                    @if($req->status === 'approved' && $req->type === 'add_pic')
                    <div class="mt-2 p-2 bg-emerald-50 border border-emerald-200 rounded text-xs text-emerald-800">
                        ✓ Account provisioned. Temporary password: <span class="font-mono font-bold bg-white px-1.5 py-0.5 rounded border border-emerald-300">password123</span>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

</div>

@endsection
