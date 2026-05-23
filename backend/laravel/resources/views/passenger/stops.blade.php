@extends('layouts.passenger')

@section('title', 'Halte — CrowdEase')

@section('content')

{{-- Header --}}
<header class="bg-white border-b px-5 py-3.5 flex items-center justify-between" style="border-color: var(--ink-100)">
    <div class="flex items-center gap-2.5">
        <div class="logo">C</div>
        <div>
            <div class="font-extrabold text-[15px] leading-tight" style="letter-spacing:-0.2px">CrowdEase</div>
            <div class="text-[11.5px]" style="color: var(--ink-400); margin-top:-1px">Cari halte koridor</div>
        </div>
    </div>
</header>

{{-- Main content --}}
<main class="flex-1 overflow-hidden flex flex-col">

    {{-- Search box + title --}}
    <div style="padding: 14px 20px 0">
        <div class="flex items-center gap-2 mb-3">
            <a href="{{ route('home') }}" class="flex items-center justify-center bg-white border"
               style="width:32px; height:32px; border-radius:8px; border-color:var(--ink-100)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 6-6 6 6 6"/>
                </svg>
            </a>
            <div class="font-extrabold" style="font-size:17px; letter-spacing:-0.3px">Halte</div>
        </div>

        {{-- Search input --}}
        <div class="relative flex items-center">
            <span class="absolute flex" style="left:12px; color:var(--ink-400)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14Z"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>
            </span>
            <input id="search" type="text" placeholder="Cari halte (mis. Senayan, Dukuh Atas)..."
                   class="w-full"
                   style="padding:10px 12px 10px 38px;
                          background:#fff;
                          border:1px solid var(--ink-200);
                          border-radius:8px;
                          font-size:14px;
                          outline:none"
                   oninput="filterStops(this.value)">
        </div>
    </div>

    {{-- Scrollable result list --}}
    <div class="flex-1 overflow-auto" style="padding: 12px 20px 20px">
        <div id="count-info" style="font-size: 11.5px; color: var(--ink-400); margin-bottom: 12px">
            Memuat halte...
        </div>

        {{-- Loading skeleton --}}
        <div id="loading" class="flex flex-col gap-2">
            @for ($i = 0; $i < 8; $i++)
                <div class="bg-white border animate-pulse" style="height:62px; border-color:var(--ink-100); border-radius:12px"></div>
            @endfor
        </div>

        {{-- Error --}}
        <div id="error" class="hidden p-4 text-center"
             style="background:var(--c-high-bg); border:1px solid var(--c-high-ring); border-radius:12px">
            <p class="font-semibold mb-2" style="color:var(--c-high)" id="error-msg">Gagal memuat halte</p>
            <button onclick="loadAllStops()" class="text-sm underline" style="color:var(--c-high)">Coba lagi</button>
        </div>

        {{-- Empty state --}}
        <div id="empty" class="hidden text-center" style="padding: 32px 16px; color: var(--ink-400); font-size: 13px">
            Tidak ada halte yang cocok dengan pencarian.
        </div>

        {{-- Result list --}}
        <div id="stops-list" class="hidden flex-col gap-2"></div>
    </div>
</main>

{{-- Tab bar --}}
@include('passenger.partials.tab-bar', ['active' => 'stops'])

@push('scripts')
<script>
let allStops = []; // flat list: each stop with .route embedded
let filteredStops = [];

async function loadAllStops() {
    setState('loading');
    try {
        // 1. Fetch all routes
        const routesRes = await fetch('/api/v1/routes');
        const routesJson = await routesRes.json();
        if (!routesJson.success) throw new Error(routesJson.error?.message || 'API gagal');
        const routes = routesJson.data;

        // 2. Fetch stops for each route in parallel
        const stopArrays = await Promise.all(
            routes.map(r =>
                fetch(`/api/v1/routes/${r.id}/stops`)
                    .then(r => r.json())
                    .then(j => j.success ? j.data : [])
                    .catch(() => [])
            )
        );

        // 3. Flatten: stop + its route info
        allStops = [];
        routes.forEach((r, idx) => {
            stopArrays[idx].forEach(s => {
                allStops.push({ ...s, route: r });
            });
        });

        filteredStops = allStops;
        renderStops();
        setState(allStops.length === 0 ? 'empty' : 'stops-list');
    } catch (e) {
        document.getElementById('error-msg').textContent = e.message;
        setState('error');
    }
}

function filterStops(query) {
    const q = query.toLowerCase().trim();
    filteredStops = q
        ? allStops.filter(s => s.name.toLowerCase().includes(q) || s.route.name.toLowerCase().includes(q))
        : allStops;
    renderStops();
    setState(filteredStops.length === 0 ? 'empty' : 'stops-list');
}

function setState(active) {
    ['loading', 'error', 'empty', 'stops-list'].forEach(id => {
        const el = document.getElementById(id);
        if (id === active) {
            el.classList.remove('hidden');
            if (id === 'stops-list') el.classList.add('flex');
        } else {
            el.classList.add('hidden');
            el.classList.remove('flex');
        }
    });
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}

function renderStops() {
    document.getElementById('count-info').textContent =
        `${filteredStops.length} halte · pilih untuk lihat detail`;

    const list = document.getElementById('stops-list');

    // "major" = halte awal/akhir koridor (sequence 1 atau terakhir)
    // Karena kita tidak punya kolom 'major' di schema, kita derive di client:
    // grouping by route_id, get min/max sequence per route
    const routeMinMax = {};
    allStops.forEach(s => {
        if (!routeMinMax[s.route.id]) routeMinMax[s.route.id] = { min: s.sequence, max: s.sequence };
        else {
            routeMinMax[s.route.id].min = Math.min(routeMinMax[s.route.id].min, s.sequence);
            routeMinMax[s.route.id].max = Math.max(routeMinMax[s.route.id].max, s.sequence);
        }
    });

    list.innerHTML = filteredStops.map(s => {
        const isMajor = (s.sequence === routeMinMax[s.route.id]?.min || s.sequence === routeMinMax[s.route.id]?.max);
        return `
            <a href="/halte/${s.id}"
               class="flex items-center gap-3 bg-white border hover:shadow-sm transition"
               style="border-color:var(--ink-100); border-radius:12px; padding:10px 14px"
               onmouseenter="this.style.borderColor='var(--ink-300)'"
               onmouseleave="this.style.borderColor='var(--ink-100)'">
                <div class="stop-marker ${isMajor ? 'major' : ''}" style="flex-shrink:0"></div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold truncate" style="font-size:14px">${escapeHtml(s.name)}</div>
                    <div class="truncate" style="font-size:11.5px; color:var(--ink-400); margin-top:2px">
                        ${escapeHtml(s.route.name)}
                    </div>
                </div>
                <div class="font-extrabold text-white flex items-center justify-center flex-shrink-0"
                     style="width:28px; height:28px; border-radius:8px;
                            background:${s.route.color}; font-size:12px; letter-spacing:-0.5px;
                            box-shadow: inset 0 -2px 0 rgba(0,0,0,0.15)">
                    ${escapeHtml(s.route.code)}
                </div>
            </a>
        `;
    }).join('');
}

loadAllStops();
</script>
@endpush

@endsection
