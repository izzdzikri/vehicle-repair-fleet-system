@extends('layouts.app')
@section('page-title', 'My Profile')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

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

    {{-- Two-Factor Authentication --}}
    <div class="bg-white rounded-lg shadow p-6" x-data="{ showDisable: false, showRegenerate: false }">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-lg font-semibold text-gray-700">Two-Factor Authentication</h2>
            @if($user->hasTwoFactorEnabled())
            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">Enabled</span>
            @else
            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded-full font-medium">Disabled</span>
            @endif
        </div>
        <p class="text-sm text-gray-500 mb-4">
            Add an extra layer of security to your account by requiring a code from an authenticator app
            (e.g. Google Authenticator, Authy) in addition to your password when signing in.
        </p>

        {{-- Note: success/error flash banners are already rendered globally by
             layouts/app.blade.php — deliberately not duplicated here. --}}

        @if(session('two_factor_recovery_codes'))
        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm font-semibold text-yellow-800 mb-2">
                ⚠ Save these recovery codes now — they won't be shown again
            </p>
            <p class="text-xs text-yellow-700 mb-3">
                Each code can be used once to sign in if you lose access to your authenticator app.
            </p>
            <div class="grid grid-cols-2 gap-2 font-mono text-sm bg-white rounded p-3 border border-yellow-200">
                @foreach(session('two_factor_recovery_codes') as $code)
                <span>{{ $code }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(session('two_factor_setup'))
        @php $setup = session('two_factor_setup'); @endphp
        <div class="border border-blue-200 bg-blue-50 rounded-lg p-4 mb-4"
            x-data x-init="new QRCode($refs.qr, { text: @js($setup['otpauth_url']), width: 180, height: 180 });">
            <p class="text-sm font-semibold text-blue-800 mb-3">Scan this QR code with your authenticator app</p>
            <div class="flex flex-col sm:flex-row gap-4 items-start">
                <div x-ref="qr" class="bg-white p-2 rounded border shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500 mb-1">Can't scan? Enter this key manually:</p>
                    <p class="font-mono text-sm bg-white border rounded px-3 py-2 break-all mb-4">{{ $setup['secret'] }}</p>

                    <form method="POST" action="/profile/two-factor/confirm" class="flex gap-2 items-end flex-wrap">
                        @csrf
                        <div class="flex-1 min-w-32">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Enter the 6-digit code</label>
                            <input type="text" name="code" autocomplete="one-time-code" placeholder="123456"
                                class="w-full border rounded px-3 py-2 text-sm tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>
                        <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                            Confirm & Enable
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        @if($user->hasTwoFactorEnabled())
            @if(!session('two_factor_recovery_codes'))
            <div class="flex gap-3 flex-wrap">
                <button @click="showRegenerate = true"
                    class="border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50">
                    Regenerate Recovery Codes
                </button>
                <button @click="showDisable = true"
                    class="border border-red-300 text-red-600 px-4 py-2 rounded text-sm hover:bg-red-50">
                    Disable Two-Factor Authentication
                </button>
            </div>
            @endif

            <div x-show="showDisable" x-transition class="mt-4 p-4 border border-red-200 bg-red-50 rounded-lg" style="display:none">
                <p class="text-sm text-red-700 mb-3">Enter your password to disable two-factor authentication.</p>
                <form method="POST" action="/profile/two-factor/disable" class="flex gap-2 items-end flex-wrap">
                    @csrf
                    <div class="flex-1 min-w-40">
                        <input type="password" name="password" placeholder="Current password"
                            class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                            required>
                    </div>
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">
                        Disable
                    </button>
                    <button type="button" @click="showDisable = false"
                        class="px-4 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                </form>
            </div>

            <div x-show="showRegenerate" x-transition class="mt-4 p-4 border border-gray-200 bg-gray-50 rounded-lg" style="display:none">
                <p class="text-sm text-gray-600 mb-3">
                    Enter your password to generate a new set of recovery codes. Your old codes will stop working.
                </p>
                <form method="POST" action="/profile/two-factor/recovery-codes" class="flex gap-2 items-end flex-wrap">
                    @csrf
                    <div class="flex-1 min-w-40">
                        <input type="password" name="password" placeholder="Current password"
                            class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                        Regenerate
                    </button>
                    <button type="button" @click="showRegenerate = false"
                        class="px-4 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                </form>
            </div>
        @elseif(!session('two_factor_setup'))
            <form method="POST" action="/profile/two-factor/enable">
                @csrf
                <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded text-sm hover:bg-blue-700 font-medium">
                    Enable Two-Factor Authentication
                </button>
            </form>
        @endif
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
            onsubmit="return confirmSubmit(event, {title:'Delete your account?', message:'This will permanently delete your account, vehicles, and appointment history. This cannot be undone.', confirmLabel:'Delete My Account'})">
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