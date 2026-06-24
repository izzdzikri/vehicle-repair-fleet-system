@extends('layouts.app')
@section('page-title', 'Account Requests')

@section('content')

@php $pending = $requests->where('status','pending')->count(); @endphp

<div class="flex justify-between items-center mb-6">
    <h2 class="text-lg font-semibold text-gray-700">
        Account Requests
        @if($pending)
        <span class="ml-2 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ $pending }} pending</span>
        @endif
    </h2>
</div>

@if($requests->isEmpty())
<div class="bg-white rounded-lg shadow p-12 text-center text-gray-400">
    <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-200"></i>
    No account requests yet.
</div>
@else
<div class="space-y-3">
    @foreach($requests as $req)
    @php
        $typeLabel = match($req->type) {
            'add_pic'        => 'Add New PIC',
            'remove_pic'     => 'Remove PIC',
            'close_account'  => 'Close Company Account',
            'delete_account' => 'Delete Account',
        };
        $typeColor = match($req->type) {
            'add_pic'        => 'bg-green-100 text-green-700',
            'remove_pic'     => 'bg-orange-100 text-orange-700',
            'close_account'  => 'bg-red-100 text-red-700',
            'delete_account' => 'bg-red-100 text-red-700',
        };
        $statusColor = match($req->status) {
            'pending'  => 'bg-yellow-100 text-yellow-700',
            'approved' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700',
        };
    @endphp
    <div class="bg-white rounded-lg shadow p-5 {{ $req->status === 'pending' ? 'border-l-4 border-yellow-400' : '' }}">
        <div class="flex justify-between items-start flex-wrap gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $typeColor }}">{{ $typeLabel }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusColor }}">{{ ucfirst($req->status) }}</span>
                    <span class="text-xs text-gray-400">{{ $req->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-sm font-medium text-gray-800">
                    Requested by: {{ $req->requester->name ?? '—' }}
                    @if($req->company)
                    <span class="text-gray-400">({{ $req->company->name }})</span>
                    @endif
                </p>

                @if($req->type === 'add_pic')
                <div class="mt-2 text-sm text-gray-600 space-y-0.5">
                    <p>New PIC Name: <span class="font-medium">{{ $req->target_name }}</span></p>
                    <p>Email: <span class="font-medium">{{ $req->target_email }}</span></p>
                    @if($req->target_phone)
                    <p>Phone: {{ $req->target_phone }}</p>
                    @endif
                    @if($req->status === 'approved')
                    <p class="text-xs text-green-600 mt-1">✓ Account created with temporary password: password123</p>
                    @endif
                </div>

                @elseif($req->type === 'remove_pic')
                <div class="mt-2 text-sm text-gray-600">
                    <p>Remove: <span class="font-medium text-red-600">{{ $req->targetUser->name ?? 'User already removed' }}</span></p>
                    <p class="text-xs text-gray-400">{{ $req->targetUser->email ?? '' }}</p>
                </div>

                @elseif($req->type === 'close_account')
                <div class="mt-2 text-sm text-gray-600">
                    <p>Close all accounts under <span class="font-semibold">{{ $req->company->name ?? '—' }}</span></p>
                    <p class="text-xs text-gray-400 mt-0.5">All corporate users under this company will be deactivated.</p>
                </div>

                @elseif($req->type === 'delete_account')
                <div class="mt-2 text-sm text-gray-600">
                    <p>Individual user requesting account deletion.</p>
                </div>
                @endif

                @if($req->notes)
                <p class="text-xs text-gray-500 mt-2 italic">Notes: {{ $req->notes }}</p>
                @endif
            </div>

            @if($req->status === 'pending')
            <div class="flex gap-2">
                <form method="POST" action="/admin/account-requests/{{ $req->id }}/approve">
                    @csrf @method('PATCH')
                    <button class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 font-medium">
                        Approve
                    </button>
                </form>
                <form method="POST" action="/admin/account-requests/{{ $req->id }}/reject">
                    @csrf @method('PATCH')
                    <button class="bg-red-100 text-red-700 px-4 py-2 rounded text-sm hover:bg-red-200 font-medium">
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