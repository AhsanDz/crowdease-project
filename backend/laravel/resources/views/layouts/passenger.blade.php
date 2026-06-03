<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CrowdEase — Kepadatan TransJakarta')</title>

    {{-- Plus Jakarta Sans (sans) + JetBrains Mono (numerik) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Tailwind via CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin></script>

    <style>
        /* CrowdEase Design Tokens — diadopsi dari prototipe Claude Design */
        :root {
            /* Brand: TransJakarta red */
            --brand:      #E11D2A;
            --brand-600:  #C8102E;
            --brand-700:  #A30B24;
            --brand-50:   #FEE7E9;
            --brand-100:  #FCD0D4;

            /* Neutrals (ink scale) */
            --ink:        #0B0F14;
            --ink-700:    #1E242C;
            --ink-500:    #4A5260;
            --ink-400:    #6B7280;
            --ink-300:    #9AA1AC;
            --ink-200:    #C9CED6;
            --ink-100:    #E5E7EB;
            --ink-50:     #F4F5F7;
            --ink-25:     #FAFAFB;

            /* Density traffic lights */
            --c-low:      #16A34A;  --c-low-bg:  #DCFCE7;  --c-low-ring:  #86EFAC;
            --c-med:      #D97706;  --c-med-bg:  #FEF3C7;  --c-med-ring:  #FCD34D;
            --c-high:     #DC2626;  --c-high-bg: #FEE2E2;  --c-high-ring: #FCA5A5;
        }

        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: var(--ink-25);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .num  { font-feature-settings: "tnum" 1, "zero" 1; font-variant-numeric: tabular-nums; }
        .mono { font-family: 'JetBrains Mono', ui-monospace, monospace; font-feature-settings: "tnum" 1; }

        /* Logo - kotak merah dengan huruf C */
        .logo {
            width: 28px; height: 28px; border-radius: 8px;
            background: var(--brand); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 15px; letter-spacing: -0.5px;
            box-shadow: inset 0 -2px 0 rgba(0,0,0,0.18);
        }

        /* Pill (badge dengan tone) */
        .pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 3px 9px;
            font-size: 11.5px; font-weight: 600; letter-spacing: 0.1px;
            border-radius: 999px; white-space: nowrap;
            border: 1px solid transparent;
        }
        .pill-lg { padding: 6px 12px; font-size: 13px; }
        .pill-neutral { background: var(--ink-50);    color: var(--ink-700);  border-color: var(--ink-100); }
        .pill-brand   { background: var(--brand-50);  color: var(--brand-700); border-color: var(--brand-100); }
        .pill-low     { background: var(--c-low-bg);  color: var(--c-low);    border-color: var(--c-low-ring); }
        .pill-med     { background: var(--c-med-bg);  color: var(--c-med);    border-color: var(--c-med-ring); }
        .pill-high    { background: var(--c-high-bg); color: var(--c-high);   border-color: var(--c-high-ring); }
        .pill-dot     { width: 6px; height: 6px; border-radius: 999px; flex-shrink: 0; }
        .pill-dot-lg  { width: 8px; height: 8px; }

        /* Animasi */
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }
        .pulse-anim { animation: pulse 1.4s ease-in-out infinite; }

        /* Leaflet adjustments */
        .leaflet-container { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #EDEEF0; }
        .leaflet-tile { filter: saturate(0.7) brightness(1.02); }
        .leaflet-control-attribution { font-size: 10px !important; background: rgba(255,255,255,0.7) !important; }
        .leaflet-bar a { border-radius: 0 !important; }

        /* Marker kustom (rendered via L.divIcon) */
        .bus-marker {
            width: 28px; height: 28px; border-radius: 999px;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800; color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-feature-settings: "tnum" 1;
        }
        .bus-marker.low     { background: var(--c-low); }
        .bus-marker.med     { background: var(--c-med); }
        .bus-marker.high    { background: var(--c-high); }
        .bus-marker.unknown { background: var(--ink-300); }
        .bus-marker.selected {
            transform: scale(1.18);
            outline: 3px solid rgba(225, 29, 42, 0.35);
            outline-offset: 2px;
        }

        .stop-marker {
            width: 12px; height: 12px; border-radius: 999px;
            background: #fff;
            border: 3px solid var(--ink);
            box-shadow: 0 1px 3px rgba(0,0,0,0.25);
        }
        .stop-marker.major {
            width: 16px; height: 16px; border-color: var(--brand);
        }

        /* Bottom sheet (slide-up vehicle detail) */
        .sheet {
            position: absolute; left: 0; right: 0; bottom: 0;
            background: #fff;
            border-top-left-radius: 20px; border-top-right-radius: 20px;
            box-shadow: 0 -8px 32px rgba(11,15,20,0.15);
            padding: 10px 18px 20px;
            z-index: 100;
            max-height: 75vh; overflow-y: auto;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sheet.open { transform: translateY(0); }
    </style>
</head>
<body>
    <div class="h-full flex flex-col">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
