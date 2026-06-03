@extends('layouts.passenger')

@section('title', 'CrowdEase — Kepadatan TransJakarta Real-time')

@section('content')

{{-- Header --}}
<header class="bg-white border-b px-5 py-3.5 flex items-center justify-between" style="border-color: var(--ink-100)">
    <div class="flex items-center gap-2.5">
        <div class="logo">C</div>
        <div>
            <div class="font-extrabold text-[15px] leading-tight" style="letter-spacing:-0.2px">CrowdEase</div>
            <div class="text-[11.5px]" style="color: var(--ink-400); margin-top:-1px">Kepadatan TransJakarta</div>
        </div>
    </div>
    <span id="header-pill" class="pill pill-low">
        <span class="pill-dot pulse-anim" style="background: var(--c-low)"></span>
        <span id="header-fleet-count">—</span>
    </span>
</header>

{{-- Main content (scroll area) --}}
<main class="flex-1 overflow-auto" style="padding: 20px 20px 80px">

    {{-- Hero card --}}
    <div class="relative overflow-hidden text-white" style="
        background: linear-gradient(135deg, #111 0%, #2a1416 50%, var(--brand-700) 100%);
        border-radius: 20px;
        padding: 22px 22px 24px;
        margin-bottom: 18px;
    ">
        {{-- Cincin dekoratif (terpotong oleh overflow) --}}
        <div class="absolute rounded-full" style="top:-60px; right:-40px; width:200px; height:200px; border:1px solid rgba(255,255,255,0.12)"></div>
        <div class="absolute rounded-full" style="top:-90px; right:-90px; width:280px; height:280px; border:1px solid rgba(255,255,255,0.07)"></div>

        <div class="font-semibold uppercase" style="font-size:12px; opacity:0.7; letter-spacing:1px">
            TransJakarta · Live
        </div>
        <div class="font-extrabold mt-2 leading-tight" style="font-size:22px; letter-spacing:-0.4px; max-width:280px">
            Pilih koridor untuk pantau kepadatan
        </div>

        <div class="flex gap-3.5 items-center" style="margin-top:18px">
            <div style="min-width:0">
                <div class="font-extrabold num" id="stat-fleet" style="font-size:20px; letter-spacing:-0.5px">—</div>
                <div class="font-medium" style="font-size:10.5px; opacity:0.7; margin-top:2px">Armada Aktif</div>
            </div>
            <div class="self-stretch" style="width:1px; background:rgba(255,255,255,0.15)"></div>
            <div style="min-width:0">
                <div class="font-extrabold num" id="stat-stops" style="font-size:20px; letter-spacing:-0.5px">—</div>
                <div class="font-medium" style="font-size:10.5px; opacity:0.7; margin-top:2px">Halte</div>
            </div>
            <div class="self-stretch" style="width:1px; background:rgba(255,255,255,0.15)"></div>
            <div style="min-width:0">
                <div class="font-extrabold num" id="stat-avg" style="font-size:20px; letter-spacing:-0.5px">—</div>
                <div class="font-medium" style="font-size:10.5px; opacity:0.7; margin-top:2px">Rata-rata Isi</div>
            </div>
        </div>
    </div>

    {{-- Section title --}}
    <div class="flex justify-between items-center mb-3 font-bold uppercase" style="font-size:12px; color:var(--ink-500); letter-spacing:0.6px">
        <span>Koridor Aktif</span>
        <span id="route-count" class="pill pill-neutral">—</span>
    </div>

    {{-- Loading skeleton --}}
    <div id="loading" class="flex flex-col gap-3">
        @for ($i = 0; $i < 5; $i++)
            <div class="h-[78px] bg-white border animate-pulse" style="border-color:var(--ink-100); border-radius:16px"></div>
        @endfor
    </div>

    {{-- Error state --}}
    <div id="error" class="hidden p-5 text-center" style="background:var(--c-high-bg); border:1px solid var(--c-high-ring); border-radius:16px">
        <p class="font-semibold mb-2" style="color:var(--c-high)">Gagal memuat data koridor</p>
        <p class="text-sm mb-3" id="error-msg" style="color:var(--c-high)"></p>
        <button onclick="loadAll()" class="text-sm underline" style="color:var(--c-high)">Coba lagi</button>
    </div>

    {{-- Corridor cards --}}
    <div id="routes-list" class="hidden flex-col gap-3"></div>
</main>


@include('passenger.partials.tab-bar', ['active' => 'home'])

@push('scripts')
<script>
let allRoutes = [];
let vehiclesByRoute = {};

function densityLevel(ratio) {
    if (ratio === null || ratio === undefined || isNaN(ratio)) return null;
    if (ratio < 0.6) return 'low';
    if (ratio < 0.85) return 'med';
    return 'high';
}

function densityLabel(level) {
    return ({ low: 'Lengang', med: 'Sedang', high: 'Padat' })[level] || '—';
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}

function toggleState(active) {
    ['loading', 'error', 'routes-list'].forEach(id => {
        const el = document.getElementById(id);
        if (id === active) {
            el.classList.remove('hidden');
            // Penting: hanya tambahkan 'flex' untuk routes-list.
            // classList.add('') akan throw DOMException — itulah bug sebelumnya
            // yang bikin skeleton loading nyangkut.
            if (id === 'routes-list') el.classList.add('flex');
        } else {
            el.classList.add('hidden');
            el.classList.remove('flex');
        }
    });
}

async function loadAll() {
    toggleState('loading');
    try {
        // 1. Ambil daftar koridor (dengan stops_count dan vehicles_count)
        const res = await fetch('/api/v1/routes');
        const json = await res.json();
        if (!json.success) throw new Error(json.error?.message || 'API gagal');

        allRoutes = json.data;

        // 2. Render kartu (tanpa density dulu, biar cepat tampil)
        renderRoutes();
        toggleState('routes-list');

        // 3. Fetch armada tiap koridor PARALEL untuk hitung density
        await Promise.all(allRoutes.map(r =>
            fetch(`/api/v1/routes/${r.id}/vehicles`)
                .then(r => r.json())
                .then(j => { if (j.success) vehiclesByRoute[r.id] = j.data; })
                .catch(e => console.warn('Gagal fetch armada koridor', r.id, e))
        ));

        // 4. Hitung stats & render ulang dengan density
        computeStats();
        renderRoutes();
    } catch (e) {
        document.getElementById('error-msg').textContent = e.message;
        toggleState('error');
    }
}

function computeStats() {
    const totalFleet  = allRoutes.reduce((s, r) => s + (r.vehicles_count || 0), 0);
    const totalStops  = allRoutes.reduce((s, r) => s + (r.stops_count    || 0), 0);

    let occSum = 0, occCount = 0;
    Object.values(vehiclesByRoute).forEach(vehicles => {
        vehicles.forEach(v => {
            if (v.latest_density) {
                occSum += parseFloat(v.latest_density.occupancy_ratio);
                occCount++;
            }
        });
    });
    const avgPct = occCount > 0 ? Math.round((occSum / occCount) * 100) : 0;

    document.getElementById('stat-fleet').textContent = totalFleet;
    document.getElementById('stat-stops').textContent = totalStops;
    document.getElementById('stat-avg').textContent   = avgPct + '%';
    document.getElementById('header-fleet-count').textContent = totalFleet + ' bus';
    document.getElementById('route-count').textContent = allRoutes.length;
}

function renderRoutes() {
    const list = document.getElementById('routes-list');
    if (allRoutes.length === 0) {
        list.innerHTML = `<div class="text-center text-sm py-6" style="color:var(--ink-400)">Belum ada koridor terdaftar.</div>`;
        return;
    }

    list.innerHTML = allRoutes.map(r => {
        const vehicles = vehiclesByRoute[r.id] || [];
        const occRatios = vehicles
            .filter(v => v.latest_density)
            .map(v => parseFloat(v.latest_density.occupancy_ratio));

        const avg = occRatios.length > 0 ? occRatios.reduce((a, b) => a + b, 0) / occRatios.length : null;
        const level = avg !== null ? densityLevel(avg) : null;
        const label = level ? densityLabel(level) : '—';
        const pct = avg !== null ? Math.round(avg * 100) + '%' : '—';

        const badgeHtml = level ? `
            <span class="pill pill-${level}">
                <span class="pill-dot" style="background:var(--c-${level})"></span>
                ${label}
            </span>
        ` : `<span class="pill pill-neutral">Memuat...</span>`;

        return `
            <a href="/koridor/${encodeURIComponent(r.code)}"
               class="bg-white border flex items-center gap-3 hover:shadow-sm transition"
               style="border-color:var(--ink-100); border-radius:16px; padding:16px"
               onmouseenter="this.style.borderColor='var(--ink-300)'"
               onmouseleave="this.style.borderColor='var(--ink-100)'">
                <div class="font-extrabold text-white flex items-center justify-center flex-shrink-0"
                     style="width:46px; height:46px; border-radius:10px;
                            background:${r.color}; font-size:16px; letter-spacing:-0.5px;
                            box-shadow: inset 0 -2px 0 rgba(0,0,0,0.15)">
                    ${escapeHtml(r.code)}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold truncate" style="font-size:15px; letter-spacing:-0.2px">${escapeHtml(r.name)}</div>
                    <div class="flex items-center gap-2 mt-1 truncate" style="font-size:11.5px; color:var(--ink-400)">
                        <span>${r.stops_count ?? '?'} halte</span>
                        <span style="width:3px; height:3px; border-radius:999px; background:var(--ink-200); flex-shrink:0"></span>
                        <span>${r.vehicles_count ?? '?'} bus</span>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    ${badgeHtml}
                    <div class="num" style="font-size:11px; color:var(--ink-400); margin-top:4px">${pct}</div>
                </div>
                <span class="flex-shrink-0" style="color:var(--ink-300); font-size:18px">›</span>
            </a>
        `;
    }).join('');
}

loadAll();
</script>
@endpush

@endsection
