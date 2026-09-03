<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Vehicle Repair System</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col items-center justify-center"
    style="background: linear-gradient(135deg, #0f1c3f 0%, #1a2f6e 50%, #0d1b4b 100%);">

    <div class="mb-6 text-center">
        <div class="w-16 h-16 rounded-2xl bg-blue-600 flex items-center justify-center mx-auto mb-3 shadow-lg"
            style="box-shadow: 0 0 30px rgba(59,130,246,0.5);">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
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
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                           bg-gray-50"
                    required autofocus>
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold text-sm
                       hover:bg-blue-700 transition shadow-md">
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