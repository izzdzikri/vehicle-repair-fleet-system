<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register — Vehicle Repair System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer fetchpriority="high"></script>
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
<body class="min-h-screen flex flex-col items-center justify-center py-10"
    style="background: linear-gradient(135deg, #0f1c3f 0%, #1a2f6e 50%, #0d1b4b 100%);">

<div class="w-full max-w-md px-4">

    {{-- Logo --}}
    <div class="mb-6 text-center">
        <img src="/images/logo-rounded.png" alt="Teraju Setia Enterprise"
            class="w-16 h-16 rounded-2xl mx-auto mb-3 shadow-xl border border-white/20 object-cover"
            style="box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
        <h1 class="text-2xl font-bold text-white tracking-tight">Vehicle Repair System</h1>
        <p class="text-blue-300 text-sm mt-1">Teraju Setia Enterprise</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-1">Create Account</h2>
        <p class="text-gray-400 text-sm mb-6">Fill in your details to register</p>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif

        <form method="POST" action="/register" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    placeholder="Your full name"
                    autocomplete="name" enterkeyhint="next"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    placeholder="you@example.com"
                    autocomplete="email" enterkeyhint="next"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                <input type="tel" name="contact_no" value="{{ old('contact_no') }}"
                    placeholder="e.g. 011-12345678"
                    autocomplete="tel" inputmode="tel" enterkeyhint="next"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                <select name="role"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="individual" {{ old('role') === 'individual' ? 'selected' : '' }}>
                        Individual Customer
                    </option>
                    <option value="corporate" {{ old('role') === 'corporate' ? 'selected' : '' }}>
                        Corporate Client
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password"
                    placeholder="Min 6 characters"
                    autocomplete="new-password" enterkeyhint="next"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation"
                    placeholder="Repeat password"
                    autocomplete="new-password" enterkeyhint="done"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-slate-800 transition shadow-md">
                Create Account
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-5">
            Already have an account?
            <a href="/login" class="text-blue-600 font-medium hover:underline">Sign in</a>
        </p>
    </div>

</div>

<p class="text-blue-400 text-xs mt-6 opacity-60">© 2026 Teraju Setia Enterprise</p>

{{-- Public AI Assistant Chatbot --}}
@include('partials.chatbot')

</body>
</html>