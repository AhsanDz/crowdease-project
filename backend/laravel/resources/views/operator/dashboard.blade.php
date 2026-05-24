@extends('operator.layouts.app')

@section('title', 'Dashboard · Operator')
@section('page-title', 'Dasbor')
@section('page-subtitle', 'Ringkasan operasi armada · diperbarui realtime')

@php $activePage = 'operator.dashboard'; @endphp

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- Loading state --}}
<div id="loading" class="flex items-center justify-center" style="padding: 80px 20px">
    <div class="text-center" style="color: var(--ink-400); font-size: 13.5px">
        <div class="inline-block animate-spin rounded-full border-2 border-current border-r-transparent"
             style="width: 24px; height: 24px; margin-bottom: 12px"></div>
        <div>Memuat data dashboard...</div>
    </div>
</div>

{{-- Error state --}}
<div id="error" class="hidden p-5 text-center"
     style="background: var(--c-high-bg); border: 1px solid var(--c-high-ring);
            border-radius: 12px">
    <p class="font-semibold mb-2" style="color: var(--c-high)" id="error-msg">Gagal memuat data</p>
    <button onclick="loadDashboard()" class="text-sm underline" style="color: var(--c-high)">Coba lagi</button>
</div>

{{-- Content (initially hidden) --}}
<div id="content" class="hidden flex flex-col" style="gap: 18px">

    {{-- ──────── KPI row (4 kartu) ──────── --}}
    <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap: 14px">
        @foreach ([
            ['key' => 'fleet',    'label' => 'Armada Aktif',  'icon' => 'bus'],
            ['key' => 'occ',      'label' => 'Rata-rata Okupansi', 'icon' => 'signal'],
            ['key' => 'logs',     'label' => 'Log Hari Ini',  'icon' => 'refresh'],
            ['key' => 'webhooks', 'label' => 'Webhook Aktif', 'icon' => 'webhook'],
        ] as $kpi)
            <div class="bg-white border"
                 style="border-color: var(--ink-100); border-radius: 14px; padding: 16px 18px">
                <div class="flex items-center justify-between mb-2.5">
                    <div class="flex items-center justify-center"
                         style="width: 32px; height: 32px; border-radius: 8px;
                                background: var(--brand-50); color: var(--brand)">
                        @include('operator.partials.icon', ['name' => $kpi['icon'], 'size' => 16])
                    </div>
                </div>
                <div class="num font-extrabold" id="kpi-{{ $kpi['key'] }}"
                     style="font-size: 26px; letter-spacing: -0.6px">—</div>
                <div class="font-semibold" style="font-size: 12px; color: var(--ink-400); margin-top: 2px">
                    {{ $kpi['label'] }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- ──────── Chart row: tren per jam + donut distribusi ──────── --}}
    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 14px; min-width: 0">

        {{-- Hourly trend chart --}}
        <div class="bg-white border" style="border-color: var(--ink-100); border-radius: 14px; min-width: 0">
            <div class="flex justify-between items-baseline" style="padding: 16px 18px 6px">
                <div>
                    <div class="font-bold" style="font-size: 14.5px">Tren Kepadatan</div>
                    <div style="font-size: 11.5px; color: var(--ink-400); margin-top: 2px">
                        24 jam terakhir · per jam
                    </div>
                </div>
            </div>
            <div style="padding: 0 18px 16px; height: 260px">
                <canvas id="hourly-chart"></canvas>
            </div>
        </div>

        {{-- Donut distribusi level --}}
        <div class="bg-white border flex flex-col"
             style="border-color: var(--ink-100); border-radius: 14px">
            <div style="padding: 16px 18px 6px">
                <div class="font-bold" style="font-size: 14.5px">Distribusi Okupansi</div>
                <div style="font-size: 11.5px; color: var(--ink-400); margin-top: 2px">
                    <span id="vehicle-count">—</span> armada aktif
                </div>
            </div>
            <div class="relative" style="padding: 16px 18px 8px; height: 160px">
                <canvas id="donut-chart"></canvas>
                {{-- Center label overlay --}}
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <div class="num font-extrabold" id="donut-center-pct"
                         style="font-size: 22px; letter-spacing: -0.5px">—</div>
                    <div style="font-size: 10.5px; color: var(--ink-400); font-weight: 600;
                                letter-spacing: 0.4px; text-transform: uppercase">avg</div>
                </div>
            </div>

            {{-- Legend list --}}
            <div class="flex flex-col" id="donut-legend"
                 style="padding: 0 18px 16px; gap: 8px"></div>
        </div>
    </div>

    {{-- ──────── Per-corridor occupancy ──────── --}}
    <div class="bg-white border" style="border-color: var(--ink-100); border-radius: 14px">
        <div style="padding: 16px 18px 10px; border-bottom: 1px solid var(--ink-100)">
            <div class="font-bold" style="font-size: 14.5px">Okupansi per Koridor</div>
            <div style="font-size: 11.5px; color: var(--ink-400); margin-top: 2px">
                Rerata realtime di seluruh armada koridor
            </div>
        </div>
        <div id="per-corridor-list"></div>
    </div>

    {{-- Footer meta --}}
    <div class="text-center" style="font-size: 10.5px; color: var(--ink-400); padding: 8px 0 16px">
        Data per <span id="updated-at">—</span> · auto-refresh setiap 30 detik
    </div>
</div>

@endsection

@push('scripts')
<script>
const REFRESH_INTERVAL_MS = 30000;
let hourlyChart, donutChart;

function densityLevel(ratio) {
    if (ratio < 0.6)  return 'low';
    if (ratio < 0.85) return 'med';
    return 'high';
}

function levelMeta(level) {
    return ({
        low:     { label: 'Lengang', color: 'var(--c-low)',  hex: '#16A34A' },
        med:     { label: 'Sedang',  color: 'var(--c-med)',  hex: '#D97706' },
        high:    { label: 'Padat',   color: 'var(--c-high)', hex: '#DC2626' },
        no_data: { label: 'No data', color: 'var(--ink-300)', hex: '#9AA1AC' },
    })[level];
}

function setState(active) {
    ['loading', 'error', 'content'].forEach(id => {
        const el = document.getElementById(id);
        if (id === active) {
            el.classList.remove('hidden');
            if (id === 'content') el.classList.add('flex');
        } else {
            el.classList.add('hidden');
            el.classList.remove('flex');
        }
    });
}

async function loadDashboard() {
    // Pertahankan content kalau sudah ada (silent refresh)
    if (!document.getElementById('content').classList.contains('hidden')) {
        // Already loaded — silent refresh, no loading state
    } else {
        setState('loading');
    }

    try {
        const res = await fetchAuth('/api/v1/admin/dashboard/stats');
        const json = await res.json();
        if (!json.success) throw new Error(json.error?.message || 'API gagal');

        renderDashboard(json.data, json.meta);
        setState('content');
    } catch (e) {
        document.getElementById('error-msg').textContent = e.message;
        if (document.getElementById('content').classList.contains('hidden')) {
            setState('error');
        } else {
            console.warn('Refresh gagal, mempertahankan data sebelumnya:', e.message);
        }
    }
}

function renderDashboard(data, meta) {
    // ── KPIs ────────────────────────────────────────────────────
    document.getElementById('kpi-fleet').textContent     = data.totals.active_vehicles;
    document.getElementById('kpi-occ').textContent       = data.occupancy.avg_percent + '%';
    document.getElementById('kpi-logs').textContent      = data.totals.today_logs.toLocaleString('id-ID');
    document.getElementById('kpi-webhooks').textContent  = data.totals.active_webhooks;

    document.getElementById('vehicle-count').textContent = data.totals.active_vehicles;
    document.getElementById('donut-center-pct').textContent = data.occupancy.avg_percent + '%';

    // ── Hourly chart ────────────────────────────────────────────
    const trend = data.hourly_trend;
    const labels = trend.map(t => String(t.hour).padStart(2, '0'));
    const values = trend.map(t => Math.round(t.avg_occupancy * 100));

    if (hourlyChart) hourlyChart.destroy();
    hourlyChart = new Chart(document.getElementById('hourly-chart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Rata-rata Okupansi (%)',
                data: values,
                borderColor: '#E11D2A',
                backgroundColor: 'rgba(225, 29, 42, 0.08)',
                fill: true,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#E11D2A',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0B0F14',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: ctx => ctx.parsed.y + '% kepadatan',
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: {
                    beginAtZero: true, max: 100,
                    ticks: { callback: v => v + '%', font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.05)' },
                },
            },
        },
    });

    // ── Donut distribusi ────────────────────────────────────────
    const dist = data.occupancy.level_distribution;
    const total = (dist.low || 0) + (dist.med || 0) + (dist.high || 0) + (dist.no_data || 0);

    if (donutChart) donutChart.destroy();
    donutChart = new Chart(document.getElementById('donut-chart'), {
        type: 'doughnut',
        data: {
            labels: ['Lengang', 'Sedang', 'Padat', 'No data'],
            datasets: [{
                data: [dist.low, dist.med, dist.high, dist.no_data],
                backgroundColor: ['#16A34A', '#D97706', '#DC2626', '#C9CED6'],
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0B0F14',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: ctx => `${ctx.label}: ${ctx.parsed} armada`,
                    },
                },
            },
        },
    });

    // Legend list
    const legend = document.getElementById('donut-legend');
    legend.innerHTML = ['low', 'med', 'high', 'no_data']
        .filter(lvl => dist[lvl] > 0 || lvl !== 'no_data')
        .map(lvl => {
            const meta = levelMeta(lvl);
            const n = dist[lvl];
            const pct = total > 0 ? Math.round((n / total) * 100) : 0;
            return `
                <div class="flex items-center" style="gap: 10px">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: ${meta.hex}"></span>
                    <span class="font-semibold flex-1" style="font-size: 13px">${meta.label}</span>
                    <span class="num font-bold" style="font-size: 13px">${n}</span>
                    <span class="num" style="font-size: 11.5px; color: var(--ink-400); width: 38px; text-align: right">
                        ${pct}%
                    </span>
                </div>
            `;
        })
        .join('');

    // ── Per-corridor ────────────────────────────────────────────
    const list = document.getElementById('per-corridor-list');
    if (!data.per_corridor.length) {
        list.innerHTML = `<div class="text-center" style="padding: 24px; color: var(--ink-400); font-size: 13px">Belum ada koridor aktif.</div>`;
    } else {
        list.innerHTML = data.per_corridor.map((c, i) => {
            const ratio = c.avg_occupancy;
            const pct = Math.round(ratio * 100);
            const lvl = densityLevel(ratio);
            const meta = levelMeta(lvl);
            const isLast = i === data.per_corridor.length - 1;
            return `
                <div class="flex items-center"
                     style="padding: 12px 18px; gap: 12px;
                            ${isLast ? '' : 'border-bottom: 1px solid var(--ink-50);'}">
                    <div class="font-extrabold text-white flex items-center justify-center flex-shrink-0"
                         style="width: 32px; height: 32px; border-radius: 8px;
                                background: ${c.color}; font-size: 12px; letter-spacing: -0.5px;
                                box-shadow: inset 0 -2px 0 rgba(0,0,0,0.15)">
                        ${escapeHtml(c.code)}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold truncate" style="font-size: 13px">${escapeHtml(c.name)}</div>
                        <div style="margin-top: 6px; height: 6px; background: var(--ink-100);
                                    border-radius: 999px; overflow: hidden">
                            <div style="width: ${Math.min(100, pct)}%; height: 100%;
                                        background: ${meta.hex}; transition: width 0.4s"></div>
                        </div>
                    </div>
                    <div class="num font-bold text-right flex-shrink-0"
                         style="width: 44px; font-size: 13px">
                        ${pct}%
                    </div>
                </div>
            `;
        }).join('');
    }

    // ── Timestamp ───────────────────────────────────────────────
    if (meta && meta.server_time) {
        const dt = new Date(meta.server_time);
        document.getElementById('updated-at').textContent = dt.toLocaleTimeString('id-ID', {
            hour: '2-digit', minute: '2-digit', second: '2-digit',
        });
    }
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}

// ── Boot ─────────────────────────────────────────────────────────
loadDashboard();
setInterval(loadDashboard, REFRESH_INTERVAL_MS);
</script>
@endpush
