@extends('layouts.passenger')

@section('title', $route->code . ' — ' . $route->name . ' | CrowdEase')

@section('content')

{{-- Style khusus halaman peta --}}
<style>
    /*
        Geser tombol zoom Leaflet (+/-) ke bawah floating top bar.
        Top bar tingginya: top:12 + 36px button = ~48px; kasih ruang ~60px supaya tidak nempel.
        Tanpa ini, tombol back dan zoom kelihatan kayak satu toolbar yang berdempetan.
    */
    #map .leaflet-top.leaflet-left { padding-top: 60px; }
</style>

{{-- Header --}}
<header class="bg-white border-b px-5 py-3.5 flex items-center justify-between" style="border-color: var(--ink-100)">
    <div class="flex items-center gap-2.5">
        <div class="logo">C</div>
        <div>
            <div class="font-extrabold text-[15px] leading-tight" style="letter-spacing:-0.2px">CrowdEase</div>
            <div class="text-[11.5px]" style="color: var(--ink-400); margin-top:-1px">Kepadatan TransJakarta</div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <span id="live-pill" class="pill pill-low">
            <span class="pill-dot pulse-anim" style="background: var(--c-low)"></span>
            Live
        </span>
        <span id="updated-text" class="text-[11px] hidden sm:inline" style="color: var(--ink-400)">—</span>
    </div>
</header>

{{-- Map area --}}
<div class="flex-1 relative" style="background:#EDEEF0">

    {{-- Floating top bar overlay --}}
    <div class="absolute z-50 flex items-center gap-2.5" style="top:12px; left:12px; right:12px">
        <a href="/" class="bg-white border flex items-center justify-center"
           style="width:36px; height:36px; border-radius:10px; border-color:var(--ink-100);
                  box-shadow:0 1px 3px rgba(11,15,20,0.06)"
           aria-label="Kembali">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <div class="bg-white border flex items-center gap-2.5 flex-1 min-w-0"
             style="border-color:var(--ink-100); border-radius:12px; padding:8px 12px;
                    box-shadow:0 1px 3px rgba(11,15,20,0.06)">
            <div class="font-extrabold text-white flex items-center justify-center flex-shrink-0"
                 style="width:28px; height:28px; border-radius:8px;
                        background:{{ $route->color }}; font-size:12px; letter-spacing:-0.5px;
                        box-shadow: inset 0 -2px 0 rgba(0,0,0,0.15)">
                {{ $route->code }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-bold truncate" style="font-size:13.5px; line-height:1.15">{{ $route->name }}</div>
                <div id="corridor-stats" style="font-size:11px; color:var(--ink-400)">— halte · — bus</div>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="absolute z-40 bg-white border flex flex-col gap-1.5 font-semibold"
         style="top:64px; right:12px; border-color:var(--ink-100); border-radius:10px;
                padding:8px 10px; box-shadow:0 1px 3px rgba(11,15,20,0.06); font-size:11px">
        <div class="flex items-center gap-1.5">
            <span style="width:10px; height:10px; border-radius:999px; background:var(--c-low); border:2px solid #fff; box-shadow: 0 0 0 1px var(--c-low)"></span>
            <span>Lengang</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span style="width:10px; height:10px; border-radius:999px; background:var(--c-med); border:2px solid #fff; box-shadow: 0 0 0 1px var(--c-med)"></span>
            <span>Sedang</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span style="width:10px; height:10px; border-radius:999px; background:var(--c-high); border:2px solid #fff; box-shadow: 0 0 0 1px var(--c-high)"></span>
            <span>Padat</span>
        </div>
    </div>

    {{-- Peta Leaflet --}}
    {{--
        PENTING: isolation:isolate memaksa elemen ini jadi stacking context terisolasi.
        Tanpa ini, z-index internal Leaflet (tile-pane 200, marker-pane 600, control 1000)
        "bocor" ke root stacking context dan menutupi bottom sheet (z-100), top bar (z-50),
        dan legend (z-40). Dengan isolation:isolate, semua z-index Leaflet jadi kompetisi
        internal — dari luar peta cuma satu elemen di z-auto.
    --}}
    <div id="map" class="absolute inset-0" style="isolation: isolate"></div>

    {{-- Bottom sheet: detail kendaraan --}}
    <div id="sheet" class="sheet">
        {{-- Handle --}}
        <div class="flex justify-center mb-2">
            <div style="width:36px; height:4px; background:var(--ink-200); border-radius:999px"></div>
        </div>

        {{-- Header --}}
        <div class="flex justify-between items-start mb-2.5">
            <div class="flex items-center gap-3">
                <div id="sheet-icon" class="flex items-center justify-center text-white"
                     style="width:44px; height:44px; border-radius:12px; background:var(--ink-300)">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="6" width="18" height="13" rx="2"/>
                        <path d="M3 14h18"/>
                        <circle cx="7" cy="17" r="1.5"/>
                        <circle cx="17" cy="17" r="1.5"/>
                    </svg>
                </div>
                <div>
                    <div id="sheet-label" class="font-extrabold" style="font-size:16px; letter-spacing:-0.3px">—</div>
                    <div id="sheet-plate" class="mono" style="font-size:12px; color:var(--ink-400)">—</div>
                </div>
            </div>
            <button onclick="closeSheet()" style="color:var(--ink-400)" aria-label="Tutup">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Occupancy bar --}}
        <div style="margin: 6px 0 14px">
            <div class="flex justify-between" style="margin-bottom:6px">
                <div class="font-semibold" style="font-size:12.5px; color:var(--ink-500)">Kepadatan</div>
                <div class="num font-bold" id="sheet-occ-text" style="font-size:12.5px">— / —</div>
            </div>
            <div class="relative overflow-hidden" style="height:10px; background:var(--ink-100); border-radius:999px">
                <div id="sheet-occ-bar" style="width:0%; height:100%; background:var(--ink-300); border-radius:999px; transition: width 0.3s"></div>
                {{-- Tick di 60% dan 85% --}}
                <div class="absolute" style="left:60%; top:-2px; bottom:-2px; width:1px; background:var(--ink-300)"></div>
                <div class="absolute" style="left:85%; top:-2px; bottom:-2px; width:1px; background:var(--ink-300)"></div>
            </div>
            <div class="flex justify-between" style="margin-top:6px">
                <span id="sheet-density-badge" class="pill pill-neutral pill-lg">—</span>
                <div class="flex items-center gap-1.5" style="font-size:11.5px; color:var(--ink-400)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span id="sheet-updated">—</span>
                </div>
            </div>
        </div>

        {{-- Forecast --}}
        <div>
            <div class="font-bold uppercase mb-3" style="font-size:12px; color:var(--ink-500); letter-spacing:0.6px">Prediksi 5–15 Menit</div>
            <div id="forecast-grid" class="grid grid-cols-3 gap-2">
                <div class="border text-center col-span-3" style="border-color:var(--ink-100); border-radius:12px; padding:12px 10px; background:var(--ink-25); font-size:12px; color:var(--ink-400)">
                    Memuat prediksi...
                </div>
            </div>
            <div class="text-center" style="font-size:10.5px; color:var(--ink-400); margin-top:8px">
                Model <span class="mono">moving_avg_v1</span> · diperbarui tiap data sensor baru masuk
            </div>
        </div>
    </div>
</div>


@include('passenger.partials.tab-bar', [])

@push('scripts')
<script>
const ROUTE_ID    = {{ $route->id }};
const ROUTE_CODE  = @json($route->code);
const ROUTE_COLOR = @json($route->color);
const POLL_MS     = 5000;

// === Inisialisasi peta ===
const map = L.map('map', { zoomControl: true, preferCanvas: true });
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
}).addTo(map);

let stops = [];
const vehicleMarkers = new Map();
let selectedVehicleId = null;
const forecastCache = new Map();

// === Helpers ===
function densityLevel(ratio) {
    if (ratio === null || ratio === undefined || isNaN(ratio)) return null;
    if (ratio < 0.6)  return 'low';
    if (ratio < 0.85) return 'med';
    return 'high';
}
function densityLabel(level) {
    return ({ low: 'Lengang', med: 'Sedang', high: 'Padat' })[level] || 'Tidak ada data';
}
function makeBusIcon(level, percentage, selected) {
    const lvl = level || 'unknown';
    const text = percentage !== null && percentage !== undefined ? Math.round(percentage) : '—';
    return L.divIcon({
        className: '',
        html: `<div class="bus-marker ${lvl} ${selected ? 'selected' : ''}">${text}</div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14]
    });
}
function makeStopIcon(isMajor) {
    return L.divIcon({
        className: '',
        html: `<div class="stop-marker ${isMajor ? 'major' : ''}"></div>`,
        iconSize:   isMajor ? [16, 16] : [12, 12],
        iconAnchor: isMajor ? [8, 8]   : [6, 6]
    });
}

// === Muat halte (sekali, di awal) ===
async function loadStops() {
    const res = await fetch(`/api/v1/routes/${ROUTE_ID}/stops`);
    const json = await res.json();
    if (!json.success) return;
    stops = json.data;

    // Polyline rute dengan halo putih di belakang
    if (stops.length >= 2) {
        const latlngs = stops.map(s => [s.latitude, s.longitude]);
        L.polyline(latlngs, { color: '#fff', weight: 9, opacity: 0.9 }).addTo(map);
        L.polyline(latlngs, { color: ROUTE_COLOR, weight: 5, opacity: 0.85, lineJoin: 'round' }).addTo(map);
    }

    // Halte: halte pertama & terakhir di-tandai "major" (visual berbeda)
    stops.forEach((s, i) => {
        const isMajor = (i === 0 || i === stops.length - 1);
        L.marker([s.latitude, s.longitude], { icon: makeStopIcon(isMajor) })
            .bindTooltip(s.name, { direction: 'top', offset: [0, -6] })
            .addTo(map);
    });

    // Fit map ke bounding box halte
    if (stops.length > 0) {
        const bounds = L.latLngBounds(stops.map(s => [s.latitude, s.longitude]));
        map.fitBounds(bounds.pad(0.15));
    }
}

/**
 * SISTEM ANIMASI POSISI ARMADA
 *
 * Pendekatan: tiap armada punya "progress" 0..1 sepanjang rute (polyline halte).
 *   - 0   = di halte pertama
 *   - 0.5 = di tengah rute
 *   - 1   = di halte terakhir
 *
 * Saat progress mencapai batas (0 atau 1), arah dibalik — armada bouncing/round-trip
 * mengikuti pola TransJakarta sungguhan yang bolak-balik.
 *
 * PENTING: ini animasi KOSMETIK menyusuri polyline, BUKAN tracking GPS sungguhan.
 * Sistem CrowdEase melacak kepadatan via sensor IoT (pintu/kamera). Posisi sebenarnya
 * dari armada perlu integrasi GPS terpisah — di luar scope sistem ini.
 *
 * Yang REAL di sini adalah:
 *   - Persentase di dalam marker (data sensor IoT live)
 *   - Warna marker berdasarkan level kepadatan
 *   - Prediksi 5/10/15 menit di bottom sheet
 *
 * Yang ILUSTRATIF:
 *   - Posisi marker (animasi mengikuti polyline)
 *   - ETA di halaman detail halte (dihitung dari sequence)
 */
const vehicleProgress = new Map(); // vehicle.id -> { progress, speed, direction }

function initProgress(vehicleId) {
    if (vehicleProgress.has(vehicleId)) return vehicleProgress.get(vehicleId);
    // PRNG sederhana berbasis ID supaya posisi awal deterministik tapi tersebar
    const r = ((vehicleId * 1664525 + 1013904223) >>> 0) / 4294967296;
    const r2 = ((vehicleId * 22695477 + 1) >>> 0) / 4294967296;
    const p = {
        progress: r,                                  // posisi awal 0..1 (tersebar)
        speed: 0.0010 + r2 * 0.0008,                  // kecepatan/tick (50ms); ~30-50 dtk full traversal
        direction: r > 0.5 ? 1 : -1                   // setengah ke depan, setengah ke belakang
    };
    vehicleProgress.set(vehicleId, p);
    return p;
}

/**
 * Konversi progress (0..1) ke koordinat lat/lng pada polyline halte.
 * Interpolasi linear antar 2 halte berdekatan.
 */
function getPolylinePosition(progress) {
    if (stops.length < 2) {
        // Edge case: kurang dari 2 halte, kembalikan halte pertama atau null
        return stops.length === 1 ? [stops[0].latitude, stops[0].longitude] : null;
    }
    const segments = stops.length - 1;
    const segPos = Math.max(0, Math.min(1, progress)) * segments;
    const segIdx = Math.min(Math.floor(segPos), segments - 1);
    const segT = segPos - segIdx;
    const a = stops[segIdx];
    const b = stops[segIdx + 1];
    return [
        a.latitude  + (b.latitude  - a.latitude)  * segT,
        a.longitude + (b.longitude - a.longitude) * segT
    ];
}

/**
 * Animation loop: dipanggil tiap 50ms (~20fps) untuk menggeser semua marker.
 * Polling (5 dtk) hanya mengupdate IKON (warna/persentase), tidak posisi.
 * Separation of concerns: polling = data, animation = motion.
 */
function animateMarkers() {
    vehicleMarkers.forEach((marker, id) => {
        const p = vehicleProgress.get(id);
        if (!p) return;
        p.progress += p.speed * p.direction;
        // Bounce di ujung rute
        if (p.progress >= 1) { p.progress = 1; p.direction = -1; }
        else if (p.progress <= 0) { p.progress = 0; p.direction = 1; }
        const pos = getPolylinePosition(p.progress);
        if (pos) marker.setLatLng(pos);
    });
}

// === Polling armada ===
async function pollVehicles() {
    flashLive();
    try {
        const res = await fetch(`/api/v1/routes/${ROUTE_ID}/vehicles`);
        const json = await res.json();
        if (!json.success) return;

        const vehicles = json.data;
        const seen = new Set();

        vehicles.forEach(v => {
            seen.add(v.id);
            const progress = initProgress(v.id);

            const ratio = v.latest_density ? parseFloat(v.latest_density.occupancy_ratio) : null;
            const level = densityLevel(ratio);
            const pct = ratio !== null ? ratio * 100 : null;
            const isSel = selectedVehicleId === v.id;

            let marker = vehicleMarkers.get(v.id);
            if (marker) {
                // Hanya update ikon (data); posisi di-handle animateMarkers
                marker.setIcon(makeBusIcon(level, pct, isSel));
                marker.setZIndexOffset(isSel ? 1000 : 0);
                marker.vehicleData = v;
            } else {
                // Marker baru: tempatkan di posisi awal sesuai progress
                const initialPos = getPolylinePosition(progress.progress);
                if (!initialPos) return;
                marker = L.marker(initialPos, {
                    icon: makeBusIcon(level, pct, isSel),
                    zIndexOffset: isSel ? 1000 : 0
                });
                marker.vehicleData = v;
                marker.on('click', () => openSheet(marker.vehicleData));
                marker.addTo(map);
                vehicleMarkers.set(v.id, marker);
            }
        });

        // Hapus marker armada yang sudah tidak muncul (juga buang progress-nya)
        for (const [id, m] of vehicleMarkers) {
            if (!seen.has(id)) {
                map.removeLayer(m);
                vehicleMarkers.delete(id);
                vehicleProgress.delete(id);
            }
        }

        // Update isi sheet bila terbuka untuk salah satu armada yang baru di-poll
        if (selectedVehicleId !== null) {
            const updated = vehicles.find(v => v.id === selectedVehicleId);
            if (updated) updateSheetContent(updated);
        }

        // Update stats di header overlay
        document.getElementById('corridor-stats').textContent =
            `${stops.length} halte · ${vehicles.length} bus`;

        // Timestamp
        if (json.meta && json.meta.server_time) {
            const dt = new Date(json.meta.server_time);
            document.getElementById('updated-text').textContent =
                'Diperbarui ' + dt.toLocaleTimeString('id-ID', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
        }
    } catch (e) {
        console.error('Polling gagal:', e);
    }
}

function flashLive() {
    const p = document.getElementById('live-pill');
    if (!p) return;
    p.style.transform = 'scale(1.05)';
    setTimeout(() => p.style.transform = '', 200);
}

// === Bottom sheet ===
function openSheet(vehicle) {
    selectedVehicleId = vehicle.id;
    refreshSelectedHighlight();
    updateSheetContent(vehicle);
    document.getElementById('sheet').classList.add('open');
    loadForecast(vehicle.id, vehicle.capacity);
}

function closeSheet() {
    selectedVehicleId = null;
    refreshSelectedHighlight();
    document.getElementById('sheet').classList.remove('open');
}

function refreshSelectedHighlight() {
    vehicleMarkers.forEach((m, id) => {
        const v = m.vehicleData;
        const ratio = v.latest_density ? parseFloat(v.latest_density.occupancy_ratio) : null;
        const lvl = densityLevel(ratio);
        const pct = ratio !== null ? ratio * 100 : null;
        const sel = selectedVehicleId === id;
        m.setIcon(makeBusIcon(lvl, pct, sel));
        m.setZIndexOffset(sel ? 1000 : 0);
    });
}

function updateSheetContent(v) {
    document.getElementById('sheet-label').textContent = v.plate_number;
    document.getElementById('sheet-plate').textContent = `Kapasitas ${v.capacity} · Koridor ${ROUTE_CODE}`;

    const d = v.latest_density;
    const occText  = document.getElementById('sheet-occ-text');
    const occBar   = document.getElementById('sheet-occ-bar');
    const badge    = document.getElementById('sheet-density-badge');
    const icon     = document.getElementById('sheet-icon');
    const updated  = document.getElementById('sheet-updated');

    if (d) {
        const ratio = parseFloat(d.occupancy_ratio);
        const pct = Math.round(ratio * 100);
        const lvl = densityLevel(ratio);

        occText.innerHTML =
            `${d.passenger_count} / ${d.capacity_at_time} ` +
            `<span style="color:var(--ink-400); font-weight:500">(${pct}%)</span>`;
        occBar.style.width = Math.min(100, pct) + '%';
        occBar.style.background = `var(--c-${lvl})`;

        badge.className = `pill pill-${lvl} pill-lg`;
        badge.innerHTML = `<span class="pill-dot pill-dot-lg" style="background:var(--c-${lvl})"></span>${densityLabel(lvl)}`;

        icon.style.background = `var(--c-${lvl})`;

        if (d.recorded_at) {
            const dt = new Date(d.recorded_at);
            updated.textContent = 'tercatat ' + dt.toLocaleTimeString('id-ID', {
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        }
    } else {
        occText.textContent = 'Belum ada data';
        occBar.style.width = '0%';
        occBar.style.background = 'var(--ink-300)';
        badge.className = 'pill pill-neutral pill-lg';
        badge.textContent = 'Tidak ada data';
        icon.style.background = 'var(--ink-300)';
        updated.textContent = '—';
    }
}

async function loadForecast(vehicleId, capacity) {
    const grid = document.getElementById('forecast-grid');

    // Cache 10 detik
    const cached = forecastCache.get(vehicleId);
    if (cached && (Date.now() - cached.at) < 10000) {
        renderForecast(cached.data, capacity);
        return;
    }

    grid.innerHTML = `
        <div class="col-span-3 border text-center" style="border-color:var(--ink-100); border-radius:12px; padding:12px; background:var(--ink-25); font-size:12px; color:var(--ink-400)">
            Memuat prediksi...
        </div>
    `;

    try {
        const res = await fetch(`/api/v1/vehicles/${vehicleId}/forecast`);
        const json = await res.json();

        if (!json.success || !json.data.forecasts || json.data.forecasts.length === 0) {
            grid.innerHTML = `
                <div class="col-span-3 border text-center" style="border-color:var(--ink-100); border-radius:12px; padding:12px; background:var(--ink-25); font-size:12px; color:var(--ink-400)">
                    Belum ada prediksi untuk armada ini.
                </div>
            `;
            return;
        }

        forecastCache.set(vehicleId, { data: json.data, at: Date.now() });
        renderForecast(json.data, capacity);
    } catch (e) {
        grid.innerHTML = `
            <div class="col-span-3 text-center" style="font-size:12px; color:var(--c-high); padding:10px">
                Gagal memuat prediksi.
            </div>
        `;
    }
}

function renderForecast(data, capacity) {
    const grid = document.getElementById('forecast-grid');
    const cap = capacity || 60;

    grid.innerHTML = data.forecasts.map(f => {
        const ratio = f.predicted_count / cap;
        const lvl = densityLevel(ratio);
        const pct = Math.round(ratio * 100);
        return `
            <div class="border text-center" style="border-color:var(--ink-100); border-radius:12px; padding:12px 10px; background:#fff">
                <div class="font-semibold" style="font-size:11px; color:var(--ink-400)">+${f.minutes_ahead} mnt</div>
                <div class="font-extrabold num" style="font-size:20px; margin-top:4px">~${f.predicted_count}</div>
                <div style="margin-top:6px">
                    <span class="pill pill-${lvl}">
                        <span class="pill-dot" style="background:var(--c-${lvl})"></span>
                        ${pct}%
                    </span>
                </div>
            </div>
        `;
    }).join('');
}

// === Boot ===
(async () => {
    await loadStops();
    await pollVehicles();
    setInterval(pollVehicles, POLL_MS);

    // Animation loop dimulai SETELAH halte dan armada awal di-load,
    // supaya getPolylinePosition() punya data halte untuk interpolasi.
    setInterval(animateMarkers, 50); // ~20fps; halus tanpa membebani CPU
})();
</script>
@endpush

@endsection