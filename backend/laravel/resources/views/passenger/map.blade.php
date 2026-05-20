@extends('layouts.passenger')

@section('title', $route->code . ' — ' . $route->name . ' | CrowdEase')

@section('content')

{{-- Top bar dengan info koridor + indikator live --}}
<header class="bg-white border-b border-slate-200 sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
        <a href="/" class="text-slate-500 hover:text-slate-800 text-sm flex items-center gap-1">
            <span class="text-lg">←</span>
            <span class="hidden sm:inline">Kembali</span>
        </a>

        <div class="h-8 w-1.5 rounded-full" style="background-color: {{ $route->color }}"></div>

        <div class="flex-1 min-w-0">
            <div class="font-bold text-lg text-slate-800">{{ $route->code }}</div>
            <div class="text-xs text-slate-500 truncate">{{ $route->name }}</div>
        </div>

        <div class="flex items-center gap-3 text-xs">
            <span class="flex items-center gap-1.5 text-slate-600">
                <span id="live-dot" class="w-2 h-2 rounded-full bg-green-500"></span>
                <span class="hidden sm:inline">Live</span>
            </span>
            <span id="last-updated" class="text-slate-400 hidden sm:inline">—</span>
        </div>
    </div>
</header>

{{-- Legenda warna --}}
<div class="bg-white border-b border-slate-200 px-4 py-2.5">
    <div class="max-w-7xl mx-auto flex items-center gap-4 text-xs text-slate-600 flex-wrap">
        <span class="font-medium text-slate-700">Kepadatan:</span>
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full" style="background-color:#22c55e"></span>Lengang
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full" style="background-color:#eab308"></span>Sedang
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full" style="background-color:#ef4444"></span>Padat
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full" style="background-color:#7f1d1d"></span>Overcrowded
        </span>
        <span class="text-slate-400 hidden md:inline ml-auto">
            Titik abu-abu = halte &nbsp;·&nbsp; Klik marker bus untuk detail
        </span>
    </div>
</div>

{{-- Peta Leaflet --}}
<div id="map" style="height: calc(100vh - 200px); min-height: 400px;"></div>

@push('scripts')
<script>
    // --- Konfigurasi ---
    const ROUTE_ID = {{ $route->id }};
    const POLL_INTERVAL_MS = 5000; // selaras dengan pola polling di API Contract

    // --- Inisialisasi peta ---
    const map = L.map('map').setView([-6.18, 106.83], 12);

    // Tile OpenStreetMap = titik integrasi TI-5
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
    }).addTo(map);

    let stops = [];
    const vehicleMarkers = new Map(); // vehicleId -> L.circleMarker

    // --- Helper warna & label ---
    function colorForLevel(level) {
        return ({
            'low':         '#22c55e',
            'medium':      '#eab308',
            'high':        '#ef4444',
            'overcrowded': '#7f1d1d',
        })[level] || '#94a3b8';
    }

    function labelForLevel(level) {
        return ({
            'low':         'Lengang',
            'medium':      'Sedang',
            'high':        'Padat',
            'overcrowded': 'Overcrowded',
        })[level] || 'Tidak ada data';
    }

    // --- Memuat halte (sekali, saat halaman dibuka) ---
    async function loadStops() {
        const res = await fetch(`/api/v1/routes/${ROUTE_ID}/stops`);
        const json = await res.json();
        if (!json.success) {
            console.error('Gagal memuat halte:', json);
            return;
        }
        stops = json.data;

        // Render setiap halte sebagai titik kecil abu-abu
        stops.forEach(stop => {
            L.circleMarker([stop.latitude, stop.longitude], {
                radius: 5,
                fillColor: '#64748b',
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9,
            })
            .bindTooltip(stop.name, { direction: 'top', offset: [0, -8] })
            .addTo(map);
        });

        // Sesuaikan zoom & posisi peta ke bounding box halte koridor ini
        if (stops.length > 0) {
            const bounds = L.latLngBounds(stops.map(s => [s.latitude, s.longitude]));
            map.fitBounds(bounds, { padding: [60, 60] });
        }
    }

    /**
     * Posisi marker armada di peta.
     *
     * Sistem ini melacak KEPADATAN, bukan GPS posisi armada. Sebagai
     * visualisasi yang masuk akal, tiap armada ditempatkan di halte
     * koridor dengan pemetaan stabil (vehicle.id -> halte ke-n).
     *
     * Saat presentasi, sebutkan: "Posisi marker bersifat ilustratif
     * — sistem nyata akan terintegrasi dengan modul GPS perangkat.
     * Yang real-time di sini adalah data kepadatannya (warna marker)."
     */
    function positionForVehicle(vehicle) {
        if (stops.length === 0) return null;
        const idx = (vehicle.id - 1) % stops.length;
        return [stops[idx].latitude, stops[idx].longitude];
    }

    // --- HTML untuk popup marker armada ---
    function popupHTML(vehicle) {
        const d = vehicle.latest_density;

        if (!d) {
            return `
                <div class="text-sm">
                    <div class="font-semibold text-slate-800">${escapeHtml(vehicle.plate_number)}</div>
                    <div class="text-slate-500 mt-1">Belum ada data kepadatan.</div>
                </div>
            `;
        }

        const pct = Math.round(d.occupancy_ratio * 100);
        const color = colorForLevel(d.occupancy_level);

        return `
            <div class="text-sm">
                <div class="font-semibold text-slate-800">${escapeHtml(vehicle.plate_number)}</div>
                <div class="flex items-center gap-3 mt-2">
                    <div class="text-3xl font-bold leading-none" style="color:${color}">${pct}%</div>
                    <div class="text-xs text-slate-600 leading-tight">
                        ${d.passenger_count} / ${d.capacity_at_time} penumpang<br>
                        <span class="font-medium" style="color:${color}">${labelForLevel(d.occupancy_level)}</span>
                    </div>
                </div>
                <button onclick="loadForecast(${vehicle.id})"
                        id="forecast-btn-${vehicle.id}"
                        class="mt-3 text-xs text-blue-600 hover:underline">
                    Lihat prediksi 5–15 menit →
                </button>
                <div id="forecast-${vehicle.id}" class="mt-2"></div>
            </div>
        `;
    }

    /**
     * Lazy-load forecast saat tombol di popup di-klik.
     * Memanggil endpoint /vehicles/{id}/forecast.
     */
    async function loadForecast(vehicleId) {
        const btn = document.getElementById(`forecast-btn-${vehicleId}`);
        const target = document.getElementById(`forecast-${vehicleId}`);
        if (!target) return;

        if (btn) btn.style.display = 'none';
        target.innerHTML = '<span class="text-xs text-slate-500">Memuat prediksi...</span>';

        try {
            const res = await fetch(`/api/v1/vehicles/${vehicleId}/forecast`);
            const json = await res.json();

            if (!json.success || !json.data.forecasts || json.data.forecasts.length === 0) {
                target.innerHTML = '<span class="text-xs text-slate-500">Belum ada prediksi untuk armada ini.</span>';
                return;
            }

            target.innerHTML = `
                <div class="bg-slate-50 rounded-md p-2 text-xs mt-1 space-y-1">
                    ${json.data.forecasts.map(f => `
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">+${f.minutes_ahead} menit</span>
                            <span class="font-medium text-slate-800">~${f.predicted_count} penumpang</span>
                        </div>
                    `).join('')}
                    <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-200">
                        Model: ${escapeHtml(json.data.model_version || '—')}
                    </div>
                </div>
            `;
        } catch (e) {
            console.error('Forecast fetch failed:', e);
            target.innerHTML = '<span class="text-xs text-red-500">Gagal memuat prediksi.</span>';
        }
    }

    // --- Polling armada setiap 5 detik (endpoint utama TI-3) ---
    async function pollVehicles() {
        flashLive();

        try {
            const res = await fetch(`/api/v1/routes/${ROUTE_ID}/vehicles`);
            const json = await res.json();
            if (!json.success) return;

            const seenIds = new Set();

            json.data.forEach(vehicle => {
                seenIds.add(vehicle.id);

                const pos = positionForVehicle(vehicle);
                if (!pos) return;

                const color = colorForLevel(vehicle.latest_density?.occupancy_level);
                let marker = vehicleMarkers.get(vehicle.id);

                if (marker) {
                    marker.setLatLng(pos);
                    marker.setStyle({ fillColor: color });
                    // Jangan refresh isi popup kalau sedang dibuka — biar
                    // tombol "Lihat prediksi" yang sudah di-klik tidak hilang.
                    if (!marker.isPopupOpen()) {
                        marker.setPopupContent(popupHTML(vehicle));
                    }
                } else {
                    marker = L.circleMarker(pos, {
                        radius: 11,
                        fillColor: color,
                        color: '#fff',
                        weight: 3,
                        opacity: 1,
                        fillOpacity: 0.95,
                    })
                    .bindPopup(popupHTML(vehicle), { maxWidth: 300 })
                    .addTo(map);
                    vehicleMarkers.set(vehicle.id, marker);
                }
            });

            // Bersihkan marker armada yang sudah tidak muncul lagi
            for (const [id, marker] of vehicleMarkers) {
                if (!seenIds.has(id)) {
                    map.removeLayer(marker);
                    vehicleMarkers.delete(id);
                }
            }

            // Update label "diperbarui kapan"
            if (json.meta?.server_time) {
                const dt = new Date(json.meta.server_time);
                document.getElementById('last-updated').textContent =
                    'Diperbarui ' + dt.toLocaleTimeString('id-ID', {
                        hour: '2-digit', minute: '2-digit', second: '2-digit'
                    });
            }
        } catch (e) {
            console.error('Polling failed:', e);
        }
    }

    /** Beri "kedip" sebentar pada dot live untuk indikasi polling. */
    function flashLive() {
        const dot = document.getElementById('live-dot');
        if (!dot) return;
        dot.classList.remove('bg-green-500');
        dot.classList.add('bg-green-300');
        setTimeout(() => {
            dot.classList.remove('bg-green-300');
            dot.classList.add('bg-green-500');
        }, 200);
    }

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(s);
        return div.innerHTML;
    }

    // --- Boot ---
    (async () => {
        await loadStops();
        await pollVehicles();
        setInterval(pollVehicles, POLL_INTERVAL_MS);
    })();
</script>
@endpush

@endsection
