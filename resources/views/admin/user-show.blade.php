@extends('layouts.app')
@section('page-title', 'User Profile')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <a href="/admin/users" class="text-sm text-blue-600 hover:underline">← Back to Users</a>

    @if(session('success'))
    <div class="p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
    @endif

    {{-- Profile Card --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center gap-5 mb-6 pb-6 border-b">
            <img src="{{ $user->avatar_url }}"
                alt="Avatar"
                class="w-20 h-20 rounded-full object-cover border-4 border-blue-500"
                id="admin-avatar-preview">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $user->name }}</h2>
                <div class="flex gap-2 mt-1 flex-wrap">
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $user->role === 'admin'     ? 'bg-purple-100 text-purple-700' :
                          ($user->role === 'staff'     ? 'bg-blue-100 text-blue-700' :
                          ($user->role === 'corporate' ? 'bg-yellow-100 text-yellow-700' :
                          'bg-green-100 text-green-700')) }}">
                        {{ ucfirst($user->role) }}
                    </span>
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </div>
                @if($user->company)
                <p class="text-sm text-gray-500 mt-1">🏢 {{ $user->company->name }}</p>
                @endif
            </div>
        </div>

        {{-- Edit Form --}}
        <form method="POST" action="/admin/users/{{ $user->id }}"
            enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                <input type="file" name="avatar" accept="image/*"
                    class="w-full text-sm text-gray-500 border rounded px-3 py-2"
                    onchange="previewAdminAvatar(this)">
                <p class="text-xs text-gray-400 mt-1">JPG or PNG, max 2MB</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact No</label>
                    <input type="text" name="contact_no" value="{{ old('contact_no', $user->contact_no) }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="active"   {{ $user->status === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                @if($user->role === 'corporate')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Link to Company</label>
                    <select name="company_id"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">— No company —</option>
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
            <div class="border-t pt-4">
                <h4 class="text-sm font-semibold text-gray-600 mb-3">
                    Change Password
                    <span class="font-normal text-gray-400">(leave blank to keep current)</span>
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="password"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                            placeholder="Min 6 characters">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 text-sm font-medium">
                Save Changes
            </button>
        </form>
    </div>

    {{-- Vehicles — customers & corporate --}}
    @if($vehicles !== null)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">
            Registered Vehicles
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $vehicles->count() }} total</span>
        </h3>
        @if($vehicles->count())
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2 pr-4">Plate No</th>
                    <th class="pb-2 pr-4">Brand</th>
                    <th class="pb-2 pr-4">Model</th>
                    <th class="pb-2 pr-4">Year</th>
                    <th class="pb-2">Mileage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vehicles as $v)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-2 pr-4">
                        <a href="/admin/vehicles/{{ $v->id }}"
                            class="font-bold text-blue-700 hover:underline">{{ $v->plate_number }}</a>
                    </td>
                    <td class="py-2 pr-4">{{ $v->brand }}</td>
                    <td class="py-2 pr-4">{{ $v->model }}</td>
                    <td class="py-2 pr-4">{{ $v->year }}</td>
                    <td class="py-2">{{ number_format($v->mileage) }} km</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-gray-400 text-sm">No vehicles registered yet.</p>
        @endif
    </div>
    @endif

    {{-- Assigned Jobs — staff --}}
    @if($assignedJobs !== null)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">
            Assigned Job Cards
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $assignedJobs->count() }} total</span>
        </h3>
        @if($assignedJobs->count())
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2 pr-4">Job ID</th>
                    <th class="pb-2 pr-4">Vehicle</th>
                    <th class="pb-2 pr-4">Service</th>
                    <th class="pb-2 pr-4">Stage</th>
                    <th class="pb-2">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assignedJobs as $job)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-2 pr-4 font-medium">
                        <a href="/admin/job-cards/{{ $job->id }}"
                            class="text-blue-600 hover:underline">#{{ $job->id }}</a>
                    </td>
                    <td class="py-2 pr-4">{{ $job->vehicle->plate_number ?? '—' }}</td>
                    <td class="py-2 pr-4">{{ $job->appointment->service_type ?? '—' }}</td>
                    <td class="py-2 pr-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            {{ $job->current_stage === 'completed' ? 'bg-green-100 text-green-700' :
                              ($job->current_stage === 'repairing' ? 'bg-blue-100 text-blue-700' :
                              'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst(str_replace('_',' ',$job->current_stage)) }}
                        </span>
                    </td>
                    <td class="py-2">{{ $job->created_at->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-gray-400 text-sm">No jobs assigned yet.</p>
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