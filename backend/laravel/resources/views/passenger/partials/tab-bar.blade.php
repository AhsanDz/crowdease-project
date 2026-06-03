{{--
    Tab bar bawah untuk halaman penumpang — 3 tab.
    Aktif state auto-detect dari route name; bisa di-override via param 'active'.

    Pemakaian (semua bekerja):
      @include('passenger.partials.tab-bar')
      @include('passenger.partials.tab-bar', ['active' => 'home'])
      @include('passenger.partials.tab-bar', ['active' => 'map'])
      @include('passenger.partials.tab-bar', ['active' => 'stops'])
--}}
@php
    if (!isset($active)) {
        if (request()->routeIs('passenger.map') || request()->routeIs('passenger.peta')) {
            $active = 'map';
        } elseif (request()->routeIs('passenger.stops') || request()->routeIs('passenger.stop-detail')) {
            $active = 'stops';
        } elseif (request()->routeIs('home')) {
            $active = 'home';
        } else {
            $active = null;
        }
    }

    $colorFor = fn ($id) => $active === $id ? 'var(--brand)' : 'var(--ink-400)';
@endphp

<nav class="border-t bg-white grid"
     style="border-color: var(--ink-100); grid-template-columns: repeat(3, 1fr)">

    <a href="{{ route('home') }}"
       class="flex flex-col items-center gap-1 transition"
       style="padding: 10px 8px 12px;
              color: {{ $colorFor('home') }};
              font-weight: 600; font-size: 11px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 11 12 4l9 7"/>
            <path d="M5 10v10h14V10"/>
        </svg>
        Beranda
    </a>

    <a href="{{ route('passenger.peta') }}"
       class="flex flex-col items-center gap-1 transition"
       style="padding: 10px 8px 12px;
              color: {{ $colorFor('map') }};
              font-weight: 600; font-size: 11px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Z"/>
            <path d="M9 4v14"/>
            <path d="M15 6v14"/>
        </svg>
        Peta
    </a>

    <a href="{{ route('passenger.stops') }}"
       class="flex flex-col items-center gap-1 transition"
       style="padding: 10px 8px 12px;
              color: {{ $colorFor('stops') }};
              font-weight: 600; font-size: 11px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 22s7-7.5 7-13a7 7 0 1 0-14 0c0 5.5 7 13 7 13Z"/>
            <path d="M12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
        </svg>
        Halte
    </a>
</nav>
