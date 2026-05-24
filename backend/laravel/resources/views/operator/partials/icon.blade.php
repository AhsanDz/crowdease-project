{{--
    Reusable inline-SVG icons. Pemakaian:
        @include('operator.partials.icon', ['name' => 'dashboard'])
        @include('operator.partials.icon', ['name' => 'bus', 'size' => 20])

    Nilai 'name' yang valid: dashboard, map, bus, stop, key, webhook,
    bell, signal, refresh, chevron-down, logout.
--}}
@php
    $size = $size ?? 18;
    $svgs = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'map'       => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14"/><path d="M15 6v14"/>',
        'bus'       => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 14h18"/><circle cx="7" cy="17" r="1.5"/><circle cx="17" cy="17" r="1.5"/>',
        'stop'      => '<path d="M12 22s7-7.5 7-13a7 7 0 1 0-14 0c0 5.5 7 13 7 13Z"/><circle cx="12" cy="9" r="2"/>',
        'key'       => '<circle cx="8" cy="15" r="4"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
        'webhook'   => '<path d="M18 16.98h-5.99c-1.66 0-3.01-1.34-3.01-3s1.34-3 3.01-3H18"/><path d="M5.02 16.98h6.98"/><path d="M11.92 13.98c-1.66-1.66-1.66-4.34 0-6 1.66-1.66 4.34-1.66 6 0 1.66 1.66 1.66 4.34 0 6"/>',
        'bell'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'signal'    => '<path d="M2 12h2"/><path d="M6 8v8"/><path d="M10 4v16"/><path d="M14 8v8"/><path d="M18 12h2"/>',
        'refresh'   => '<path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
    ];
    $path = $svgs[$name] ?? $svgs['dashboard'];
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true">
    {!! $path !!}
</svg>
