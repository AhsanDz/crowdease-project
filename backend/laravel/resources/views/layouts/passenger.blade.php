<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CrowdEase — Kepadatan TransJakarta Real-time')</title>

    {{-- Inter font untuk tampilan modern --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CSS via CDN. Untuk produksi sebaiknya compile via Vite. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                }
            }
        }
    </script>

    {{-- Leaflet untuk peta. Dipakai oleh halaman map. --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .leaflet-popup-content { margin: 12px 14px; min-width: 200px; }
        .leaflet-popup-content-wrapper { border-radius: 8px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col">

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-500">
        CrowdEase &middot; Sistem Deteksi Kepadatan Transportasi Umum &middot; Tugas Akhir Mata Kuliah Teknologi Integrasi Sistem
    </footer>

    @stack('scripts')
</body>
</html>
