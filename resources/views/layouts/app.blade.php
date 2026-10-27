<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Vehicle Repair System')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer fetchpriority="high"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer fetchpriority="low"></script>
    <style>
        /* Modern Web Guidance: Stable scrollbar gutter to prevent layout shift */
        html { scrollbar-gutter: stable; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        /* Modern Web Guidance: Balanced headlines to prevent orphan words */
        h1, h2, h3, .card-title { text-wrap: balance; }

        /* Modern Web Guidance: Contain scroll chaining under modal dialogs & overlays */
        dialog:modal, [role="dialog"], .modal-overlay, .modal-container {
            overscroll-behavior: contain;
        }

        /* Modern Web Guidance: Show validation feedback only after user interaction/blur */
        input:user-invalid, select:user-invalid, textarea:user-invalid {
            border-color: #ef4444 !important;
            background-color: #fffafb !important;
        }
        input:user-valid:not(:placeholder-shown):not([type="checkbox"]):not([type="radio"]) {
            border-color: #10b981;
        }

        /* Modern slim scrollbar without legacy webkit hacks */
        * {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 10mm;
            }
            aside, header, nav, .no-print, [no-print] { display: none !important; }
            html, body {
                background: #ffffff !important;
                color: #111827 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .flex.h-screen { display: block !important; height: auto !important; overflow: visible !important; }
            .flex-1.flex.flex-col { display: block !important; height: auto !important; overflow: visible !important; }
            main { padding: 0 !important; margin: 0 !important; overflow: visible !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">

    {{-- Accessibility: Skip to Main Content Link --}}
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:px-4 focus:py-2.5 focus:bg-slate-900 focus:text-white focus:rounded-xl focus:font-semibold focus:shadow-xl focus:ring-2 focus:ring-blue-400">
        Skip to main content
    </a>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
        style="display:none">
    </div>

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white flex flex-col
                      transform transition-transform duration-300 ease-in-out
                      lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

            {{-- Sidebar header --}}
            <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
                <a href="/" class="flex items-center gap-3 hover:opacity-90 transition">
                    <img src="/images/logo-rounded.png" alt="Teraju Setia Logo"
                        class="w-9 h-9 rounded-xl object-cover shadow-md shrink-0 border border-slate-700/80">
                    <div class="min-w-0">
                        <span class="block text-white text-sm font-extrabold tracking-tight leading-tight truncate">Teraju Setia</span>
                        <span class="block text-[11px] text-blue-400 font-semibold tracking-wide uppercase leading-tight">Vehicle System</span>
                    </div>
                </a>
                {{-- Close button mobile --}}
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white p-1 rounded-lg hover:bg-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Nav links --}}
            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                @php
                    $link = function($href, $icon, $label, $patterns, $badge = null, $exclude = []) {
                        $patterns = (array) $patterns;
                        $active   = request()->is(...$patterns);
                        if ($active && !empty($exclude) && request()->is(...(array) $exclude)) {
                            $active = false;
                        }
                        $base   = 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 transition ';
                        $base  .= $active ? 'bg-gray-800 text-white font-semibold' : 'text-gray-300';
                        $html   = '<a href="'.$href.'" class="'.$base.'">';
                        $html  .= '<i data-lucide="'.$icon.'" class="w-4 h-4 shrink-0"></i>';
                        $html  .= '<span class="flex-1 truncate">'.$label.'</span>';
                        if ($badge) {
                            $html .= '<span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shrink-0">'.$badge.'</span>';
                        }
                        $html .= '</a>';
                        return $html;
                    };
                    $section = function($label) {
                        return '<p class="px-3 pt-4 pb-1 text-xs font-bold text-gray-400 uppercase tracking-wider first:pt-0">'.$label.'</p>';
                    };
                @endphp

                @if(auth()->user()->role === 'admin')
                    @php
                        $sidebarCounts = \Illuminate\Support\Facades\Cache::remember('sidebar_counts_admin', 30, function () {
                            return [
                                'pending_appts'    => \App\Models\Appointment::where('status','pending')->count(),
                                'low_stock'        => \App\Models\SparePart::whereColumn('stock','<=','min_stock')->count(),
                                'pending_requests' => \App\Models\AccountRequest::where('status','pending')->count(),
                                'draft_pos'        => \App\Models\PurchaseOrder::where('status','draft')->count(),
                                'pending_leave'    => \App\Models\LeaveRequest::where('status','pending')->count(),
                                'urgent_alerts'    => \App\Models\MaintenanceAlert::where('urgency','high')->where('is_read', false)->count(),
                            ];
                        });
                    @endphp

                    {!! $section('Overview') !!}
                    {!! $link('/admin/dashboard',       'layout-dashboard', 'Dashboard',         'admin/dashboard') !!}

                    {!! $section('Front Desk') !!}
                    {!! $link('/admin/appointments',    'calendar',         'Appointments',      'admin/appointments*', $sidebarCounts['pending_appts'] ?: null, 'admin/appointments/queue') !!}
                    {!! $link('/admin/appointments/queue', 'clock',         'Today\'s Queue',    'admin/appointments/queue') !!}
                    {!! $link('/admin/vehicles',        'car',              'Vehicles',          'admin/vehicles*') !!}
                    {!! $link('/admin/trip-logs',       'map-pin',          'Trip Logs',         'admin/trip-logs*') !!}

                    {!! $section('Workshop') !!}
                    {!! $link('/admin/job-cards',       'clipboard-list',   'Job Cards',         'admin/job-cards*', null, ['admin/job-cards/schedule','admin/job-cards/board']) !!}
                    {!! $link('/admin/job-cards/board', 'layout-grid',      'Job Board',         'admin/job-cards/board') !!}
                    {!! $link('/admin/job-cards/schedule', 'list-ordered',  'Job Schedule',      'admin/job-cards/schedule') !!}
                    {!! $link('/admin/maintenance',     'bell',             'Maintenance',       'admin/maintenance*', $sidebarCounts['urgent_alerts'] ?: null) !!}

                    {!! $section('Inventory') !!}
                    {!! $link('/admin/spare-parts',     'package',          'Inventory',         'admin/spare-parts*', $sidebarCounts['low_stock'] ?: null) !!}
                    {!! $link('/admin/suppliers',       'truck',            'Suppliers',         'admin/suppliers*') !!}
                    {!! $link('/admin/purchase-orders', 'shopping-cart',    'Purchase Orders',   'admin/purchase-orders*', $sidebarCounts['draft_pos'] ?: null) !!}

                    {!! $section('Finance') !!}
                    {!! $link('/admin/invoices',        'receipt',          'Invoices',          'admin/invoices*') !!}

                    {!! $section('People') !!}
                    {!! $link('/admin/users',           'users',            'Users',             'admin/users*') !!}
                    {!! $link('/admin/staff-management/roster', 'user-cog', 'Staff Management', 'admin/staff-management*', $sidebarCounts['pending_leave'] ?: null) !!}
                    {!! $link('/admin/companies',       'building-2',       'Companies',         'admin/companies*') !!}
                    {!! $link('/admin/account-requests','user-x',           'Account Requests',  'admin/account-requests*', $sidebarCounts['pending_requests'] ?: null) !!}

                    {!! $section('System') !!}
                    {!! $link('/pricing/job-types',             'list-checks',      'Job Types',                 'pricing/job-types*') !!}
                    {!! $link('/admin/predictions/proofread',   'trending-down',    'Demand & Predictions',      'admin/predictions/proofread*') !!}
                    {!! $link('/admin/reports',                 'bar-chart-2',      'Reports',                   'admin/reports') !!}
                    {!! $link('/admin/activity-log',            'history',          'Activity Log',              'admin/activity-log*') !!}

                @elseif(auth()->user()->role === 'staff')
                    {!! $section('Overview') !!}
                    {!! $link('/staff/dashboard',       'layout-dashboard', 'Dashboard',         'staff/dashboard') !!}

                    {!! $section('Front Desk') !!}
                    {!! $link('/staff/appointments/queue', 'clock',         'Today\'s Queue',    'staff/appointments/queue') !!}

                    {!! $section('Workshop') !!}
                    {!! $link('/staff/job-cards/board',    'layout-grid',   'Job Board',         'staff/job-cards/board') !!}
                    {!! $link('/staff/job-cards/schedule', 'list-ordered',  'Job Schedule',      'staff/job-cards/schedule') !!}
                    {!! $link('/staff/inventory',       'package',          'Inventory',         'staff/inventory') !!}
                    @if(auth()->user()->hasPermission('pricing.manage'))
                    {!! $link('/pricing/job-types',     'list-checks',      'Job Types',         'pricing/job-types*') !!}
                    @endif

                    {!! $section('Finance') !!}
                    {!! $link('/staff/invoices',        'receipt',          'Invoices',          'staff/invoices*') !!}
                    @if(auth()->user()->hasPermission('invoice.manage'))
                    {!! $link('/staff-management/salary', 'wallet',         'Salary Payments',   'staff-management/salary') !!}
                    @endif

                    {!! $section('My Account') !!}
                    {!! $link('/staff/staff-management/attendance', 'clock-4', 'My Attendance',  'staff/staff-management/attendance') !!}
                    {!! $link('/staff/staff-management/leave',      'calendar-off', 'Leave',     'staff/staff-management/leave') !!}
                    {!! $link('/staff/staff-management/salary',     'wallet',       'My Salary',  'staff/staff-management/salary') !!}

                @elseif(auth()->user()->role === 'coordinator')
                    @php
                        $coordStats = \Illuminate\Support\Facades\Cache::remember('sidebar_counts_coordinator', 30, function () {
                            return [
                                'stale'     => \App\Models\JobCard::where('current_stage','!=','completed')->where('updated_at','<', now()->subHours(3))->count(),
                                'low_stock' => \App\Models\SparePart::whereColumn('stock','<=','min_stock')->count(),
                                'appts'     => \App\Models\Appointment::whereDate('date', today())->where('status','confirmed')->count(),
                            ];
                        });
                    @endphp
                    {!! $section('Overview') !!}
                    {!! $link('/coordinator/dashboard', 'layout-dashboard', 'Dashboard',         'coordinator/dashboard') !!}

                    {!! $section('Front Desk') !!}
                    {!! $link('/coordinator/appointments',       'calendar',        'Appointments',   'coordinator/appointments*', null, 'coordinator/appointments/queue') !!}
                    {!! $link('/coordinator/appointments/queue', 'clock',           'Today\'s Queue', 'coordinator/appointments/queue', $coordStats['appts'] ?: null) !!}

                    {!! $section('Repair Progress') !!}
                    {!! $link('/coordinator/job-cards',          'clipboard-check', 'Job Cards',      'coordinator/job-cards*', $coordStats['stale'] ?: null) !!}

                    {!! $section('Inventory') !!}
                    {!! $link('/coordinator/inventory',          'package',         'Inventory',      'coordinator/inventory*', $coordStats['low_stock'] ?: null) !!}

                    {!! $section('Finance') !!}
                    {!! $link('/coordinator/invoices',           'receipt',         'Invoices',       'coordinator/invoices*') !!}

                    {!! $section('My Account') !!}
                    {!! $link('/coordinator/staff-management/attendance', 'clock-4', 'My Attendance',  'coordinator/staff-management/attendance') !!}
                    {!! $link('/coordinator/staff-management/leave',      'calendar-off', 'Leave',     'coordinator/staff-management/leave') !!}
                    {!! $link('/coordinator/staff-management/salary',     'wallet',       'My Salary',  'coordinator/staff-management/salary') !!}

                @elseif(auth()->user()->role === 'corporate')
                    {!! $section('Overview') !!}
                    {!! $link('/client/dashboard',      'layout-dashboard', 'Dashboard',         'client/dashboard') !!}

                    {!! $section('Fleet') !!}
                    {!! $link('/client/appointments',   'calendar',         'Appointments',      'client/appointments*') !!}
                    {!! $link('/client/vehicles',       'car',              'My Fleet',          'client/vehicles*') !!}
                    {!! $link('/client/trip-logs',      'map-pin',          'Trip Logs',         'client/trip-logs*') !!}
                    {!! $link('/client/maintenance',    'bell',             'Maintenance Alerts','client/maintenance*') !!}

                    {!! $section('Finance') !!}
                    {!! $link('/client/invoices',       'receipt',          'Invoices',          'client/invoices*') !!}
                    {!! $link('/client/statement',      'file-text',        'Fleet Statement',   'client/statement*') !!}

                    {!! $section('Company') !!}
                    {!! $link('/client/company',        'building-2',       'Company',           'client/company*') !!}

                @elseif(auth()->user()->role === 'individual')
                    @php
                        $myAlertBadge = \Illuminate\Support\Facades\Cache::remember('sidebar_alerts_user_'.auth()->id(), 30, function () {
                            return \App\Models\MaintenanceAlert::whereIn(
                                'vehicle_id',
                                \App\Models\Vehicle::where('user_id', auth()->id())->pluck('id')
                            )->where('is_read', false)->count();
                        });
                    @endphp

                    {!! $section('Overview') !!}
                    {!! $link('/customer/dashboard',    'layout-dashboard', 'Dashboard',         'customer/dashboard') !!}

                    {!! $section('My Vehicles') !!}
                    {!! $link('/customer/appointments', 'calendar',         'Appointments',      'customer/appointments*') !!}
                    {!! $link('/customer/vehicles',     'car',              'My Vehicles',       'customer/vehicles*') !!}
                    {!! $link('/customer/trip-logs',    'map-pin',          'Trip Logs',         'customer/trip-logs*') !!}
                    {!! $link('/customer/maintenance',  'bell',             'Maintenance Alerts','customer/maintenance*', $myAlertBadge ?: null) !!}

                    {!! $section('Finance') !!}
                    {!! $link('/customer/invoices',     'receipt',          'Invoices',          'customer/invoices*') !!}
                @endif
            </nav>

            {{-- User info --}}
            <div class="px-4 py-3 border-t border-gray-700">
                <div class="flex items-center gap-3">
                    <img src="{{ auth()->user()->avatar_url }}"
                        class="w-8 h-8 rounded-full object-cover border border-gray-600 shrink-0">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 capitalize">
                            {{ auth()->user()->role }}{{ auth()->user()->staff_role_label ? ' · '.auth()->user()->staff_role_label : '' }}
                        </p>
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

            @php
                $crumbLabels = [
                    'admin' => 'Admin', 'staff' => 'Staff', 'coordinator' => 'Coordinator',
                    'client' => 'Client', 'customer' => 'Customer', 'pricing' => 'Pricing',
                    'dashboard' => 'Dashboard', 'users' => 'Users', 'specialties' => 'Specialties',
                    'role' => 'Role & Permissions',
                    'toggle' => 'Toggle Status', 'companies' => 'Companies',
                    'account-requests' => 'Account Requests', 'approve' => 'Approve', 'reject' => 'Reject',
                    'job-types' => 'Job Types', 'appointments' => 'Appointments', 'walkin' => 'Walk-in',
                    'queue' => "Today's Queue", 'confirm' => 'Confirm', 'cancel' => 'Cancel',
                    'complete' => 'Complete', 'job-cards' => 'Job Cards', 'schedule' => 'Job Schedule',
                    'board' => 'Job Board',
                    'stage' => 'Update Stage', 'parts' => 'Parts', 'symptoms' => 'Symptoms',
                    'labour' => 'Labour', 'checkin' => 'Check-in', 'spare-parts' => 'Inventory', 'inventory' => 'Inventory',
                    'vehicles' => 'Vehicles', 'trip-logs' => 'Trip Logs', 'create' => 'Create', 'edit' => 'Edit', 'reports' => 'Reports',
                    'export' => 'Export', 'maintenance' => 'Maintenance', 'read' => 'Mark Read', 'invoices' => 'Invoices',
                    'generate' => 'Generate', 'payments' => 'Payments', 'resend' => 'Resend Email',
                    'staff-management' => 'Staff Management', 'attendance' => 'Attendance',
                    'clock-in' => 'Clock In', 'clock-out' => 'Clock Out', 'leave' => 'Leave Requests',
                    'performance' => 'Performance', 'email' => 'Email Report', 'salary' => 'Salary', 'suppliers' => 'Suppliers',
                    'purchase-orders' => 'Purchase Orders', 'order' => 'Order', 'receive' => 'Receive',
                    'company' => 'Company', 'profile' => 'Profile', 'activity-log' => 'Activity Log',
                    'capacity' => 'Booking Capacity', 'feedback' => 'Feedback',
                    'notifications' => 'Notifications', 'go' => 'Notification', 'read-all' => 'Mark All Read',
                ];

                $segments = request()->segments();
                $crumbs   = [];
                $accum    = '';
                foreach ($segments as $seg) {
                    $accum .= '/' . $seg;
                    if (is_numeric($seg)) continue;
                    $label = $crumbLabels[$seg] ?? \Illuminate\Support\Str::title(str_replace(['-', '_'], ' ', $seg));
                    $crumbs[] = ['label' => $label, 'href' => $accum];
                }
            @endphp

            {{-- Top bar --}}
            <header class="bg-white border-b px-4 py-3 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    {{-- Hamburger --}}
                    <button @click="sidebarOpen = true"
                        class="lg:hidden text-gray-500 hover:text-gray-700 p-1 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>

                    {{-- Universal back button (hidden on each role's own dashboard, since that's "home") --}}
                    @unless(request()->is('*/dashboard'))
                    <button onclick="history.back()" title="Go back"
                        class="text-gray-400 hover:text-gray-700 p-1 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    @endunless

                    <div class="min-w-0">
                        @if(count($crumbs) > 1)
                        <nav class="hidden sm:flex items-center gap-1.5 text-xs text-gray-500 font-medium truncate mb-0.5">
                            @foreach($crumbs as $i => $crumb)
                                @if($i > 0)<span class="text-gray-300">/</span>@endif
                                @if($i === count($crumbs) - 1)
                                    <span class="text-gray-700 font-semibold truncate">{{ $crumb['label'] }}</span>
                                @else
                                    <a href="{{ $crumb['href'] }}" class="hover:text-blue-600 transition shrink-0">{{ $crumb['label'] }}</a>
                                @endif
                            @endforeach
                        </nav>
                        @endif
                        <h1 class="text-xl font-bold text-gray-900 tracking-tight truncate">@yield('page-title', 'Dashboard')</h1>
                        <p class="text-xs text-gray-500 font-medium hidden sm:block mt-0.5">{{ now()->format('l, d F Y') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">

                    {{-- Notifications bell (admin — surfaces the same counts that badge the sidebar) --}}
                    @if(auth()->user()->role === 'admin')
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.stop="open = false">
                        <button @click="open = !open" class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100" title="Notifications">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            @php $totalAlerts = collect($sidebarCounts ?? [])->sum(); @endphp
                            @if($totalAlerts > 0)
                            <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] leading-none rounded-full w-4 h-4 flex items-center justify-center font-semibold">
                                {{ $totalAlerts > 9 ? '9+' : $totalAlerts }}
                            </span>
                            @endif
                        </button>
                        <div x-show="open" x-transition
                            class="absolute right-0 mt-2 w-72 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-lg border py-2 z-30"
                            style="display:none">
                            <p class="px-4 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wide">Needs Attention</p>
                            @if(!empty($sidebarCounts['pending_appts']))
                            <a href="/admin/appointments?status=pending" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <span>Pending appointments</span>
                                <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full">{{ $sidebarCounts['pending_appts'] }}</span>
                            </a>
                            @endif
                            @if(!empty($sidebarCounts['low_stock']))
                            <a href="/admin/spare-parts" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <span>Low stock parts</span>
                                <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">{{ $sidebarCounts['low_stock'] }}</span>
                            </a>
                            @endif
                            @if(!empty($sidebarCounts['pending_requests']))
                            <a href="/admin/account-requests" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <span>Account requests</span>
                                <span class="bg-purple-100 text-purple-600 text-xs px-2 py-0.5 rounded-full">{{ $sidebarCounts['pending_requests'] }}</span>
                            </a>
                            @endif
                            @if(!empty($sidebarCounts['draft_pos']))
                            <a href="/admin/purchase-orders" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <span>Draft purchase orders</span>
                                <span class="bg-orange-100 text-orange-600 text-xs px-2 py-0.5 rounded-full">{{ $sidebarCounts['draft_pos'] }}</span>
                            </a>
                            @endif
                            @if(!empty($sidebarCounts['pending_leave']))
                            <a href="/admin/staff-management/leave" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <span>Leave requests</span>
                                <span class="bg-blue-100 text-blue-600 text-xs px-2 py-0.5 rounded-full">{{ $sidebarCounts['pending_leave'] }}</span>
                            </a>
                            @endif
                            @if(!empty($sidebarCounts['urgent_alerts']))
                            <a href="/admin/maintenance" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <span>Urgent maintenance</span>
                                <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">{{ $sidebarCounts['urgent_alerts'] }}</span>
                            </a>
                            @endif
                            @if($totalAlerts === 0)
                            <p class="px-4 py-3 text-sm text-gray-400">You're all caught up 🎉</p>
                            @endif
                        </div>
                    </div>

                    {{-- Notifications bell — staff / corporate / individual --}}
                    @elseif(in_array(auth()->user()->role, ['staff','corporate','individual']))
                    @php
                        $myNotifications = \App\Models\Notification::where('user_id', auth()->id())->latest()->take(8)->get();
                        $myUnreadCount   = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
                    @endphp
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.stop="open = false">
                        <button @click="open = !open" class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100" title="Notifications">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            @if($myUnreadCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] leading-none rounded-full w-4 h-4 flex items-center justify-center font-semibold">
                                {{ $myUnreadCount > 9 ? '9+' : $myUnreadCount }}
                            </span>
                            @endif
                        </button>
                        <div x-show="open" x-transition
                            class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-lg border py-2 z-30"
                            style="display:none">
                            <div class="flex justify-between items-center px-4 py-1.5">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Notifications</p>
                                @if($myUnreadCount > 0)
                                <form method="POST" action="/notifications/read-all">
                                    @csrf
                                    <button class="text-xs text-blue-600 hover:underline">Mark all read</button>
                                </form>
                                @endif
                            </div>
                            @forelse($myNotifications as $n)
                            <a href="/notifications/{{ $n->id }}/go" class="block px-4 py-2.5 hover:bg-gray-50 {{ !$n->is_read ? 'bg-blue-50/50' : '' }}">
                                <div class="flex items-start gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full {{ !$n->is_read ? 'bg-blue-500' : '' }} mt-1.5 shrink-0"></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $n->title }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $n->message }}</p>
                                        <p class="text-[11px] text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </a>
                            @empty
                            <p class="px-4 py-3 text-sm text-gray-400">No notifications yet.</p>
                            @endforelse
                        </div>
                    </div>
                    @endif

                    {{-- Profile dropdown --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.stop="open = false">
                        <button @click="open = !open" class="flex items-center gap-2 hover:bg-gray-100 rounded-lg px-2 py-1.5">
                            <img src="{{ auth()->user()->avatar_url }}"
                                class="w-8 h-8 rounded-full object-cover border-2 border-blue-400">
                            <span class="text-sm font-medium text-gray-700 hidden sm:block truncate max-w-32">
                                {{ auth()->user()->name }}
                            </span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 hidden sm:block"></i>
                        </button>
                        <div x-show="open" x-transition
                            class="absolute right-0 mt-2 w-56 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-lg border py-2 z-30"
                            style="display:none">
                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="/profile" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i data-lucide="user" class="w-4 h-4 text-gray-400"></i> My Profile
                            </a>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Flash messages --}}
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="mx-4 mt-4 p-3.5 bg-emerald-50 text-emerald-800 rounded-xl text-sm border border-emerald-200 no-print flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5 min-w-0">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span class="truncate font-medium">{{ session('success') }}</span>
                </div>
                <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-800 p-1 rounded-md transition shrink-0 ml-2" title="Dismiss">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            @endif
            @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="mx-4 mt-4 p-3.5 bg-rose-50 text-rose-800 rounded-xl text-sm border border-rose-200 no-print flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5 min-w-0">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span class="truncate font-medium">{{ session('error') }}</span>
                </div>
                <button type="button" @click="show = false" class="text-rose-500 hover:text-rose-800 p-1 rounded-md transition shrink-0 ml-2" title="Dismiss">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            @endif

            {{-- Page content --}}
            <main id="main-content" class="flex-1 overflow-y-auto p-4 pb-24 lg:p-6 lg:pb-10 focus:outline-none" tabindex="-1">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Chatbot widget --}}
    @include('partials.chatbot')

    {{-- Global confirm dialog — replaces native confirm() popups with a
         styled modal. Trigger from any form via:
         onsubmit="return confirmSubmit(event, {title:'...', message:'...', confirmLabel:'Delete'})" --}}
    <div x-data
        x-show="$store.confirmDialog.open"
        x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[60] flex items-center justify-center p-4 no-print"
        style="display:none">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-sm p-6" @click.outside="$store.confirmDialog.close()">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-800" x-text="$store.confirmDialog.title"></h3>
            </div>
            <p class="text-sm text-gray-500 mb-5" x-text="$store.confirmDialog.message"></p>
            <div class="flex gap-3 justify-end">
                <button type="button" @click="$store.confirmDialog.close()"
                    class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" @click="$store.confirmDialog.confirm()"
                    class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition shadow-xs"
                    x-text="$store.confirmDialog.confirmLabel">
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('confirmDialog', {
                open: false,
                title: 'Are you sure?',
                message: 'This action cannot be undone.',
                confirmLabel: 'Confirm',
                pendingForm: null,
                request(form, options = {}) {
                    this.pendingForm  = form;
                    this.title        = options.title || 'Are you sure?';
                    this.message      = options.message || 'This action cannot be undone.';
                    this.confirmLabel = options.confirmLabel || 'Confirm';
                    this.open = true;
                },
                confirm() {
                    if (this.pendingForm) this.pendingForm.submit();
                    this.close();
                },
                close() {
                    this.open = false;
                    this.pendingForm = null;
                },
            });
        });

        function confirmSubmit(event, options = {}) {
            event.preventDefault();
            window.Alpine.store('confirmDialog').request(event.target, options);
            return false;
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>