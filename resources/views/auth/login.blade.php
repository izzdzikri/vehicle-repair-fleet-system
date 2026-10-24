<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Vehicle Repair System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6"
    style="background: linear-gradient(135deg, #09132b 0%, #112352 50%, #071129 100%);">

    {{-- Main Container --}}
    <div class="w-full max-w-5xl my-auto">

        {{-- Top Brand Header --}}
        <div class="mb-8 text-center">
            <img src="/images/logo-rounded.png" alt="Teraju Setia Enterprise"
                class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl mx-auto mb-3 shadow-2xl border border-white/20 object-cover"
                style="box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Teraju Setia Enterprise</h1>
            <p class="text-blue-300 text-sm mt-1">Vehicle Repair & Fleet Maintenance Management System</p>
        </div>

        {{-- Two-Column Grid: AI Assistant Showcase (Left) + Sign In Form (Right) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">

            {{-- PROMINENT AI CHATBOT HERO CARD --}}
            <div class="lg:col-span-7 bg-white/10 backdrop-blur-md rounded-2xl border border-white/15 p-6 sm:p-8 flex flex-col justify-between shadow-2xl relative overflow-hidden">
                {{-- Decorative gradient background accents --}}
                <div class="absolute -right-16 -top-16 w-56 h-56 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-16 -bottom-16 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-200 border border-blue-400/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            @if(!empty(config('services.gemini.api_key')))
                                Google Gemini AI Online
                            @else
                                AI Workshop Desk Online
                            @endif
                        </span>
                        <span class="text-xs text-blue-300/80 font-medium">No account needed for guest booking</span>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight leading-snug">
                        Need Fast Service or Instant Quotes?
                    </h2>
                    <p class="text-blue-100/80 text-xs sm:text-sm mt-2 leading-relaxed">
                        Chat directly with our intelligent workshop assistant to check repair pricing, lookup stock availability, or schedule a walk-in service appointment in seconds.
                    </p>

                    {{-- 1-Click Interactive Prompts (Triggers Chatbot) --}}
                    <div class="mt-6 space-y-2.5">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-300/90">
                            Tap any quick question to chat instantly:
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-chatbot', { detail: { prompt: 'How much is an oil change and brake service?' } }))"
                                class="text-left p-3 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 hover:border-blue-400/40 transition group flex items-start gap-2.5">
                                <span class="p-1.5 rounded-lg bg-blue-500/20 text-blue-300 group-hover:scale-105 transition shrink-0">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-bold text-white group-hover:text-blue-200">Service Rates</p>
                                    <p class="text-[11px] text-blue-200/70">Oil & filter, brake checks</p>
                                </div>
                            </button>

                            <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-chatbot', { detail: { prompt: 'I want to book a walk-in service appointment tomorrow at 10 AM' } }))"
                                class="text-left p-3 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 hover:border-emerald-400/40 transition group flex items-start gap-2.5">
                                <span class="p-1.5 rounded-lg bg-emerald-500/20 text-emerald-300 group-hover:scale-105 transition shrink-0">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-bold text-white group-hover:text-emerald-200">Fast Walk-in Booking</p>
                                    <p class="text-[11px] text-blue-200/70">Reserve a repair slot</p>
                                </div>
                            </button>

                            <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-chatbot', { detail: { prompt: 'What are the workshop opening hours and location?' } }))"
                                class="text-left p-3 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 hover:border-blue-400/40 transition group flex items-start gap-2.5">
                                <span class="p-1.5 rounded-lg bg-blue-500/20 text-blue-300 group-hover:scale-105 transition shrink-0">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-bold text-white group-hover:text-blue-200">Hours & Directions</p>
                                    <p class="text-[11px] text-blue-200/70">Kluang Industrial Area</p>
                                </div>
                            </button>

                            <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-chatbot', { detail: { prompt: 'Check available spare parts and pricing' } }))"
                                class="text-left p-3 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 hover:border-purple-400/40 transition group flex items-start gap-2.5">
                                <span class="p-1.5 rounded-lg bg-purple-500/20 text-purple-300 group-hover:scale-105 transition shrink-0">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-bold text-white group-hover:text-purple-200">Check Parts & Stock</p>
                                    <p class="text-[11px] text-blue-200/70">Live inventory prices</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Prominent Open Chatbot Action --}}
                <div class="mt-8 pt-5 border-t border-white/10 flex items-center justify-between flex-wrap gap-3 relative z-10">
                    <div class="flex items-center gap-2 text-xs text-blue-200/80">
                        <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                        <span>Powered by Teraju NLU & Predictive Engine</span>
                    </div>

                    <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-chatbot'))"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-extrabold shadow-lg transition flex items-center gap-2 transform active:scale-95"
                        style="box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span>Chat with Workshop AI</span>
                    </button>
                </div>
            </div>

            {{-- SIGN IN FORM CARD --}}
            <div class="lg:col-span-5 bg-white rounded-2xl shadow-2xl p-6 sm:p-8 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-1">Welcome back</h2>
                    <p class="text-xs text-gray-500 mb-6">Sign in to manage vehicles, appointments & repairs</p>

                    @if(session('success'))
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-xs text-green-700 font-semibold">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600 font-semibold">
                        {{ session('error') }}
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600 font-semibold">
                        {{ $errors->first() }}
                    </div>
                    @endif

                    <form method="POST" action="/login">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email Address</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                placeholder="you@example.com"
                                class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500
                                       bg-gray-50 transition"
                                required autofocus>
                        </div>

                        <div class="mb-2">
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Password</label>
                                <a href="/forgot-password" class="text-xs font-semibold text-blue-600 hover:underline">Forgot password?</a>
                            </div>
                            <input type="password" name="password"
                                placeholder="••••••••"
                                class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500
                                       bg-gray-50 transition"
                                required>
                        </div>

                        <button type="submit"
                            class="w-full mt-5 bg-blue-600 text-white py-3 rounded-xl font-bold text-sm
                                   hover:bg-blue-700 transition shadow-md"
                            style="box-shadow: 0 4px 15px rgba(59,130,246,0.35);">
                            Sign In to Portal
                        </button>
                    </form>
                </div>

                <div class="pt-6 mt-6 border-t border-gray-100 text-center">
                    <p class="text-xs text-gray-500">
                        Don't have an account?
                        <a href="/register" class="text-blue-600 font-bold hover:underline">Register here</a>
                    </p>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <p class="text-center text-blue-300/60 text-xs mt-8">
            &copy; 2026 Teraju Setia Enterprise &bull; Automotive Fleet & Workshop Management
        </p>

    </div>

    {{-- Public AI Assistant Chatbot --}}
    @include('partials.chatbot')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
