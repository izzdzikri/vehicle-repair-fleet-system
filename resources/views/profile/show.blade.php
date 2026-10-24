@extends('layouts.app')
@section('page-title', 'My Profile')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div class="max-w-2xl mx-auto space-y-6">

    {{-- Avatar Card --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6 flex items-center gap-6 flex-wrap">
        <div class="relative shrink-0">
            <img src="{{ $user->avatar_url }}"
                alt="Avatar"
                class="w-24 h-24 rounded-full object-cover border-4 border-blue-500 shadow-xs"
                id="avatar-preview">
        </div>
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $user->name }}</h2>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    {{ ucfirst($user->role) }}
                </span>
                <span class="text-xs font-mono text-gray-500">{{ $user->email }}</span>
            </div>
            @if($user->contact_no)
            <p class="text-xs text-gray-400 font-mono mt-1">{{ $user->contact_no }}</p>
            @endif
        </div>
    </div>

    {{-- Edit Form --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="mb-5 pb-3 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Personal Information</h3>
            <p class="text-xs font-medium text-gray-500 mt-0.5">Update personal details, username, and password credentials</p>
        </div>

        <form method="POST" action="/profile" enctype="multipart/form-data" class="space-y-4">
            @csrf

            {{-- Avatar upload --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Change Profile Picture</label>
                <input type="file" name="avatar" accept="image/*"
                    class="w-full text-sm text-gray-500 border border-gray-300 rounded-lg px-3.5 py-2 bg-gray-50/50"
                    onchange="previewAvatar(this)">
                <p class="text-xs text-gray-400 mt-1">Accepts JPG or PNG (Maximum 2MB)</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Username</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. ali123">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Contact Number</label>
                    <input type="text" name="contact_no" value="{{ old('contact_no', $user->contact_no) }}"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                        placeholder="e.g. 0123456789">
                </div>
            </div>

            {{-- Password section --}}
            <div class="border-t border-gray-100 pt-4 mt-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">
                    Change Password
                    <span class="font-normal text-gray-400 normal-case">(leave blank to keep current)</span>
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">New Password</label>
                        <input type="password" name="password"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Min 6 characters">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Confirm Password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Repeat new password">
                    </div>
                </div>
            </div>

            <div class="pt-3">
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 text-sm font-semibold shadow-xs transition">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Two-Factor Authentication --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6" x-data="{ showDisable: false, showRegenerate: false }">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h2 class="text-base font-bold text-gray-900">Two-Factor Authentication (2FA)</h2>
                <p class="text-xs font-medium text-gray-500 mt-0.5">Protect your account using TOTP authenticator verification</p>
            </div>
            @if($user->hasTwoFactorEnabled())
            <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 rounded-full font-bold">Enabled</span>
            @else
            <span class="text-xs bg-gray-100 text-gray-600 border border-gray-200 px-2.5 py-0.5 rounded-full font-bold">Disabled</span>
            @endif
        </div>
        <p class="text-xs text-gray-600 mb-4 leading-relaxed">
            Require a time-based 6-digit verification code from Google Authenticator, Authy, or 1Password when signing in.
        </p>

        @if(session('two_factor_recovery_codes'))
        <div class="mb-4 p-4 bg-amber-50/70 border border-amber-200 rounded-xl">
            <p class="text-xs font-bold text-amber-900 mb-1.5 flex items-center gap-1.5">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                Save these emergency recovery codes now — they will not be shown again
            </p>
            <p class="text-xs text-amber-800 mb-3">
                Each one-time code can be used to authenticate if your device is misplaced.
            </p>
            <div class="grid grid-cols-2 gap-2 font-mono text-xs font-bold bg-white rounded-lg p-3 border border-amber-200 text-gray-800">
                @foreach(session('two_factor_recovery_codes') as $code)
                <span>{{ $code }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(session('two_factor_setup'))
        @php $setup = session('two_factor_setup'); @endphp
        <div class="border border-blue-200 bg-blue-50/50 rounded-xl p-5 mb-4"
            x-data x-init="new QRCode($refs.qr, { text: @js($setup['otpauth_url']), width: 170, height: 170 });">
            <p class="text-xs font-bold uppercase tracking-wider text-blue-900 mb-3">Scan QR code with your authenticator</p>
            <div class="flex flex-col sm:flex-row gap-5 items-start">
                <div x-ref="qr" class="bg-white p-2.5 rounded-xl border border-blue-200 shrink-0 shadow-xs"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-600 mb-1.5">Manual secret entry key:</p>
                    <p class="font-mono text-xs font-bold bg-white border border-gray-300 rounded-lg px-3 py-2 break-all mb-4 text-gray-800">{{ $setup['secret'] }}</p>

                    <form method="POST" action="/profile/two-factor/confirm" class="flex gap-2 items-end flex-wrap">
                        @csrf
                        <div class="flex-1 min-w-32">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Enter 6-digit verification code</label>
                            <input type="text" name="code" autocomplete="one-time-code" placeholder="123456"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                required>
                        </div>
                        <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 shadow-xs">
                            Confirm & Enable
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        @if($user->hasTwoFactorEnabled())
            @if(!session('two_factor_recovery_codes'))
            <div class="flex gap-3 flex-wrap pt-2">
                <button @click="showRegenerate = true"
                    class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-xs font-semibold hover:bg-gray-50 transition">
                    Regenerate Recovery Codes
                </button>
                <button @click="showDisable = true"
                    class="border border-rose-200 text-rose-600 px-4 py-2 rounded-lg text-xs font-semibold hover:bg-rose-50 transition">
                    Disable Two-Factor Authentication
                </button>
            </div>
            @endif

            <div x-show="showDisable" x-transition class="mt-4 p-4 border border-rose-200 bg-rose-50/50 rounded-xl" style="display:none">
                <p class="text-xs font-bold text-rose-800 mb-2.5">Confirm account password to disable two-factor authentication:</p>
                <form method="POST" action="/profile/two-factor/disable" class="flex gap-2 items-end flex-wrap">
                    @csrf
                    <div class="flex-1 min-w-40">
                        <input type="password" name="password" placeholder="Account password"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white"
                            required>
                    </div>
                    <button type="submit" class="bg-rose-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-rose-700 shadow-xs">
                        Disable 2FA
                    </button>
                    <button type="button" @click="showDisable = false"
                        class="px-3.5 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-white transition">
                        Cancel
                    </button>
                </form>
            </div>

            <div x-show="showRegenerate" x-transition class="mt-4 p-4 border border-gray-200 bg-gray-50 rounded-xl" style="display:none">
                <p class="text-xs font-medium text-gray-700 mb-2.5">
                    Enter password to invalidate previous recovery keys and issue new backup codes:
                </p>
                <form method="POST" action="/profile/two-factor/recovery-codes" class="flex gap-2 items-end flex-wrap">
                    @csrf
                    <div class="flex-1 min-w-40">
                        <input type="password" name="password" placeholder="Account password"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                            required>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 shadow-xs">
                        Regenerate
                    </button>
                    <button type="button" @click="showRegenerate = false"
                        class="px-3.5 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-white transition">
                        Cancel
                    </button>
                </form>
            </div>
        @elseif(!session('two_factor_setup'))
            <form method="POST" action="/profile/two-factor/enable" class="pt-2">
                @csrf
                <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-xs font-bold hover:bg-blue-700 shadow-xs transition">
                    Enable Two-Factor Authentication
                </button>
            </form>
        @endif
    </div>

    {{-- Delete Account (individual only) --}}
    @if(auth()->user()->role === 'individual')
    <div class="bg-white rounded-xl border border-rose-200 shadow-xs p-6">
        <h2 class="text-base font-bold text-rose-800 mb-1">Delete Customer Account</h2>
        <p class="text-xs text-gray-600 mb-4 leading-relaxed">
            Permanently erase personal identity credentials, linked vehicles, and appointment histories. This action is irreversible.
            To confirm deletion, type <span class="font-mono font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">delete my account</span> below.
        </p>

        <form method="POST" action="/customer/account/delete"
            onsubmit="return confirmSubmit(event, {title:'Delete your account?', message:'This will permanently delete your account, vehicles, and appointment history. This cannot be undone.', confirmLabel:'Delete My Account'})">
            @csrf @method('DELETE')
            <div class="flex gap-3 items-end flex-wrap">
                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Confirmation string *</label>
                    <input type="text" name="confirmation"
                        placeholder="delete my account"
                        class="w-full border border-rose-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        required>
                </div>
                <button type="submit"
                    class="bg-rose-600 text-white px-5 py-2.5 rounded-lg hover:bg-rose-700 text-xs font-bold shadow-xs transition">
                    Delete My Account
                </button>
            </div>
            @error('confirmation')
            <p class="text-xs font-semibold text-rose-600 mt-1.5">{{ $message }}</p>
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
