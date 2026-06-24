<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%232563EB'/><path d='M67 32a1 1 0 0 0 0 6l7 7a1 1 0 0 0 6 0l16-16a26 26 0 0 1-34 34l-30 30a9 9 0 0 1-13-13l30-30a26 26 0 0 1 34-34l-16 16z' fill='white' transform='scale(0.55) translate(18,18)'/></svg>">    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Vehicle Repair System</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col items-center justify-center py-10"
    style="background: linear-gradient(135deg, #0f1c3f 0%, #1a2f6e 50%, #0d1b4b 100%);">

<div class="w-full max-w-md px-4">

    {{-- Logo --}}
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
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    placeholder="you@example.com"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                <input type="text" name="contact_no" value="{{ old('contact_no') }}"
                    placeholder="e.g. 011-12345678"
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
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation"
                    placeholder="Repeat password"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required>
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold text-sm
                       hover:bg-blue-700 transition shadow-md"
                style="box-shadow: 0 4px 15px rgba(59,130,246,0.4);">
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

</body>
</html>