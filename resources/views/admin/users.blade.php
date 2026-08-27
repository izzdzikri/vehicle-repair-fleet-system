@extends('layouts.app')
@section('page-title', 'User Management')

@section('content')

@if(session('success'))
<div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
@endif

{{-- Add User --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Add New User</h2>
    @if($errors->any())
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm">
        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
    @endif
    <form method="POST" action="/admin/users" enctype="multipart/form-data"
        class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                class="w-full border rounded px-3 py-2 text-sm" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
            <input type="text" name="username" value="{{ old('username') }}"
                class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="w-full border rounded px-3 py-2 text-sm" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password"
                class="w-full border rounded px-3 py-2 text-sm" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contact No</label>
            <input type="text" name="contact_no" value="{{ old('contact_no') }}"
                class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
            <select name="role" class="w-full border rounded px-3 py-2 text-sm" required>
                <option value="">Select role</option>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
                <option value="corporate">Corporate Client</option>
                <option value="individual">Individual Customer</option>
            </select>
        </div>
        <div class="md:col-span-3">
            <button type="submit"
                class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                Create User
            </button>
        </div>
    </form>
</div>

{{-- Search --}}
<form method="GET" class="mb-4 flex gap-2">
    @if(request('role'))<input type="hidden" name="role" value="{{ request('role') }}">@endif
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <input type="text" name="search" value="{{ $search }}"
        placeholder="Search by name or email..."
        class="flex-1 max-w-md border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Search</button>
    @if($search)
    <a href="/admin/users" class="px-4 py-2 rounded text-sm border text-gray-600 hover:bg-gray-50">Clear</a>
    @endif
</form>

{{-- Filters --}}
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="/admin/users"
        class="px-4 py-2 rounded text-sm {{ !request('role') && !request('status') ? 'bg-blue-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        All ({{ $totalUsers }})
    </a>
    @foreach(['admin','staff','corporate','individual'] as $r)
    <a href="/admin/users?role={{ $r }}"
        class="px-4 py-2 rounded text-sm capitalize {{ request('role') === $r ? 'bg-blue-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        {{ ucfirst($r) }}
    </a>
    @endforeach
    <a href="/admin/users?status=inactive"
        class="px-4 py-2 rounded text-sm {{ request('status') === 'inactive' ? 'bg-red-500 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
        Inactive
    </a>
</div>

{{-- User List --}}
<div class="bg-white rounded-lg shadow p-6">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-2 pr-4">Name</th>
                <th class="pb-2 pr-4">Email</th>
                <th class="pb-2 pr-4">Contact</th>
                <th class="pb-2 pr-4">Role</th>
                <th class="pb-2 pr-4">Status</th>
                <th class="pb-2 pr-4">Joined</th>
                <th class="pb-2">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr class="border-b hover:bg-gray-50 {{ $user->status === 'inactive' ? 'opacity-60' : '' }}">
                <td class="py-3 pr-4">
                    <a href="/admin/users/{{ $user->id }}"
                        class="font-medium text-blue-600 hover:underline">{{ $user->name }}</a>
                    @if($user->username)
                    <p class="text-xs text-gray-400">@ {{ $user->username }}</p>
                    @endif
                </td>
                <td class="py-3 pr-4 text-gray-500">{{ $user->email }}</td>
                <td class="py-3 pr-4 text-gray-500">{{ $user->contact_no ?? '—' }}</td>
                <td class="py-3 pr-4">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $user->role === 'admin'     ? 'bg-purple-100 text-purple-700' :
                          ($user->role === 'staff'     ? 'bg-blue-100 text-blue-700' :
                          ($user->role === 'corporate' ? 'bg-yellow-100 text-yellow-700' :
                          'bg-green-100 text-green-700')) }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
                <td class="py-3 pr-4">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </td>
                <td class="py-3 pr-4 text-gray-500">{{ $user->created_at->format('d M Y') }}</td>
                <td class="py-3">
                    <div class="flex items-center gap-2">
                        <a href="/admin/users/{{ $user->id }}"
                            class="text-blue-600 hover:underline text-xs">View</a>
                        @if($user->role === 'staff')
                        <button onclick="
                            const row = document.getElementById('specialty-row-{{ $user->id }}');
                            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';"
                            class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200">
                            Skills
                        </button>
                        @endif
                        @if($user->id !== auth()->id())
                        <form method="POST" action="/admin/users/{{ $user->id }}/toggle" class="inline">
                            @csrf @method('PATCH')
                            <button class="text-xs {{ $user->status === 'active' ? 'text-orange-500 hover:text-orange-700' : 'text-green-600 hover:text-green-800' }}">
                                {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <form method="POST" action="/admin/users/{{ $user->id }}"
                            onsubmit="return confirm('Delete {{ $user->name }}?')" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-xs">Delete</button>
                        </form>
                        @else
                        <span class="text-gray-400 text-xs italic">You</span>
                        @endif
                    </div>
                </td>
            </tr>
            @if($user->role === 'staff')
            {{-- Collapsible specialties row --}}
            <tr class="bg-gray-50 border-b" id="specialty-row-{{ $user->id }}" style="display: none;">
                <td colspan="7" class="px-4 py-3">
                    <form method="POST" action="/admin/users/{{ $user->id }}/specialties"
                        class="flex items-start gap-4 flex-wrap">
                        @csrf
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-600 mb-2">
                                Specialties for {{ $user->name }}
                                <span class="text-gray-400 font-normal">(select all job types this staff can handle)</span>
                            </p>
                            @if(isset($jobTypes) && $jobTypes->count())
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach($jobTypes as $jt)
                                <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="specialties[]"
                                        value="{{ $jt->id }}"
                                        {{ is_array($user->specialties) && in_array($jt->id, $user->specialties) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600">
                                    {{ $jt->name }}
                                    <span class="text-gray-400">({{ $jt->category }})</span>
                                </label>
                                @endforeach
                            </div>
                            @else
                            <p class="text-sm text-gray-400">No job types defined. Please create job types first.</p>
                            @endif
                        </div>
                        <button type="submit"
                            class="bg-blue-600 text-white px-4 py-1.5 rounded text-xs hover:bg-blue-700 shrink-0 mt-4">
                            Save Specialties
                        </button>
                    </form>
                </td>
            </tr>
            @endif
            @empty
            <tr><td colspan="7" class="py-6 text-center text-gray-400">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())
    <div class="mt-4 pt-4 border-t">
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection