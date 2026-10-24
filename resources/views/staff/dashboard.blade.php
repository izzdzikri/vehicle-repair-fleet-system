@extends('layouts.app')
@section('page-title', 'Staff Dashboard')

@section('content')

@include('partials.greeting')

{{-- Today summary --}}
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">My Active Jobs</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $myJobs->count() }}</p>
            <p class="text-xs text-gray-400 font-medium mt-1">assigned repairs</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Today's Schedule</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="calendar" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $todayAppointments->count() }}</p>
            <p class="text-xs text-gray-400 font-medium mt-1">booked appointments</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">In Progress (Repairing)</span>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="wrench" class="w-4 h-4"></i>
            </div>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight">
                {{ $myJobs->where('current_stage', 'repairing')->count() }}
            </p>
            <p class="text-xs text-gray-400 font-medium mt-1">active bay jobs</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-5 flex flex-col justify-center items-center text-center">
        <a href="/staff/appointments/walkin"
            class="bg-slate-900 text-white px-4 py-2.5 rounded-xl hover:bg-slate-800 text-xs font-semibold flex items-center gap-2 transition shadow-xs">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            New Walk-in Intake
        </a>
        <p class="text-xs text-gray-500 mt-2">Fast check-in for arriving drivers</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- My Active Jobs --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
            <div>
                <h2 class="text-base font-bold text-gray-900 tracking-tight">My Assigned Job Cards</h2>
                <p class="text-xs text-gray-500 mt-0.5">Vehicles currently in your repair queue</p>
            </div>
            <a href="/staff/job-cards/board" class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                Kanban &rarr;
            </a>
        </div>
        <div class="space-y-3">
        @forelse($myJobs as $job)
        <div class="border border-gray-200 rounded-xl p-4 hover:border-gray-300 hover:bg-gray-50/50 transition">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                            {{ $job->vehicle->plate_number ?? '—' }}
                        </span>
                        @if($job->appointment && $job->appointment->is_walkin)
                        <span class="text-[11px] font-bold uppercase bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded">Walk-in</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $job->vehicle->brand ?? '' }} {{ $job->vehicle->model ?? '' }}
                    </p>
                    <p class="text-sm font-bold text-gray-900 mt-1">
                        {{ $job->jobType->name ?? $job->appointment->service_type ?? 'General Service' }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border
                    {{ $job->current_stage === 'repairing'     ? 'bg-blue-50 text-blue-700 border-blue-200' :
                      ($job->current_stage === 'quality_check' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                      ($job->current_stage === 'waiting_parts' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                      'bg-amber-50 text-amber-700 border-amber-200')) }}">
                    {{ ucfirst(str_replace('_', ' ', $job->current_stage)) }}
                </span>
            </div>

            {{-- Mini stage progress bar --}}
            @php
                $stages  = ['received','diagnosing','waiting_parts','repairing','quality_check','completed'];
                $current = array_search($job->current_stage, $stages);
            @endphp
            <div class="flex gap-1 mt-2.5 mb-3">
                @foreach($stages as $i => $stage)
                <div class="flex-1 h-1.5 rounded-full {{ $i <= $current ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                @endforeach
            </div>

            @if($job->is_stale)
            <div class="mb-2 inline-flex items-center gap-1.5 text-xs bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1 rounded-full font-bold animate-pulse">
                <i data-lucide="alarm-clock" class="w-3.5 h-3.5 text-rose-500"></i>
                No update in {{ $job->hours_since_update }}h — please update stage
            </div>
            @endif

            @if($job->estimated_completion)
            <p class="text-xs {{ $job->estimated_completion->isPast() ? 'text-rose-600 font-bold' : 'text-gray-500 font-medium' }} mb-2">
                Target: {{ $job->estimated_completion->format('d M Y, H:i') }}
                @if($job->estimated_completion->isPast())
                &bull; Overdue
                @endif
            </p>
            @endif

            {{-- Symptoms summary if filled --}}
            @if($job->symptoms && count($job->symptoms) > 0)
            <div class="flex flex-wrap gap-1 mb-2.5">
                @foreach(array_slice($job->symptoms, 0, 3) as $s)
                <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded border border-gray-200">{{ $s }}</span>
                @endforeach
                @if(count($job->symptoms) > 3)
                <span class="text-xs text-gray-400 font-medium">+{{ count($job->symptoms) - 3 }} more</span>
                @endif
            </div>
            @endif

            <div class="pt-2 border-t border-gray-100 text-right">
                <a href="/staff/job-cards/{{ $job->id }}"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                    Manage Job Order &rarr;
                </a>
            </div>
        </div>
        @empty
        <x-empty-state icon="inbox" title="No active jobs assigned" subtitle="New assignments will show up here as they come in." />
        @endforelse
        </div>
    </div>

    {{-- Today's Appointments --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
            <div>
                <h2 class="text-base font-bold text-gray-900 tracking-tight">Today's Bay Arrivals</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ now()->format('l, d F Y') }}</p>
            </div>
            <a href="/staff/appointments/queue"
                class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
                Queue &rarr;
            </a>
        </div>
        <div class="space-y-3">
        @forelse($todayAppointments as $apt)
        <div class="border border-gray-200 rounded-xl p-3.5 hover:bg-gray-50/50 transition">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-bold text-sm text-gray-900">{{ $apt->service_type }}</p>
                        @if($apt->is_walkin)
                        <span class="text-[11px] font-bold uppercase bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded">Walk-in</span>
                        @endif
                    </div>
                    <p class="text-xs font-mono font-bold text-gray-700 mt-1">
                        {{ $apt->vehicle->plate_number ?? '—' }}
                        <span class="font-sans font-normal text-gray-500">({{ $apt->vehicle->brand ?? '' }} {{ $apt->vehicle->model ?? '' }})</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if($apt->is_walkin)
                            {{ $apt->walkin_name ?? 'Walk-in' }}
                            {{ $apt->walkin_contact ? '· ' . $apt->walkin_contact : '' }}
                        @else
                            {{ $apt->user->name ?? '—' }}
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-sm text-blue-600">{{ $apt->time }}</p>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold border mt-1
                        {{ $apt->status === 'confirmed'  ? 'bg-blue-50 text-blue-700 border-blue-200' :
                          ($apt->status === 'completed'  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          'bg-amber-50 text-amber-700 border-amber-200') }}">
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

</div>

{{-- Quick Actions --}}
<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs p-6">
    <div class="mb-4">
        <h2 class="text-base font-bold text-gray-900 tracking-tight">Mechanic Quick Shortcuts</h2>
        <p class="text-xs text-gray-500 mt-0.5">Daily tools for the workshop floor</p>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <a href="/staff/appointments/queue" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 hover:border-blue-400 hover:bg-blue-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-blue-700">Today's Queue</span>
        </a>
        <a href="/staff/job-cards/schedule" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 hover:border-purple-400 hover:bg-purple-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white flex items-center justify-center transition">
                <i data-lucide="list-ordered" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-purple-700">Job Schedule</span>
        </a>
        <a href="/staff/invoices" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 hover:border-emerald-400 hover:bg-emerald-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white flex items-center justify-center transition">
                <i data-lucide="receipt" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-emerald-700">Invoices</span>
        </a>
        <a href="/staff/staff-management/attendance" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 hover:border-amber-400 hover:bg-amber-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white flex items-center justify-center transition">
                <i data-lucide="clock-4" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-amber-700">My Attendance</span>
        </a>
        <a href="/staff/staff-management/leave" class="group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-gray-200 hover:border-rose-400 hover:bg-rose-50/30 text-center transition">
            <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 group-hover:bg-rose-600 group-hover:text-white flex items-center justify-center transition">
                <i data-lucide="calendar-off" class="w-5 h-5"></i>
            </div>
            <span class="text-xs font-semibold text-gray-800 group-hover:text-rose-700">Leave Requests</span>
        </a>
    </div>
</div>

@endsection