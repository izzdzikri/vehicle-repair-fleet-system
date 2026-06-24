<aside class="w-64 bg-gray-900 text-white flex flex-col">
    <div class="px-6 py-5 font-bold border-b border-gray-700 flex items-center gap-2">
        <i data-lucide="wrench" class="w-5 h-5 text-blue-400"></i>
        <span class="text-white text-lg">Vehicle System</span>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

        @php
        $link = fn($href, $icon, $label, $pattern, $badge = null) =>
            '<a href="'.$href.'" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-gray-700 '.
            (request()->is(ltrim($pattern,'/')) ? 'bg-gray-700 text-white' : 'text-gray-300').
            '"><i data-lucide="'.$icon.'" class="w-4 h-4 shrink-0"></i><span class="flex-1">'.$label.'</span>'.
            ($badge ? '<span class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">'.$badge.'</span>' : '').
            '</a>';
        @endphp

        @if(auth()->user()->role === 'admin')
            @php
                $pendingBadge = \App\Models\Appointment::where('status','pending')->count();
                $lowBadge = \App\Models\SparePart::whereColumn('stock','<=','min_stock')->count();
            @endphp
            {!! $link('/admin/dashboard',    'layout-dashboard', 'Dashboard',    'admin/dashboard') !!}
            {!! $link('/admin/appointments', 'calendar',         'Appointments', 'admin/appointments*', $pendingBadge ?: null) !!}
            {!! $link('/admin/job-cards',    'clipboard-list',   'Job Cards',    'admin/job-cards*') !!}
            {!! $link('/admin/spare-parts',  'package',          'Inventory',    'admin/spare-parts*', $lowBadge ?: null) !!}
            {!! $link('/admin/vehicles',     'car',              'Vehicles',     'admin/vehicles*') !!}
            {!! $link('/admin/users',        'users',            'Users',        'admin/users*') !!}
            {!! $link('/admin/companies',    'building-2',       'Companies',    'admin/companies*') !!}
            {!! $link('/admin/job-types',    'list-checks',      'Job Types',    'admin/job-types*') !!}
            {!! $link('/admin/maintenance',  'bell',             'Maintenance',  'admin/maintenance*') !!}
            {!! $link('/admin/reports',      'bar-chart-2',      'Reports',      'admin/reports') !!}
            {!! $link('/admin/account-requests', 'user-x', 'Account Requests', 'admin/account-requests*',
    \App\Models\AccountRequest::where('status','pending')->count() ?: null) !!}

        @elseif(auth()->user()->role === 'staff')
            {!! $link('/staff/dashboard',    'layout-dashboard', 'Dashboard',    'staff/dashboard') !!}
            {!! $link('/staff/inventory',    'package',          'Inventory',    'staff/inventory') !!}

        @elseif(auth()->user()->role === 'corporate')
            {!! $link('/client/dashboard',    'layout-dashboard', 'Dashboard',         'client/dashboard') !!}
            {!! $link('/client/appointments', 'calendar',         'Appointments',      'client/appointments*') !!}
            {!! $link('/client/vehicles',     'car',              'My Vehicles',       'client/vehicles*') !!}
            {!! $link('/client/maintenance',  'bell',             'Maintenance Alerts','client/maintenance*') !!}
            {!! $link('/client/company', 'building-2', 'Company', 'client/company*') !!}

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
            {!! $link('/customer/maintenance',  'bell',             'Maintenance Alerts','customer/maintenance*', $myAlertBadge ?: null) !!}
        @endif

    </nav>

    <div class="px-4 py-3 border-t border-gray-700">
        <div class="flex items-center gap-3">
            <img src="{{ auth()->user()->avatar_url }}"
                class="w-8 h-8 rounded-full object-cover border border-gray-600">
            <div class="min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-400 capitalize">{{ auth()->user()->role }}</p>
            </div>
        </div>
    </div>
</aside>
