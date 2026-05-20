@extends('layouts.passenger')

@section('title', 'CrowdEase — Cek Kepadatan Bus Real-time')

@section('content')

{{-- Hero section --}}
<section class="bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-600 text-white">
    <div class="max-w-5xl mx-auto px-4 py-12 md:py-16">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center font-bold text-lg">CE</div>
            <span class="font-semibold tracking-wide">CrowdEase</span>
        </div>
        <h1 class="text-3xl md:text-5xl font-bold leading-tight mb-3">
            Cek Kepadatan Bus<br class="hidden md:block">
            <span class="text-cyan-200">TransJakarta</span> Real-time
        </h1>
        <p class="text-blue-100 text-base md:text-lg max-w-2xl">
            Pilih koridor untuk melihat status armada secara langsung. Data diperbarui setiap 5 detik dari sensor di kendaraan.
        </p>
    </div>
</section>

{{-- Corridor picker --}}
<section class="max-w-5xl mx-auto px-4 py-10">
    <div class="flex items-baseline justify-between mb-6">
        <h2 class="text-xl md:text-2xl font-semibold text-slate-800">Pilih Koridor</h2>
        <span id="route-count" class="text-sm text-slate-500"></span>
    </div>

    {{-- Loading state (skeleton) --}}
    <div id="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @for ($i = 0; $i < 5; $i++)
            <div class="h-32 bg-white rounded-lg border border-slate-200 animate-pulse"></div>
        @endfor
    </div>

    {{-- Error state --}}
    <div id="error" class="hidden bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <div class="text-red-700 font-medium mb-2">Gagal memuat daftar koridor</div>
        <div class="text-sm text-red-600 mb-3" id="error-message"></div>
        <button onclick="loadRoutes()" class="text-sm bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
            Coba lagi
        </button>
    </div>

    {{-- Empty state --}}
    <div id="empty" class="hidden bg-slate-100 border border-slate-200 rounded-lg p-8 text-center text-slate-500">
        Belum ada koridor terdaftar.
    </div>

    {{-- Grid of routes --}}
    <div id="routes-grid" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>
</section>

@push('scripts')
<script>
    async function loadRoutes() {
        // Toggle states
        document.getElementById('loading').classList.remove('hidden');
        document.getElementById('error').classList.add('hidden');
        document.getElementById('empty').classList.add('hidden');
        document.getElementById('routes-grid').classList.add('hidden');

        try {
            const res = await fetch('/api/v1/routes');
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const json = await res.json();
            if (!json.success) throw new Error(json.error?.message || 'Unknown error');

            document.getElementById('loading').classList.add('hidden');

            if (json.data.length === 0) {
                document.getElementById('empty').classList.remove('hidden');
                return;
            }

            const grid = document.getElementById('routes-grid');
            grid.innerHTML = json.data.map(route => `
                <a href="/koridor/${encodeURIComponent(route.code)}"
                   class="block bg-white rounded-lg border border-slate-200 hover:border-blue-300 hover:shadow-md transition overflow-hidden group">
                    <div class="h-2" style="background-color: ${route.color}"></div>
                    <div class="p-5">
                        <div class="flex items-baseline justify-between mb-2">
                            <span class="font-bold text-2xl text-slate-800">${route.code}</span>
                            <span class="text-slate-300 group-hover:text-blue-500 transition text-xl">→</span>
                        </div>
                        <h3 class="font-medium text-slate-700 mb-3 leading-snug">${escapeHtml(route.name)}</h3>
                        <div class="flex gap-3 text-xs text-slate-500">
                            <span>${route.stops_count ?? '?'} halte</span>
                            <span>·</span>
                            <span>${route.vehicles_count ?? '?'} armada</span>
                        </div>
                    </div>
                </a>
            `).join('');
            grid.classList.remove('hidden');

            document.getElementById('route-count').textContent = `${json.data.length} koridor aktif`;
        } catch (e) {
            console.error('loadRoutes failed:', e);
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error').classList.remove('hidden');
            document.getElementById('error-message').textContent = e.message;
        }
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    loadRoutes();
</script>
@endpush

@endsection
