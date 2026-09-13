@php
    $hour = (int) now()->format('G');
    $timeGreeting = match(true) {
        $hour < 12 => 'Good morning',
        $hour < 17 => 'Good afternoon',
        default    => 'Good evening',
    };
    $firstName = explode(' ', auth()->user()->name)[0] ?? auth()->user()->name;
@endphp
<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800">{{ $timeGreeting }}, {{ $firstName }}</h2>
    <p class="text-sm text-gray-400 mt-0.5">{{ now()->format('l, d F Y') }}</p>
</div>