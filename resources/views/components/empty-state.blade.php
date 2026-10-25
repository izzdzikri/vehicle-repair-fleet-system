@props([
    'icon' => 'inbox',
    'title' => 'Nothing here yet',
    'subtitle' => null,
    'actionHref' => null,
    'actionLabel' => null,
])

<div class="text-center py-12">
    <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-3">
        <i data-lucide="{{ $icon }}" class="w-7 h-7 text-gray-300"></i>
    </div>
    <p class="text-gray-500 font-medium">{{ $title }}</p>
    @if($subtitle)
    <p class="text-gray-400 text-sm mt-1">{{ $subtitle }}</p>
    @endif
    @if($actionHref && $actionLabel)
    <a href="{{ $actionHref }}" class="inline-flex items-center gap-1.5 mt-4 text-xs font-bold bg-slate-900 text-white px-4 py-2.5 rounded-xl hover:bg-slate-800 shadow-xs transition">
        {{ $actionLabel }}
    </a>
    @endif
</div>