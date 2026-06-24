<aside class="w-64 bg-gray-900 text-white flex flex-col">
    <div class="px-6 py-5 text-xl font-bold border-b border-gray-700">
        🚗 Vehicle System
    </div>
    <nav class="flex-1 px-4 py-4 space-y-1">

        @if(auth()->user()->role === 'admin')
            <a href="/admin/dashboard" class="block px-4 py-2 rounded hover:bg-gray-700">Dashboard</a>
            <a href="/admin/job-cards" class="block px-4 py-2 rounded hover:bg-gray-700">Job Cards</a>
            <a href="/admin/spare-parts" class="block px-4 py-2 rounded hover:bg-gray-700">Inventory</a>
            <a href="/admin/vehicles" class="block px-4 py-2 rounded hover:bg-gray-700">Vehicles</a>
            <a href="/admin/reports" class="block px-4 py-2 rounded hover:bg-gray-700">Reports</a>

        @elseif(auth()->user()->role === 'staff')
            <a href="/staff/dashboard" class="block px-4 py-2 rounded hover:bg-gray-700">Dashboard</a>
            <a href="/staff/inventory" class="block px-4 py-2 rounded hover:bg-gray-700">Inventory</a>

        @elseif(auth()->user()->role === 'corporate')
            <a href="/client/dashboard" class="block px-4 py-2 rounded hover:bg-gray-700">Dashboard</a>
            <a href="/client/appointments" class="block px-4 py-2 rounded hover:bg-gray-700">Appointments</a>
            <a href="/client/vehicles" class="block px-4 py-2 rounded hover:bg-gray-700">My Vehicles</a>
            <a href="/client/maintenance" class="block px-4 py-2 rounded hover:bg-gray-700">Maintenance Alerts</a>

        @elseif(auth()->user()->role === 'individual')
            <a href="/customer/dashboard" class="block px-4 py-2 rounded hover:bg-gray-700">Dashboard</a>
            <a href="/customer/appointments" class="block px-4 py-2 rounded hover:bg-gray-700">Appointments</a>
            <a href="/customer/vehicles" class="block px-4 py-2 rounded hover:bg-gray-700">My Vehicles</a>
        @endif

    </nav>
</aside>