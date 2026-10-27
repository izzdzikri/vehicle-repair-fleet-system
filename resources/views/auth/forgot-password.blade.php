<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Vehicle Repair System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { scrollbar-gutter: stable; }
        h1, h2, h3 { text-wrap: balance; }
        input:user-invalid, select:user-invalid, textarea:user-invalid {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        input:user-valid:not(:placeholder-shown) {
            border-color: #10b981 !important;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center"
    style="background: linear-gradient(135deg, #0f1c3f 0%, #1a2f6e 50%, #0d1b4b 100%);">

    <div class="mb-6 text-center">
        <img src="/images/logo-rounded.png" alt="Teraju Setia Enterprise"
            class="w-16 h-16 rounded-2xl mx-auto mb-3 shadow-xl border border-white/20 object-cover"
            style="box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
        <h1 class="text-2xl font-bold text-white tracking-tight">Vehicle Repair System</h1>
        <p class="text-blue-300 text-sm mt-1">Teraju Setia Enterprise</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-1">Forgot Password</h2>
        <p class="text-sm text-gray-400 mb-6">Enter your email and we'll send you a reset link.</p>

        @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="/forgot-password">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    placeholder="you@example.com"
                    autocomplete="email" enterkeyhint="send"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                           bg-gray-50"
                    required autofocus>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-slate-800 transition shadow-md">
                Send Reset Link
            </button>
        </form>

        <p class="text-center text-sm text-gray-400 mt-5">
            <a href="/login" class="text-blue-600 font-medium hover:underline">← Back to Login</a>
        </p>
    </div>

    <p class="text-blue-400 text-xs mt-6 opacity-60">© 2026 Teraju Setia Enterprise</p>

</body>
</html>