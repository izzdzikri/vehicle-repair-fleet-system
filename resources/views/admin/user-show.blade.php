@extends('layouts.app')
@section('page-title', 'User Profile')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <a href="/admin/users"
        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-xs transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to User Directory
    </a>

    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-semibold flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Profile Card --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex items-center gap-5 mb-6 pb-6 border-b border-gray-100 flex-wrap">
            <img src="{{ $user->avatar_url }}"
                alt="Avatar"
                class="w-20 h-20 rounded-full object-cover border-4 border-blue-500 shadow-xs"
                id="admin-avatar-preview">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $user->name }}</h2>
                <div class="flex gap-2 mt-1.5 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold
                        {{ $user->role === 'admin'       ? 'bg-purple-50 text-purple-700 border border-purple-200' :
                          ($user->role === 'staff'       ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                          ($user->role === 'coordinator' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' :
                          ($user->role === 'corporate'   ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                          'bg-emerald-50 text-emerald-700 border border-emerald-200'))) }}">
                        {{ ucfirst($user->role) }}
                    </span>
                    @if($user->role === 'staff' && $user->staff_role_label)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $user->staff_role_label }}
                    </span>
                    @endif
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold
                        {{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </div>
                @if($user->company)
                <p class="text-xs font-semibold text-gray-600 mt-2 flex items-center gap-1.5">
                    <i data-lucide="building" class="w-3.5 h-3.5 text-gray-400"></i>
                    <span>{{ $user->company->name }}</span>
                </p>
                @endif
            </div>
        </div>

        {{-- Edit Form --}}
        <form method="POST" action="/admin/users/{{ $user->id }}"
            enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Update Profile Photo</label>
                <input type="file" name="avatar" accept="image/*"
                    class="w-full text-sm text-gray-500 border border-gray-300 rounded-lg px-3.5 py-2 bg-gray-50/50"
                    onchange="previewAdminAvatar(this)">
                <p class="text-xs text-gray-400 mt-1">Accepts JPG or PNG (Maximum 2MB)</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Username</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Contact Number</label>
                    <input type="text" name="contact_no" value="{{ old('contact_no', $user->contact_no) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Account Status</label>
                    <select name="status"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="active"   {{ $user->status === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                @if($user->role === 'corporate')
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Linked Company</label>
                    <select name="company_id"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">— No company assigned —</option>
                        @foreach(\App\Models\Company::all() as $company)
                        <option value="{{ $company->id }}"
                            {{ $user->company_id == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            {{-- Password --}}
            <div class="border-t border-gray-100 pt-4 mt-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">
                    Reset Password
                    <span class="font-normal text-gray-400 normal-case">(leave blank to keep current)</span>
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">New Password</label>
                        <input type="password" name="password"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                            placeholder="Minimum 6 characters">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Confirm Password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                            placeholder="Repeat new password">
                    </div>
                </div>
            </div>

            <div class="pt-3">
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 text-sm font-semibold shadow-xs transition">
                    Save User Profile Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Role & Permissions (staff only) --}}
    @if($user->role === 'staff')
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <h3 class="text-base font-bold text-gray-900 mb-1">Staff Sub-Role & System Permissions</h3>
        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
            Assign workshop duties and operational permissions. A staff member can hold multiple permissions at once.
        </p>
        <form method="POST" action="/admin/users/{{ $user->id }}/role" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Staff Sub-Role Designation</label>
                <select name="staff_role" class="w-full md:w-72 border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">— None —</option>
                    @foreach(\App\Models\User::STAFF_ROLES as $key => $label)
                    <option value="{{ $key }}" {{ $user->staff_role === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">Granted Permissions</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                    @foreach(\App\Models\User::PERMISSIONS as $key => $label)
                    <label class="flex items-start gap-2.5 text-xs font-medium text-gray-700 p-2.5 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition">
                        <input type="checkbox" name="permissions[]" value="{{ $key }}"
                            {{ in_array($key, $user->permissions ?? []) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="pt-2">
                <button type="submit"
                    class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 text-xs font-bold shadow-xs transition">
                    Save Role & Permissions
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Vehicles — customers & corporate --}}
    @if($vehicles !== null)
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-gray-900">Registered Vehicles</h3>
            <span class="text-xs font-semibold px-2.5 py-0.5 bg-gray-100 text-gray-600 rounded-md">{{ $vehicles->count() }} total</span>
        </div>
        @if($vehicles->count())
        <div class="overflow-x-auto border border-gray-200/80 rounded-xl">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/75 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="py-3 px-4">Plate No</th>
                        <th class="py-3 px-4">Brand</th>
                        <th class="py-3 px-4">Model</th>
                        <th class="py-3 px-4">Year</th>
                        <th class="py-3 px-4 text-right">Mileage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($vehicles as $v)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="py-3 px-4">
                            <a href="/admin/vehicles/{{ $v->id }}"
                                class="font-mono text-xs font-bold bg-gray-100 text-gray-900 px-2 py-0.5 rounded border border-gray-200 hover:border-blue-400">
                                {{ $v->plate_number }}
                            </a>
                        </td>
                        <td class="py-3 px-4 font-medium text-gray-900">{{ $v->brand }}</td>
                        <td class="py-3 px-4 text-gray-700">{{ $v->model }}</td>
                        <td class="py-3 px-4 text-xs font-mono text-gray-500">{{ $v->year }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-gray-800">{{ number_format($v->mileage) }} km</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-400 text-sm py-4 text-center">No vehicles registered under this user account.</p>
        @endif
    </div>
    @endif

    {{-- Assigned Jobs — staff --}}
    @if($assignedJobs !== null)
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-gray-900">Assigned Job Cards</h3>
            <span class="text-xs font-semibold px-2.5 py-0.5 bg-gray-100 text-gray-600 rounded-md">{{ $assignedJobs->count() }} total</span>
        </div>
        @if($assignedJobs->count())
        <div class="overflow-x-auto border border-gray-200/80 rounded-xl">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/75 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="py-3 px-4">Job ID</th>
                        <th class="py-3 px-4">Vehicle</th>
                        <th class="py-3 px-4">Service Required</th>
                        <th class="py-3 px-4">Current Stage</th>
                        <th class="py-3 px-4 text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($assignedJobs as $job)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="py-3 px-4 font-bold">
                            <a href="/admin/job-cards/{{ $job->id }}"
                                class="text-blue-600 hover:underline">#{{ $job->id }}</a>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-mono text-xs font-bold bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200">
                                {{ $job->vehicle->plate_number ?? '—' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-medium text-gray-900">{{ $job->appointment->service_type ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold
                                {{ $job->current_stage === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                                  ($job->current_stage === 'repairing' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                                  'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right text-xs font-mono text-gray-500">{{ $job->created_at->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-400 text-sm py-4 text-center">No repair jobs assigned to this technician yet.</p>
        @endif
    </div>
    @endif

</div>

<script>
function previewAdminAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('admin-avatar-preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
