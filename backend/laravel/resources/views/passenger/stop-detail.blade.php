@extends('layouts.passenger')

@section('title', $stop->name . ' — CrowdEase')

@section('content')

{{-- Header --}}
<header class="bg-white border-b px-5 py-3.5 flex items-center justify-between" style="border-color: var(--ink-100)">
    <div class="flex items-center gap-2.5">
        <div class="logo">C</div>
        <div>
            <div class="font-extrabold text-[15px] leading-tight" style="letter-spacing:-0.2px">CrowdEase</div>
            <div class="text-[11.5px]" style="color: var(--ink-400); margin-top:-1px">Detail halte</div>
        </div>
    </div>
    <span id="live-pill" class="pill pill-low">
        <span class="pill-dot pulse-anim" style="background: var(--c-low)"></span>
        Live
    </span>
</header>

{{-- Main content --}}
<main class="flex-1 overflow-auto" style="padding: 14px 20px 20px">

    {{-- Back button --}}
    <a href="{{ route('passenger.stops') }}"
       class="inline-flex items-center gap-1.5 font-semibold mb-3"
       style="font-size:13px; color:var(--ink-500)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 6-6 6 6 6"/>
        </svg>
        Kembali ke daftar
    </a>

    {{-- Stop info card --}}
    <div class="bg-white border" style="border-color:var(--ink-100); border-radius:16px; padding:18px; margin-bottom:16px">
        <div class="flex justify-between items-start gap-3">
            <div class="min-w-0">
                <div class="font-semibold uppercase" style="font-size:12px; color:var(--ink-400); margin-bottom:4px; letter-spacing:0.5px">
                    Koridor {{ $stop->route->code }}
                </div>
                <div class="font-extrabold leading-tight" style="font-size:22px; letter-spacing:-0.5px">
                    {{ $stop->name }}
                </div>
                <div class="mono" style="font-size:12px; color:var(--ink-400); margin-top:6px">
                    {{ number_format($stop->latitude, 4) }}, {{ number_format($stop->longitude, 4) }}
                </div>
            </div>
            <div class="font-extrabold text-white flex items-center justify-center flex-shrink-0"
                 style="width:40px; height:40px; border-radius:10px;
                        background:{{ $stop->route->color }}; font-size:14px; letter-spacing:-0.5px;
                        box-shadow: inset 0 -2px 0 rgba(0,0,0,0.15)">
                {{ $stop->route->code }}
            </div>
        </div>
        <div class="flex gap-2" style="margin-top:14px">
            <a href="{{ route('passenger.map', $stop->route->code) }}"
               class="inline-flex items-center gap-2 font-semibold"
               style="background:#fff; color:var(--ink); border:1px solid var(--ink-200);
                      border-radius:8px; padding:9px 14px; font-size:13.5px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Z"/>
                    <path d="M9 4v14"/>
                    <path d="M15 6v14"/>
                </svg>
                Lihat di peta
            </a>
        </div>
    </div>

    {{-- Section: Akan Tiba --}}
    <div class="flex justify-between items-center mb-3 font-bold uppercase"
         style="font-size:12px; color:var(--ink-500); letter-spacing:0.6px">
        <span>Akan Tiba</span>
        <span id="bus-count" class="pill pill-neutral">—</span>
    </div>

    {{-- Loading state --}}
    <div id="loading" class="flex flex-col gap-2">
        @for ($i = 0; $i < 3; $i++)
            <div class="bg-white border animate-pulse" style="height:74px; border-color:var(--ink-100); border-radius:12px"></div>
        @endfor
    </div>

    {{-- Empty state --}}
    <div id="empty" class="hidden text-center"
         style="padding:24px; color:var(--ink-400); font-size:13px">
        Tidak ada armada aktif yang sedang menuju halte ini saat ini.
    </div>

    {{-- Error state --}}
    <div id="error" class="hidden p-4 text-center"
         style="background:var(--c-high-bg); border:1px solid var(--c-high-ring); border-radius:12px">
        <p class="font-semibold mb-2" style="color:var(--c-high)" id="error-msg">Gagal memuat armada</p>
        <button onclick="poll()" class="text-sm underline" style="color:var(--c-high)">Coba lagi</button>
    </div>

    {{-- Bus list --}}
    <div id="bus-list" class="hidden flex-col gap-2"></div>

    {{-- Footer note --}}
    <div class="text-center" style="font-size:10.5px; color:var(--ink-400); margin-top:14px">
        ETA bersifat ilustratif berdasarkan urutan halte; sistem nyata akan menggunakan GPS real-time
    </div>
</main>

{{-- Tab bar --}}
@include('passenger.partials.tab-bar', ['active' => 'stops'])

@push('scripts')
<script>
const ROUTE_ID    = {{ $stop->route->id }};
const ROUTE_CODE  = @json($stop->route->code);
const STOP_SEQ    = {{ $stop->sequence }};
const POLL_MS     = 5000;

let stopsCache = []; // semua halte di route ini

function densityLevel(ratio) {
    if (ratio === null || ratio === undefined || isNaN(ratio)) return null;
    if (ratio < 0.6)  return 'low';
    if (ratio < 0.85) return 'med';
    return 'high';
}
function densityLabel(level) {
    return ({ low: 'Lengang', med: 'Sedang', high: 'Padat' })[level] || 'Tidak ada data';
}

function setState(active) {
    ['loading', 'error', 'empty', 'bus-list'].forEach(id => {
        const el = document.getElementById(id);
        if (id === active) {
            el.classList.remove('hidden');
            if (id === 'bus-list') el.classList.add('flex');
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

async function loadStops() {
    try {
        const res = await fetch(`/api/v1/routes/${ROUTE_ID}/stops`);
        const json = await res.json();
        if (json.success) stopsCache = json.data;
    } catch (e) {
        console.warn('Gagal load halte koridor:', e);
    }
}

async function poll() {
    flashLive();
    try {
        const res = await fetch(`/api/v1/routes/${ROUTE_ID}/vehicles`);
        const json = await res.json();
        if (!json.success) throw new Error(json.error?.message || 'API gagal');

        const vehicles = json.data;
        renderApproaching(vehicles);
    } catch (e) {
        document.getElementById('error-msg').textContent = e.message;
        setState('error');
    }
}

function flashLive() {
    const p = document.getElementById('live-pill');
    if (!p) return;
    p.style.transform = 'scale(1.05)';
    setTimeout(() => p.style.transform = '', 200);
}

function renderApproaching(vehicles) {
    if (vehicles.length === 0 || stopsCache.length === 0) {
        document.getElementById('bus-count').textContent = '0';
        setState('empty');
        return;
    }

    const N = stopsCache.length;

    // Untuk tiap armada, hitung posisi (idx halte) berdasarkan pemetaan id -> halte
    // (konsisten dengan map.blade.php).
    // ETA = jumlah halte ke depan sampai target × 2 menit (ilustratif).
    const buses = vehicles.map(v => {
        const busIdx = (v.id - 1) % N;
        // Selisih maju (modular): berapa halte armada perlu lalui untuk sampai sini
        const stopsToGo = ((STOP_SEQ - 1) - busIdx + N) % N;
        const etaMin = Math.max(1, stopsToGo * 2);
        return { ...v, busIdx, stopsToGo, etaMin };
    });

    // Urutkan: armada paling dekat dulu (stopsToGo terkecil); ambil 4 teratas
    buses.sort((a, b) => a.stopsToGo - b.stopsToGo);
    const top = buses.slice(0, 4);

    document.getElementById('bus-count').textContent = top.length;
    setState('bus-list');

    const list = document.getElementById('bus-list');
    list.innerHTML = top.map(b => {
        const d = b.latest_density;
        const ratio = d ? parseFloat(d.occupancy_ratio) : null;
        const level = densityLevel(ratio);
        const pct   = ratio !== null ? Math.round(ratio * 100) : null;
        const passengerCount = d ? d.passenger_count : '?';
        const capacityAtTime = d ? d.capacity_at_time : b.capacity;
        const iconBg = level ? `var(--c-${level})` : 'var(--ink-300)';

        const densityPill = level ? `
            <span class="pill pill-${level}">
                <span class="pill-dot" style="background:var(--c-${level})"></span>
                ${densityLabel(level)}
            </span>
        ` : `<span class="pill pill-neutral">—</span>`;

        return `
            <div class="bg-white border flex items-center gap-3"
                 style="border-color:var(--ink-100); border-radius:12px; padding:14px">
                <div class="flex items-center justify-center text-white flex-shrink-0"
                     style="width:38px; height:38px; border-radius:10px; background:${iconBg}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2v1a1 1 0 0 1-2 0v-1H8v1a1 1 0 0 1-2 0v-1a2 2 0 0 1-2-2Z"/>
                        <path d="M4 10h16"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold truncate" style="font-size:14px">${escapeHtml(b.plate_number)}</div>
                    <div class="truncate" style="font-size:11.5px; color:var(--ink-400); margin-top:2px">
                        ${pct !== null ? `${passengerCount}/${capacityAtTime} · ${pct}%` : 'Belum ada data sensor'}
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="font-extrabold num" style="font-size:17px; letter-spacing:-0.3px">
                        ${b.etaMin} <span style="font-size:11.5px; color:var(--ink-400); font-weight:600">mnt</span>
                    </div>
                    <div style="margin-top:2px">${densityPill}</div>
                </div>
            </div>
        `;
    }).join('');
}

// === Boot ===
(async () => {
    await loadStops();
    await poll();
    setInterval(poll, POLL_MS);
})();
</script>
@endpush

@endsection
