<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification — Vehicle Repair System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col items-center justify-center"
    style="background: linear-gradient(135deg, #0f1c3f 0%, #1a2f6e 50%, #0d1b4b 100%);"
    x-data="{ useRecovery: false }">

    <div class="mb-6 text-center">
        <img src="/images/logo-rounded.png" alt="Teraju Setia Enterprise"
            class="w-16 h-16 rounded-2xl mx-auto mb-3 shadow-xl border border-white/20 object-cover"
            style="box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
        <h1 class="text-2xl font-bold text-white tracking-tight">Two-Factor Verification</h1>
        <p class="text-blue-300 text-sm mt-1">Teraju Setia Enterprise</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-1">Enter your code</h2>
        <p class="text-sm text-gray-400 mb-6" x-show="!useRecovery">
            Open your authenticator app and enter the 6-digit code for this account.
        </p>
        <p class="text-sm text-gray-400 mb-6" x-show="useRecovery" style="display:none">
            Enter one of your unused recovery codes.
        </p>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="/two-factor-challenge">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1"
                    x-text="useRecovery ? 'Recovery Code' : 'Authentication Code'"></label>
                <input type="text" name="code" autofocus autocomplete="one-time-code"
                    x-bind:placeholder="useRecovery ? 'XXXX-XXXX' : '123456'"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm text-center tracking-widest
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
                    required>
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold text-sm
                       hover:bg-blue-700 transition shadow-md">
                Verify
            </button>
        </form>

        <button type="button" @click="useRecovery = !useRecovery"
            class="w-full text-center text-sm text-blue-600 hover:underline mt-4">
            <span x-show="!useRecovery">Use a recovery code instead</span>
            <span x-show="useRecovery" style="display:none">Use an authenticator code instead</span>
        </button>

        <form method="POST" action="/logout" class="mt-4">
            @csrf
            <button type="submit" class="w-full text-center text-xs text-gray-400 hover:text-gray-600">
                Cancel and sign out
            </button>
        </form>
    </div>

    <p class="text-blue-400 text-xs mt-6 opacity-60">© 2026 Teraju Setia Enterprise</p>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>