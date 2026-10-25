@extends('layouts.app')
@section('page-title', 'User Management')

@section('content')

<div x-data="{ showAdd: false }">

    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">System Users & Access</h2>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                    {{ $totalUsers }} registered accounts
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Manage administrators, mechanics, coordinators, and corporate fleet accounts</p>
        </div>
        <button @click="showAdd = true"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-xs flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Add New User
        </button>
    </div>

    {{-- Search & Filters --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
        {{-- Search --}}
        <form method="GET" class="flex gap-2 max-w-md w-full">
            @if(request('role'))<input type="hidden" name="role" value="{{ request('role') }}">@endif
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Search by name or email..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
            </div>
            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 transition shadow-xs">Search</button>
            @if($search)
            <a href="/admin/users" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 flex items-center">Clear</a>
            @endif
        </form>

        {{-- Filters --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
            <a href="/admin/users"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ !request('role') && !request('status') ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                All ({{ $totalUsers }})
            </a>
            @foreach(['admin','staff','coordinator','corporate','individual'] as $r)
            <a href="/admin/users?role={{ $r }}"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border capitalize {{ request('role') === $r ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                {{ ucfirst($r) }}
            </a>
            @endforeach
            <a href="/admin/users?status=inactive"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ request('status') === 'inactive' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                Inactive
            </a>
        </div>
    </div>

    {{-- User List --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden mb-6">
        <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                    <th class="px-5 py-3.5">User Identity</th>
                    <th class="px-5 py-3.5">Email Address</th>
                    <th class="px-5 py-3.5">Contact No.</th>
                    <th class="px-5 py-3.5">Assigned Role</th>
                    <th class="px-5 py-3.5">Account Status</th>
                    <th class="px-5 py-3.5">Registered</th>
                    <th class="px-5 py-3.5 text-right w-16">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50/60 transition {{ $user->status === 'inactive' ? 'opacity-60 bg-gray-50/40' : '' }}">
                    <td class="px-5 py-4">
                        <a href="/admin/users/{{ $user->id }}"
                            class="font-bold text-sm text-gray-900 hover:text-blue-600 transition">{{ $user->name }}</a>
                        @if($user->username)
                        <p class="text-xs text-gray-400">@ {{ $user->username }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-xs font-medium text-gray-600">{{ $user->email }}</td>
                    <td class="px-5 py-4 text-xs font-medium text-gray-600 font-mono">{{ $user->contact_no ?? '—' }}</td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold border
                            {{ $user->role === 'admin'       ? 'bg-purple-50 text-purple-700 border-purple-200' :
                              ($user->role === 'staff'       ? 'bg-blue-50 text-blue-700 border-blue-200' :
                              ($user->role === 'coordinator' ? 'bg-cyan-50 text-cyan-700 border-cyan-200' :
                              ($user->role === 'corporate'   ? 'bg-amber-50 text-amber-700 border-amber-200' :
                              'bg-emerald-50 text-emerald-700 border-emerald-200'))) }}">
                            {{ ucfirst($user->role) }}
                        </span>
                        @if($user->role === 'staff' && $user->staff_role_label)
                        <p class="text-xs font-semibold text-gray-500 mt-1">{{ $user->staff_role_label }}</p>
                        @endif
                        @if($user->role === 'staff' && !empty($user->permissions))
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($user->permissions as $perm)
                            <span class="text-[10px] font-semibold bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded border border-gray-200">{{ \App\Models\User::PERMISSIONS[$perm] ?? $perm }}</span>
                            @endforeach
                        </div>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                            {{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            {{ ucfirst($user->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-xs font-medium text-gray-500">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="relative inline-block text-left" x-data="{ open: false, dropUp: false }" @click.outside="open = false">
                            <button @click="open = !open; if(open) { dropUp = ($el.getBoundingClientRect().bottom + 180 > window.innerHeight); }" class="p-1.5 rounded-lg border border-gray-200 hover:bg-gray-100 text-gray-600 transition">
                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                            </button>
                            <div x-show="open" x-transition
                                :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                                class="absolute right-0 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1.5 z-30 text-left"
                                style="display:none">
                                <a href="/admin/users/{{ $user->id }}"
                                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-gray-400"></i> View Profile
                                </a>
                                @if($user->role === 'staff')
                                <button type="button"
                                    @click="open = false; const row = document.getElementById('specialty-row-{{ $user->id }}'); row.style.display = row.style.display === 'none' ? 'table-row' : 'none';"
                                    class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                                    <i data-lucide="wrench" class="w-3.5 h-3.5"></i> Edit Specialties
                                </button>
                                @endif
                                @if($user->id !== auth()->id())
                                <form method="POST" action="/admin/users/{{ $user->id }}/toggle">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold {{ $user->status === 'active' ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
                                        <i data-lucide="{{ $user->status === 'active' ? 'user-x' : 'user-check' }}" class="w-3.5 h-3.5"></i>
                                        {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <form method="POST" action="/admin/users/{{ $user->id }}"
                                    onsubmit="return confirmSubmit(event, {title:'Delete user?', message:'This will permanently delete {{ addslashes($user->name) }} ({{ addslashes($user->email) }}). This cannot be undone.', confirmLabel:'Delete User'})">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 border-t border-gray-100">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i> Delete
                                    </button>
                                </form>
                                @else
                                <p class="px-4 py-2 text-xs text-gray-400 italic border-t border-gray-100">Current User (You)</p>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @if($user->role === 'staff')
                {{-- Collapsible specialties row --}}
                <tr class="bg-gray-50/70 border-b border-gray-100" id="specialty-row-{{ $user->id }}" style="display: none;">
                    <td colspan="7" class="px-5 py-4">
                        <form method="POST" action="/admin/users/{{ $user->id }}/specialties"
                            class="flex items-start gap-4 flex-wrap">
                            @csrf
                            <div class="flex-1">
                                <p class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                    Assigned Specialties for {{ $user->name }}
                                    <span class="text-gray-400 font-normal lowercase">(job types handled by mechanic)</span>
                                </p>
                                @if(isset($jobTypes) && $jobTypes->count())
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    @foreach($jobTypes as $jt)
                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer bg-white p-2 rounded-lg border border-gray-200">
                                        <input type="checkbox" name="specialties[]"
                                            value="{{ $jt->id }}"
                                            {{ is_array($user->specialties) && in_array($jt->id, $user->specialties) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="font-medium">{{ $jt->name }}</span>
                                        <span class="text-gray-400 text-[11px]">({{ $jt->category }})</span>
                                    </label>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-xs text-gray-400">No job types configured yet.</p>
                                @endif
                            </div>
                            <button type="submit"
                                class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-slate-800 shrink-0 mt-4 shadow-xs">
                                Save Specialties
                            </button>
                        </form>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="7" class="py-12 text-center">
                    <x-empty-state icon="users" title="No users found" subtitle="Try clearing search or filters to see all accounts." />
                </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($users->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    {{-- Add User Modal --}}
    <div x-show="showAdd" x-transition.opacity
        class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-6" @click.outside="showAdd = false">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-800 flex items-center justify-center">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Add New User</h3>
                </div>
                <button @click="showAdd = false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs">
                @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
            @endif
            <form method="POST" action="/admin/users" enctype="multipart/form-data"
                class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Password *</label>
                    <input type="password" name="password"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Contact No</label>
                    <input type="text" name="contact_no" value="{{ old('contact_no') }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Role *</label>
                    <select name="role" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select role</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff (Mechanic / Accountant / Inventory / Front Desk)</option>
                        <option value="coordinator">Coordinator (Progress Check)</option>
                        <option value="corporate">Corporate Client</option>
                        <option value="individual">Individual Customer</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                        Staff Sub-Role <span class="font-normal text-gray-400 lowercase">(only if role = staff)</span>
                    </label>
                    <select name="staff_role" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— None —</option>
                        @foreach(\App\Models\User::STAFF_ROLES as $key => $label)
                        <option value="{{ $key }}" {{ old('staff_role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Set specific permissions (inventory, pricing, invoicing) from the user's profile page after creating.</p>
                </div>
                <div class="md:col-span-2 flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAdd = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-slate-900 text-white px-5 py-2 rounded-xl hover:bg-slate-800 text-xs font-bold shadow-xs transition">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection