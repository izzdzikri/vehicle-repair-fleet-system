<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Vehicle Repair System')</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen" x-data="{ sidebarOpen: false }">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        style="display:none">
    </div>

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white flex flex-col
                      transform transition-transform duration-300 ease-in-out
                      lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

            {{-- Sidebar header --}}
            <div class="px-6 py-5 font-bold border-b border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                    </div>
                    <span class="text-white text-base">Vehicle System</span>
                </div>
                {{-- Close button mobile --}}
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Nav links --}}
            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                @php
                    $link = function($href, $icon, $label, $pattern, $badge = null) {
                        $active = request()->is(ltrim($pattern, '/'));
                        $base   = 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-gray-700 transition ';
                        $base  .= $active ? 'bg-gray-700 text-white' : 'text-gray-300';
                        $html   = '<a href="'.$href.'" class="'.$base.'">';
                        $html  .= '<i data-lucide="'.$icon.'" class="w-4 h-4 shrink-0"></i>';
                        $html  .= '<span class="flex-1 truncate">'.$label.'</span>';
                        if ($badge) {
                            $html .= '<span class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full shrink-0">'.$badge.'</span>';
                        }
                        $html .= '</a>';
                        return $html;
                    };
                @endphp

                @if(auth()->user()->role === 'admin')
                    @php
                        $pendingBadge  = \App\Models\Appointment::where('status','pending')->count();
                        $lowBadge      = \App\Models\SparePart::whereColumn('stock','<=','min_stock')->count();
                        $requestBadge  = \App\Models\AccountRequest::where('status','pending')->count();
                        $poBadge       = \App\Models\PurchaseOrder::where('status','draft')->count();
                        $leaveBadge    = \App\Models\LeaveRequest::where('status','pending')->count();
                    @endphp
                    {!! $link('/admin/dashboard',       'layout-dashboard', 'Dashboard',         'admin/dashboard') !!}
                    {!! $link('/admin/appointments',    'calendar',         'Appointments',      'admin/appointments*', $pendingBadge ?: null) !!}
                    {!! $link('/admin/appointments/queue', 'clock',         'Today\'s Queue',    'admin/appointments/queue') !!}
                    {!! $link('/admin/job-cards',       'clipboard-list',   'Job Cards',         'admin/job-cards*') !!}
                    {!! $link('/admin/job-cards/schedule', 'list-ordered',  'Job Schedule',      'admin/job-cards/schedule') !!}
                    {!! $link('/admin/spare-parts',     'package',          'Inventory',         'admin/spare-parts*', $lowBadge ?: null) !!}
                    {!! $link('/admin/vehicles',        'car',              'Vehicles',          'admin/vehicles*') !!}
                    {!! $link('/admin/invoices',        'receipt',          'Invoices',          'admin/invoices*') !!}
                    {!! $link('/admin/suppliers',       'truck',            'Suppliers',         'admin/suppliers*') !!}
                    {!! $link('/admin/purchase-orders', 'shopping-cart',    'Purchase Orders',   'admin/purchase-orders*', $poBadge ?: null) !!}
                    {!! $link('/admin/staff-management/attendance', 'user-cog', 'Staff Management', 'admin/staff-management*', $leaveBadge ?: null) !!}
                    {!! $link('/admin/users',           'users',            'Users',             'admin/users*') !!}
                    {!! $link('/admin/companies',       'building-2',       'Companies',         'admin/companies*') !!}
                    {!! $link('/admin/job-types',       'list-checks',      'Job Types',         'admin/job-types*') !!}
                    {!! $link('/admin/maintenance',     'bell',             'Maintenance',       'admin/maintenance*') !!}
                    {!! $link('/admin/reports',         'bar-chart-2',      'Reports',           'admin/reports') !!}
                    {!! $link('/admin/account-requests','user-x',           'Account Requests',  'admin/account-requests*', $requestBadge ?: null) !!}

                @elseif(auth()->user()->role === 'staff')
                    {!! $link('/staff/dashboard',       'layout-dashboard', 'Dashboard',         'staff/dashboard') !!}
                    {!! $link('/staff/appointments/queue', 'clock',         'Today\'s Queue',    'staff/appointments/queue') !!}
                    {!! $link('/staff/job-cards/schedule', 'list-ordered',  'Job Schedule',      'staff/job-cards/schedule') !!}
                    {!! $link('/staff/inventory',       'package',          'Inventory',         'staff/inventory') !!}
                    {!! $link('/staff/invoices',        'receipt',          'Invoices',          'staff/invoices*') !!}
                    {!! $link('/staff/staff-management/attendance', 'clock-4', 'My Attendance',  'staff/staff-management/attendance') !!}
                    {!! $link('/staff/staff-management/leave',      'calendar-off', 'Leave',     'staff/staff-management/leave') !!}

                @elseif(auth()->user()->role === 'corporate')
                    {!! $link('/client/dashboard',      'layout-dashboard', 'Dashboard',         'client/dashboard') !!}
                    {!! $link('/client/appointments',   'calendar',         'Appointments',      'client/appointments*') !!}
                    {!! $link('/client/vehicles',       'car',              'My Fleet',          'client/vehicles*') !!}
                    {!! $link('/client/invoices',       'receipt',          'Invoices',          'client/invoices*') !!}
                    {!! $link('/client/maintenance',    'bell',             'Maintenance Alerts','client/maintenance*') !!}
                    {!! $link('/client/company',        'building-2',       'Company',           'client/company*') !!}

                @elseif(auth()->user()->role === 'individual')
                    @php
                        $myAlertBadge = \App\Models\MaintenanceAlert::whereIn(
                            'vehicle_id',
                            \App\Models\Vehicle::where('user_id', auth()->id())->pluck('id')
                        )->where('is_read', false)->count();
                    @endphp
                    {!! $link('/customer/dashboard',    'layout-dashboard', 'Dashboard',         'customer/dashboard') !!}
                    {!! $link('/customer/appointments', 'calendar',         'Appointments',      'customer/appointments*') !!}
                    {!! $link('/customer/vehicles',     'car',              'My Vehicles',       'customer/vehicles*') !!}
                    {!! $link('/customer/invoices',     'receipt',          'Invoices',          'customer/invoices*') !!}
                    {!! $link('/customer/maintenance',  'bell',             'Maintenance Alerts','customer/maintenance*', $myAlertBadge ?: null) !!}
                @endif
            </nav>

            {{-- User info --}}
            <div class="px-4 py-3 border-t border-gray-700">
                <div class="flex items-center gap-3">
                    <img src="{{ auth()->user()->avatar_url }}"
                        class="w-8 h-8 rounded-full object-cover border border-gray-600 shrink-0">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 capitalize">{{ auth()->user()->role }}</p>
                    </div>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" title="Logout"
                            class="text-gray-400 hover:text-white shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Top bar --}}
            <header class="bg-white border-b px-4 py-3 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    {{-- Hamburger --}}
                    <button @click="sidebarOpen = true"
                        class="lg:hidden text-gray-500 hover:text-gray-700 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-base font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
                        <p class="text-xs text-gray-400 hidden sm:block">{{ now()->format('l, d F Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/profile" class="flex items-center gap-2 hover:opacity-80">
                        <img src="{{ auth()->user()->avatar_url }}"
                            class="w-8 h-8 rounded-full object-cover border-2 border-blue-400">
                        <span class="text-sm font-medium text-gray-700 hidden sm:block truncate max-w-32">
                            {{ auth()->user()->name }}
                        </span>
                    </a>
                </div>
            </header>

            {{-- Flash messages --}}
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                class="mx-4 mt-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm border border-green-200">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                class="mx-4 mt-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm border border-red-200">
                {{ session('error') }}
            </div>
            @endif

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Chatbot widget --}}
    <div x-data="{
        open: false,
        messages: [
            { role: 'bot', text: 'Hi! I\'m the Teraju Setia assistant. Ask me about services, part prices, or your appointments.' }
        ],
        input: '',
        loading: false,
        async send() {
            if (!this.input.trim() || this.loading) return;
            const userMsg = this.input.trim();
            this.messages.push({ role: 'user', text: userMsg });
            this.input = '';
            this.loading = true;
            this.$nextTick(() => this.scrollToBottom());
            try {
                const res = await fetch('/chatbot/reply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ message: userMsg }),
                });
                const data = await res.json();
                this.messages.push({ role: 'bot', text: data.reply });
            } catch(e) {
                this.messages.push({ role: 'bot', text: 'Sorry, something went wrong.' });
            }
            this.loading = false;
            this.$nextTick(() => this.scrollToBottom());
        },
        scrollToBottom() {
            const el = this.$refs.chatBody;
            if (el) el.scrollTop = el.scrollHeight;
        }
    }" class="fixed bottom-4 right-4 z-50">
        <button @click="open = !open"
            class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-blue-600 text-white shadow-lg
                   flex items-center justify-center hover:bg-blue-700 transition">
            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <svg x-show="open" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        <div x-show="open" x-transition
            class="absolute bottom-16 right-0 bg-white rounded-2xl shadow-2xl border flex flex-col overflow-hidden"
            style="width: min(320px, calc(100vw - 2rem)); height: 460px; display:none">
            <div class="bg-blue-600 text-white px-4 py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-sm">Teraju Assistant</p>
                    <p class="text-xs text-blue-100">Ask about services & prices</p>
                </div>
            </div>
            <div x-ref="chatBody" class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50">
                <template x-for="(msg, i) in messages" :key="i">
                    <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.role === 'user'
                                ? 'bg-blue-600 text-white rounded-2xl rounded-br-sm px-3 py-2 text-xs max-w-[85%]'
                                : 'bg-white border text-gray-700 rounded-2xl rounded-bl-sm px-3 py-2 text-xs max-w-[85%] shadow-sm'"
                            x-text="msg.text"
                            style="white-space: pre-wrap; word-break: break-word;">
                        </div>
                    </div>
                </template>
                <div x-show="loading" class="flex justify-start">
                    <div class="bg-white border text-gray-400 rounded-2xl rounded-bl-sm px-3 py-2 text-xs shadow-sm">
                        Typing...
                    </div>
                </div>
            </div>
            <div class="p-3 border-t bg-white flex gap-2">
                <input type="text" x-model="input"
                    @keydown.enter="send()"
                    placeholder="Type a message..."
                    class="flex-1 border rounded-full px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button @click="send()" :disabled="loading"
                    class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center
                           hover:bg-blue-700 disabled:opacity-50 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>