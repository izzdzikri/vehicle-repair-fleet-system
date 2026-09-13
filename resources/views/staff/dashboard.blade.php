@extends('layouts.app')
@section('page-title', 'Staff Dashboard')

@section('content')

@include('partials.greeting')

{{-- Today summary --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="clipboard-list" class="w-6 h-6 text-blue-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">My Active Jobs</p>
        <p class="text-3xl font-bold text-blue-600">{{ $myJobs->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="calendar" class="w-6 h-6 text-green-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Today's Appointments</p>
        <p class="text-3xl font-bold text-green-600">{{ $todayAppointments->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <i data-lucide="alert-circle" class="w-6 h-6 text-red-500 mx-auto mb-1"></i>
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Urgent (Repairing)</p>
        <p class="text-3xl font-bold text-red-500">
            {{ $myJobs->where('current_stage', 'repairing')->count() }}
        </p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center flex flex-col items-center justify-center">
        <a href="/staff/appointments/walkin"
            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm font-medium flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            New Walk-in
        </a>
        <p class="text-xs text-gray-400 mt-2">Create a walk-in appointment</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- My Active Jobs --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">My Active Job Cards</h2>
        @forelse($myJobs as $job)
        <div class="border rounded-lg p-4 mb-3 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-gray-800">
                            {{ $job->vehicle->plate_number ?? '—' }}
                        </p>
                        @if($job->appointment && $job->appointment->is_walkin)
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Walk-in</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500">
                        {{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}
                    </p>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $job->jobType->name ?? $job->appointment->service_type ?? '—' }}
                    </p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-medium
                    {{ $job->current_stage === 'repairing'     ? 'bg-blue-100 text-blue-700' :
                      ($job->current_stage === 'quality_check' ? 'bg-purple-100 text-purple-700' :
                      ($job->current_stage === 'waiting_parts' ? 'bg-red-100 text-red-700' :
                      'bg-yellow-100 text-yellow-700')) }}">
                    {{ ucfirst(str_replace('_', ' ', $job->current_stage)) }}
                </span>
            </div>

            {{-- Mini stage progress bar --}}
            @php
                $stages  = ['received','diagnosing','waiting_parts','repairing','quality_check','completed'];
                $current = array_search($job->current_stage, $stages);
            @endphp
            <div class="flex gap-1 mt-2 mb-3">
                @foreach($stages as $i => $stage)
                <div class="flex-1 h-1.5 rounded-full {{ $i <= $current ? 'bg-blue-500' : 'bg-gray-200' }}"></div>
                @endforeach
            </div>

            @if($job->is_stale)
            <div class="mb-2 inline-flex items-center gap-1 text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full font-medium animate-pulse">
                <i data-lucide="alarm-clock" class="w-3 h-3"></i>
                No update in {{ $job->hours_since_update }}h — please update this job's status
            </div>
            @endif

            @if($job->estimated_completion)
            <p class="text-xs {{ $job->estimated_completion->isPast() ? 'text-red-500 font-medium' : 'text-gray-400' }} mb-2">
                Est: {{ $job->estimated_completion->format('d M Y H:i') }}
                @if($job->estimated_completion->isPast())
                ⚠ Overdue
                @endif
            </p>
            @endif

            {{-- Symptoms summary if filled --}}
            @if($job->symptoms && count($job->symptoms) > 0)
            <div class="flex flex-wrap gap-1 mb-2">
                @foreach(array_slice($job->symptoms, 0, 3) as $s)
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $s }}</span>
                @endforeach
                @if(count($job->symptoms) > 3)
                <span class="text-xs text-gray-400">+{{ count($job->symptoms) - 3 }} more</span>
                @endif
            </div>
            @endif

            <a href="/staff/job-cards/{{ $job->id }}"
                class="text-sm text-blue-600 hover:underline font-medium">
                Update stage →
            </a>
        </div>
        @empty
        <x-empty-state icon="inbox" title="No active jobs assigned" subtitle="New assignments will show up here as they come in." />
        @endforelse
    </div>

    {{-- Today's Appointments --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">
                Today's Appointments
                <span class="text-sm font-normal text-gray-400 ml-1">{{ now()->format('d M Y') }}</span>
            </h2>
            <a href="/staff/appointments/walkin"
                class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200 font-medium flex items-center gap-1">
                <i data-lucide="plus" class="w-3 h-3"></i> Walk-in
            </a>
        </div>
        @forelse($todayAppointments as $apt)
        <div class="border rounded-lg p-3 mb-3">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-sm">{{ $apt->service_type }}</p>
                        @if($apt->is_walkin)
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Walk-in</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $apt->vehicle->plate_number ?? '—' }}
                        — {{ $apt->vehicle->brand ?? '' }} {{ $apt->vehicle->model ?? '' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        @if($apt->is_walkin)
                            {{ $apt->walkin_name ?? 'Walk-in' }}
                            {{ $apt->walkin_contact ? '· ' . $apt->walkin_contact : '' }}
                        @else
                            {{ $apt->user->name ?? '—' }}
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-sm text-blue-600">{{ $apt->time }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ $apt->status === 'confirmed'  ? 'bg-green-100 text-green-700' :
                          ($apt->status === 'completed'  ? 'bg-blue-100 text-blue-700' :
                          'bg-yellow-100 text-yellow-700') }}">
                        {{ ucfirst($apt->status) }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <x-empty-state icon="calendar-x" title="No appointments today" subtitle="Enjoy the quiet — or add a walk-in if one arrives." />
        @endforelse
    </div>

</div>

{{-- Quick Actions --}}
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Quick Actions</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <a href="/staff/appointments/queue" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="clock" class="w-5 h-5 text-blue-500"></i>
            <span class="text-xs font-medium text-gray-600">Today's Queue</span>
        </a>
        <a href="/staff/job-cards/schedule" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="list-ordered" class="w-5 h-5 text-purple-500"></i>
            <span class="text-xs font-medium text-gray-600">Job Schedule</span>
        </a>
        <a href="/staff/invoices" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="receipt" class="w-5 h-5 text-green-600"></i>
            <span class="text-xs font-medium text-gray-600">Invoices</span>
        </a>
        <a href="/staff/staff-management/attendance" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="clock-4" class="w-5 h-5 text-orange-500"></i>
            <span class="text-xs font-medium text-gray-600">My Attendance</span>
        </a>
        <a href="/staff/staff-management/leave" class="flex flex-col items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 text-center transition">
            <i data-lucide="calendar-off" class="w-5 h-5 text-red-500"></i>
            <span class="text-xs font-medium text-gray-600">Leave</span>
        </a>
    </div>
</div>

@endsection