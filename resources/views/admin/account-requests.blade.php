@extends('layouts.app')
@section('page-title', 'Account Requests')

@section('content')

@php $pending = $requests->where('status','pending')->count(); @endphp

<div class="flex justify-between items-center mb-6 flex-wrap gap-2">
    <div>
        <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Corporate & Client Account Requests</h2>
        <p class="text-xs font-medium text-gray-500 mt-0.5">Manage identity verification, PIC appointments, and fleet closure requests</p>
    </div>
    @if($pending)
    <span class="text-xs bg-rose-50 text-rose-700 border border-rose-200 px-3 py-1 rounded-full font-bold">
        {{ $pending }} Pending Review
    </span>
    @endif
</div>

@if($requests->isEmpty())
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-12 text-center text-gray-400">
    <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
    <p class="text-sm font-semibold text-gray-600">No account requests found</p>
    <p class="text-xs text-gray-400 mt-1">Requests from corporate managers or customers will appear here.</p>
</div>
@else
<div class="space-y-3.5">
    @foreach($requests as $req)
    @php
        $typeLabel = match($req->type) {
            'add_pic'        => 'Add New PIC',
            'remove_pic'     => 'Remove PIC',
            'close_account'  => 'Close Company Account',
            'delete_account' => 'Delete Account',
            default          => ucfirst($req->type),
        };
        $typeColor = match($req->type) {
            'add_pic'        => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'remove_pic'     => 'bg-amber-50 text-amber-700 border border-amber-200',
            'close_account'  => 'bg-rose-50 text-rose-700 border border-rose-200',
            'delete_account' => 'bg-rose-50 text-rose-700 border border-rose-200',
            default          => 'bg-gray-100 text-gray-700 border border-gray-200',
        };
        $statusColor = match($req->status) {
            'pending'  => 'bg-amber-50 text-amber-700 border border-amber-200',
            'approved' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'rejected' => 'bg-rose-50 text-rose-700 border border-rose-200',
            default    => 'bg-gray-100 text-gray-700 border border-gray-200',
        };
    @endphp
    <div class="bg-white rounded-xl border p-5 shadow-xs transition
        {{ $req->status === 'pending' ? 'border-amber-300 bg-amber-50/20' : 'border-gray-200/80' }}">
        <div class="flex justify-between items-start flex-wrap gap-4">
            <div class="space-y-1.5 flex-1 min-w-[280px]">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-bold {{ $typeColor }}">{{ $typeLabel }}</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-bold {{ $statusColor }}">{{ ucfirst($req->status) }}</span>
                    <span class="text-xs text-gray-400 font-medium">{{ $req->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-sm font-bold text-gray-900">
                    Requested by: {{ $req->requester->name ?? '—' }}
                    @if($req->company)
                    <span class="text-xs font-semibold text-gray-500 font-normal">({{ $req->company->name }})</span>
                    @endif
                </p>

                @if($req->type === 'add_pic')
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-xs text-gray-700 space-y-1 mt-2">
                    <p><span class="text-gray-500">Candidate Name:</span> <strong class="text-gray-900">{{ $req->target_name }}</strong></p>
                    <p><span class="text-gray-500">Email:</span> <strong class="text-gray-900 font-mono">{{ $req->target_email }}</strong></p>
                    @if($req->target_phone)
                    <p><span class="text-gray-500">Phone:</span> <strong class="text-gray-900 font-mono">{{ $req->target_phone }}</strong></p>
                    @endif
                    @if($req->status === 'approved')
                    <p class="text-emerald-700 font-bold mt-1.5">✓ Account provisioned with temporary password: <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-emerald-300">password123</span></p>
                    @endif
                </div>

                @elseif($req->type === 'remove_pic')
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-xs text-gray-700 mt-2">
                    <p><span class="text-gray-500">Target to Remove:</span> <strong class="text-rose-700">{{ $req->targetUser->name ?? 'User already removed' }}</strong></p>
                    <p class="text-gray-400 font-mono mt-0.5">{{ $req->targetUser->email ?? '' }}</p>
                </div>

                @elseif($req->type === 'close_account')
                <div class="p-3 bg-rose-50/50 rounded-lg border border-rose-200 text-xs text-rose-800 mt-2">
                    <p class="font-bold">Close corporate account: {{ $req->company->name ?? '—' }}</p>
                    <p class="text-rose-600 mt-0.5">All PIC user credentials associated with this company will be deactivated.</p>
                </div>

                @elseif($req->type === 'delete_account')
                <div class="p-3 bg-rose-50/50 rounded-lg border border-rose-200 text-xs text-rose-800 mt-2">
                    <p class="font-bold">Individual user requesting permanent account removal.</p>
                </div>
                @endif

                @if($req->notes)
                <p class="text-xs text-gray-600 italic bg-gray-50 p-2 rounded border border-gray-100 mt-2">
                    "{{ $req->notes }}"
                </p>
                @endif
            </div>

            @if($req->status === 'pending')
            <div class="flex items-center gap-2 shrink-0">
                <form method="POST" action="/admin/account-requests/{{ $req->id }}/approve">
                    @csrf @method('PATCH')
                    <button class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 shadow-xs transition">
                        Approve Request
                    </button>
                </form>
                <form method="POST" action="/admin/account-requests/{{ $req->id }}/reject">
                    @csrf @method('PATCH')
                    <button class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-100 shadow-xs transition">
                        Reject
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
