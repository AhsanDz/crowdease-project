<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Operator · CrowdEase')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    @stack('head')

    <style>
        /* Design tokens (same set as passenger) */
        :root {
            --brand: #E11D2A;   --brand-600: #C8102E;  --brand-700: #A30B24;
            --brand-50:  #FEE7E9;  --brand-100: #FCD0D4;
            --ink:     #0B0F14;  --ink-700: #1E242C;  --ink-500: #4A5260;
            --ink-400: #6B7280;  --ink-300: #9AA1AC;  --ink-200: #C9CED6;
            --ink-100: #E5E7EB;  --ink-50:  #F4F5F7;  --ink-25:  #FAFAFB;
            --c-low:   #16A34A;  --c-low-bg:   #DCFCE7;  --c-low-ring:  #86EFAC;
            --c-med:   #D97706;  --c-med-bg:   #FEF3C7;  --c-med-ring:  #FCD34D;
            --c-high:  #DC2626;  --c-high-bg:  #FEE2E2;  --c-high-ring: #FCA5A5;
            --info:    #1D4ED8;  --info-bg:    #DBEAFE;
        }

        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--ink-25);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .num  { font-feature-settings: "tnum" 1, "zero" 1; font-variant-numeric: tabular-nums; }
        .mono { font-family: 'JetBrains Mono', ui-monospace, monospace; font-feature-settings: "tnum" 1; }

        /* Logo (kotak merah dengan C) */
        .logo {
            width: 30px; height: 30px; border-radius: 8px;
            background: var(--brand); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 16px; letter-spacing: -0.5px;
            box-shadow: inset 0 -2px 0 rgba(0,0,0,0.18);
            flex-shrink: 0;
        }

        /* Sidebar nav item */
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            font-weight: 600; font-size: 13.5px;
            color: var(--ink-500);
            background: transparent;
            text-align: left;
            transition: background 0.15s;
        }
        .nav-item:hover:not(.active) { background: var(--ink-50); }
        .nav-item.active {
            color: var(--brand);
            background: var(--brand-50);
        }
        .nav-item.active::after {
            content: ''; margin-left: auto;
            width: 6px; height: 6px; border-radius: 999px;
            background: var(--brand);
        }
    </style>
</head>
<body>
    <div class="flex h-screen overflow-hidden">

        {{-- ───────────── Sidebar ───────────── --}}
        <aside class="flex flex-col bg-white border-r flex-shrink-0"
               style="width: 244px; border-color: var(--ink-100); padding: 20px 16px 16px">

            {{-- Brand --}}
            <div class="flex items-center gap-2.5 pb-4 mb-3.5 border-b"
                 style="border-color: var(--ink-100); padding: 0 6px 18px">
                <div class="logo">C</div>
                <div>
                    <div class="font-extrabold" style="font-size: 15px; letter-spacing: -0.2px">CrowdEase</div>
                    <div class="font-semibold uppercase"
                         style="font-size: 10.5px; color: var(--ink-400); letter-spacing: 0.4px">
                        Operator
                    </div>
                </div>
            </div>

            {{-- Menu --}}
            @php
                $current = request()->route()?->getName() ?? '';
                $items = [
                    ['name' => 'operator.dashboard', 'url' => route('operator.dashboard'),                'label' => 'Dashboard', 'icon' => 'dashboard'],
                    ['name' => 'routes',             'url' => url('/operator/routes'),                   'label' => 'Koridor',   'icon' => 'map'],
                    ['name' => 'vehicles',           'url' => url('/operator/vehicles'),                 'label' => 'Armada',    'icon' => 'bus'],
                    ['name' => 'stops',              'url' => url('/operator/stops'),                    'label' => 'Halte',     'icon' => 'stop'],
                    ['name' => 'apikeys',            'url' => url('/operator/apikeys'),                  'label' => 'API Keys',  'icon' => 'key'],
                    ['name' => 'webhooks',           'url' => url('/operator/webhooks'),                 'label' => 'Webhooks',  'icon' => 'webhook'],
                ];
                $activePage = $activePage ?? '';
            @endphp

            <nav class="flex flex-col flex-1" style="gap: 2px">
                @foreach ($items as $it)
                    @php
                        $isActive = ($activePage === $it['name']) || str_contains($current, $it['name']);
                    @endphp
                    <a href="{{ $it['url'] }}" class="nav-item {{ $isActive ? 'active' : '' }}">
                        @include('operator.partials.icon', ['name' => $it['icon']])
                        {{ $it['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- User info + logout --}}
            <div class="border-t mt-2" style="border-color: var(--ink-100); padding: 12px 8px">
                <div class="flex items-center gap-2.5">
                    <div id="user-avatar"
                         class="flex items-center justify-center text-white font-extrabold"
                         style="width: 32px; height: 32px; border-radius: 999px;
                                background: linear-gradient(135deg, #FCA5A5, var(--brand));
                                font-size: 13px">
                        —
                    </div>
                    <div class="flex-1 min-w-0">
                        <div id="user-name" class="font-bold truncate"
                             style="font-size: 13px; line-height: 1.1">—</div>
                        <div style="font-size: 11px; color: var(--ink-400)">Operator</div>
                    </div>
                    <button id="logout-btn" title="Keluar" style="color: var(--ink-400)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        {{-- ───────────── Main ───────────── --}}
        <main class="flex-1 flex flex-col min-w-0">

            {{-- Topbar --}}
            <header class="flex items-center justify-between bg-white border-b"
                    style="padding: 18px 28px 14px; border-color: var(--ink-100)">
                <div class="min-w-0">
                    <div class="font-extrabold truncate"
                         style="font-size: 20px; letter-spacing: -0.5px">
                        @yield('page-title', 'Dashboard')
                    </div>
                    <div style="font-size: 12.5px; color: var(--ink-400); margin-top: 2px">
                        @yield('page-subtitle', '')
                    </div>
                </div>
                <div class="flex items-center" style="gap: 8px">
                    {{-- Bell (stub) --}}
                    <button class="flex items-center justify-center bg-white border relative"
                            style="width: 36px; height: 36px; border-radius: 8px;
                                   border-color: var(--ink-100); color: var(--ink-500)"
                            title="Notifikasi (segera hadir)">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                        </svg>
                        <span class="absolute"
                              style="top: 7px; right: 8px; width: 7px; height: 7px;
                                     border-radius: 999px; background: var(--brand);
                                     border: 1.5px solid #fff"></span>
                    </button>

                    {{-- User chip --}}
                    <div class="flex items-center bg-white border"
                         style="gap: 8px; padding: 5px 10px 5px 5px;
                                border-color: var(--ink-100); border-radius: 999px">
                        <div id="user-chip-avatar"
                             class="flex items-center justify-center text-white font-extrabold"
                             style="width: 26px; height: 26px; border-radius: 999px;
                                    background: linear-gradient(135deg, #FCA5A5, var(--brand));
                                    font-size: 11px">
                            —
                        </div>
                        <span id="user-chip-name" class="font-semibold"
                              style="font-size: 12.5px">—</span>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <div class="flex-1 overflow-auto" style="padding: 20px 28px 28px">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        // ── Auth gate ────────────────────────────────────────────────────
        // Cek token di localStorage. Jika tidak ada, redirect ke login.
        // Karena cek client-side, sempat ada "flash" konten sebelum redirect —
        // untuk demo OK; produksi gunakan server-side auth (Sanctum SPA mode).
        (function () {
            const token = localStorage.getItem('operator_token');
            if (!token) {
                window.location.href = '{{ route('operator.login') }}';
                return;
            }

            // Render info user di sidebar + topbar
            try {
                const user = JSON.parse(localStorage.getItem('operator_user') || '{}');
                const name = user.name || 'Operator';
                const initials = name.split(' ')
                    .map(s => s[0] || '')
                    .slice(0, 2)
                    .join('')
                    .toUpperCase() || 'OP';
                document.getElementById('user-name').textContent = name;
                document.getElementById('user-avatar').textContent = initials;
                document.getElementById('user-chip-name').textContent = name.split(' ')[0];
                document.getElementById('user-chip-avatar').textContent = initials;
            } catch (e) {
                console.warn('Failed to parse user info', e);
            }
        })();

        // ── Helper: fetch dengan auth otomatis ─────────────────────────────
        async function fetchAuth(url, opts = {}) {
            const token = localStorage.getItem('operator_token');
            const res = await fetch(url, {
                ...opts,
                headers: {
                    'Accept':        'application/json',
                    'Content-Type':  'application/json',
                    'Authorization': 'Bearer ' + token,
                    ...(opts.headers || {}),
                },
            });
            // Auto-redirect ke login jika token kadaluarsa/invalid
            if (res.status === 401) {
                localStorage.removeItem('operator_token');
                localStorage.removeItem('operator_user');
                window.location.href = '{{ route('operator.login') }}';
                throw new Error('Session expired');
            }
            return res;
        }

        // ── Logout ─────────────────────────────────────────────────────────
        document.getElementById('logout-btn').addEventListener('click', async () => {
            try {
                await fetchAuth('/api/v1/admin/auth/logout', { method: 'POST' });
            } catch (e) {
                // Ignore — kita tetap clear local dan redirect
            }
            localStorage.removeItem('operator_token');
            localStorage.removeItem('operator_user');
            window.location.href = '{{ route('operator.login') }}';
        });
    </script>

    @stack('scripts')
</body>
</html>
