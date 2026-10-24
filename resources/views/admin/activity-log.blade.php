@extends('layouts.app')
@section('page-title', 'Activity Log')

@section('content')

<div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">System Audit & Activity Log</h2>
                <p class="text-xs font-medium text-gray-500 mt-0.5">Comprehensive audit trail of security, billing, and operational changes</p>
            </div>
            <span class="text-xs font-bold px-3 py-1 bg-gray-100 text-gray-700 rounded-full border border-gray-200">
                {{ $logs->total() }} recorded events
            </span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/75 border-b border-gray-200">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="py-3 px-4">Timestamp</th>
                    <th class="py-3 px-4">Authorized User</th>
                    <th class="py-3 px-4">Action Type</th>
                    <th class="py-3 px-4">Audit Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50/80 transition">
                    <td class="py-3.5 px-4 text-xs font-mono font-medium text-gray-500 whitespace-nowrap">
                        {{ $log->created_at->format('d M Y, H:i') }}
                    </td>
                    <td class="py-3.5 px-4">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold border border-blue-100">
                                {{ substr($log->user->name ?? 'S', 0, 1) }}
                            </div>
                            <span class="font-bold text-gray-900 text-sm">{{ $log->user->name ?? 'System Automated' }}</span>
                        </div>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="text-xs font-mono font-bold bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-gray-700 font-medium leading-relaxed">
                        {{ $log->description }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-12 text-center text-gray-400">
                        <i data-lucide="shield-check" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
                        <p class="text-sm font-semibold text-gray-600">No activity recorded yet</p>
                        <p class="text-xs text-gray-400">System actions will appear here in real time.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
