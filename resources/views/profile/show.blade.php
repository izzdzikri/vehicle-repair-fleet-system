@extends('layouts.app')
@section('page-title', 'My Profile')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Avatar Card --}}
    <div class="bg-white rounded-lg shadow p-6 flex items-center gap-6">
        <div class="relative">
            <img src="{{ $user->avatar_url }}"
                alt="Avatar"
                class="w-24 h-24 rounded-full object-cover border-4 border-blue-500"
                id="avatar-preview">
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">{{ $user->name }}</h2>
            <p class="text-sm text-gray-400">{{ ucfirst($user->role) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
        </div>
    </div>

    {{-- Edit Form --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Edit Profile</h3>

        <form method="POST" action="/profile" enctype="multipart/form-data" class="space-y-4">
            @csrf

            {{-- Avatar upload --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                <input type="file" name="avatar" accept="image/*"
                    class="w-full text-sm text-gray-500 border rounded px-3 py-2"
                    onchange="previewAvatar(this)">
                <p class="text-xs text-gray-400 mt-1">JPG or PNG, max 2MB</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. ali123">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact No</label>
                    <input type="text" name="contact_no" value="{{ old('contact_no', $user->contact_no) }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. 0123456789">
                </div>
            </div>

            {{-- Password section --}}
            <div class="border-t pt-4 mt-2">
                <h4 class="text-sm font-semibold text-gray-600 mb-3">Change Password <span class="font-normal text-gray-400">(leave blank to keep current)</span></h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="password"
                            class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Min 6 characters">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Repeat new password">
                    </div>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 text-sm font-medium">
                Save Changes
            </button>
        </form>
    </div>

        {{-- Delete Account (individual only) --}}
    @if(auth()->user()->role === 'individual')
    <div class="bg-white rounded-lg shadow p-6 mt-6 border border-red-200">
        <h2 class="text-lg font-semibold text-red-700 mb-1">Delete Account</h2>
        <p class="text-sm text-gray-500 mb-4">
            Permanently delete your account and all associated data. This action cannot be undone.
            To confirm, type <span class="font-mono font-semibold text-red-600">delete my account</span> below.
        </p>

        <form method="POST" action="/customer/account/delete"
            onsubmit="return confirm('Are you absolutely sure? This cannot be undone.')">
            @csrf @method('DELETE')
            <div class="flex gap-3 items-end flex-wrap">
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type confirmation *</label>
                    <input type="text" name="confirmation"
                        placeholder="delete my account"
                        class="w-full border border-red-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                        required>
                </div>
                <button type="submit"
                    class="bg-red-600 text-white px-5 py-2 rounded hover:bg-red-700 text-sm font-medium">
                    Delete My Account
                </button>
            </div>
            @error('confirmation')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </form>
    </div>
    @endif

</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatar-preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection