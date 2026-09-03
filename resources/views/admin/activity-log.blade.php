@extends('layouts.app')
@section('page-title', 'Activity Log')

@section('content')

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-700">
            System Activity Log
            <span class="text-sm font-normal text-gray-400 ml-2">{{ $logs->total() }} records</span>
        </h2>
        <p class="text-xs text-gray-400 mt-1">Tracks key administrative and financial actions across the system.</p>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-500 border-b">
                <th class="px-4 py-3">When</th>
                <th class="px-4 py-3">User</th>
                <th class="px-4 py-3">Action</th>
                <th class="px-4 py-3">Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-400 whitespace-nowrap">
                    {{ $log->created_at->format('d M Y, H:i') }}
                </td>
                <td class="px-4 py-3 font-medium">{{ $log->user->name ?? 'System' }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-mono">{{ $log->action }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $log->description }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-8 text-center text-gray-400">No activity recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($logs->hasPages())
    <div class="px-4 py-3 border-t">{{ $logs->links() }}</div>
    @endif
</div>
@endsection