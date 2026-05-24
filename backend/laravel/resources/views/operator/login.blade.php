<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Operator · CrowdEase</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --brand: #E11D2A;     --brand-600: #C8102E;  --brand-700: #A30B24;
            --brand-50:  #FEE7E9;  --brand-100: #FCD0D4;
            --ink: #0B0F14;       --ink-700: #1E242C;   --ink-500: #4A5260;
            --ink-400: #6B7280;   --ink-200: #C9CED6;   --ink-100: #E5E7EB;
            --ink-50:  #F4F5F7;    --ink-25:  #FAFAFB;
            --c-high: #DC2626;    --c-high-bg: #FEE2E2; --c-high-ring: #FCA5A5;
        }
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: var(--ink); -webkit-font-smoothing: antialiased;
        }
        .mono { font-family: 'JetBrains Mono', monospace; }

        /* Logo (same as layout) */
        .logo {
            width: 36px; height: 36px; border-radius: 9px;
            background: var(--brand); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 19px; letter-spacing: -0.5px;
            box-shadow: inset 0 -2px 0 rgba(0,0,0,0.18);
            flex-shrink: 0;
        }

        /* Decorative grid on brand panel */
        .grid-bg {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse at top right, black 0%, transparent 70%);
                    mask-image: radial-gradient(ellipse at top right, black 0%, transparent 70%);
        }
        .red-orb {
            position: absolute; top: -10%; right: -15%;
            width: 380px; height: 380px;
            background: radial-gradient(circle, rgba(225,29,42,0.45) 0%, transparent 70%);
            filter: blur(20px);
        }

        .input {
            width: 100%; padding: 11px 12px 11px 38px;
            background: #fff; border: 1px solid var(--ink-200); border-radius: 8px;
            font-size: 14px; font-family: inherit; color: var(--ink);
            outline: none; transition: border-color 0.15s;
        }
        .input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-50); }

        .btn-primary {
            width: 100%; padding: 12px;
            background: var(--brand); color: #fff;
            border: none; border-radius: 8px;
            font-weight: 700; font-size: 14px;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-primary:hover { background: var(--brand-600); }
        .btn-primary:disabled { opacity: 0.6; cursor: wait; }

        /* Responsive: pada layar kecil, sembunyikan panel brand */
        @media (max-width: 768px) {
            .brand-panel { display: none; }
            .form-panel  { width: 100%; }
        }
    </style>
</head>
<body class="flex h-full" style="background: var(--ink-25)">

    {{-- ──────── LEFT: Brand panel (gradien gelap) ──────── --}}
    <div class="brand-panel flex-1 relative text-white overflow-hidden flex flex-col justify-between"
         style="background: linear-gradient(180deg, #0B0F14 0%, #1a0d10 60%, #3a121a 100%);
                padding: 48px 56px; min-width: 0">

        <div class="grid-bg"></div>
        <div class="red-orb"></div>

        {{-- Logo bar --}}
        <div class="relative flex items-center" style="gap: 12px">
            <div class="logo">C</div>
            <div>
                <div class="font-extrabold" style="font-size: 18px; letter-spacing: -0.3px">CrowdEase</div>
                <div style="font-size: 12px; opacity: 0.6; margin-top: -1px">Portal Operator</div>
            </div>
        </div>

        {{-- Hero text --}}
        <div class="relative">
            <h1 class="font-extrabold leading-tight"
                style="font-size: 36px; letter-spacing: -1.2px; max-width: 460px">
                Pantau kepadatan armada secara real-time — dalam satu dasbor.
            </h1>
            <p style="margin-top: 24px; font-size: 14px; opacity: 0.7; max-width: 420px; line-height: 1.55">
                Akses data sensor, prediksi okupansi 5/10/15 menit, dan kelola integrasi
                webhook tim operasi TransJakarta.
            </p>

            {{-- Mini stats divider --}}
            <div class="flex"
                 style="gap: 20px; margin-top: 32px; padding-top: 20px;
                        border-top: 1px solid rgba(255,255,255,0.1)">
                @foreach ([
                    ['n' => '5',    'label' => 'koridor'],
                    ['n' => '7',    'label' => 'armada'],
                    ['n' => '20',   'label' => 'halte'],
                    ['n' => '24/7', 'label' => 'monitoring'],
                ] as $stat)
                    <div>
                        <div class="font-extrabold" style="font-size: 18px; letter-spacing: -0.5px">
                            {{ $stat['n'] }}
                        </div>
                        <div style="font-size: 11px; opacity: 0.6; margin-top: 2px; text-transform: lowercase">
                            {{ $stat['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Footer text --}}
        <div class="relative" style="font-size: 11px; opacity: 0.5; letter-spacing: 0.4px">
            CrowdEase © 2026 · v1.0.0 · Diilhami GEMASTIK XVII
        </div>
    </div>

    {{-- ──────── RIGHT: Login form ──────── --}}
    <div class="form-panel flex flex-col justify-center"
         style="width: 460px; background: #fff; padding: 56px 48px">

        <div class="font-semibold uppercase"
             style="font-size: 13px; color: var(--ink-400); letter-spacing: 0.4px">
            Selamat datang kembali
        </div>
        <div class="font-extrabold"
             style="font-size: 26px; letter-spacing: -0.6px; margin-top: 4px; margin-bottom: 28px">
            Masuk Operator
        </div>

        <form id="login-form" autocomplete="on">
            {{-- Email --}}
            <label class="block font-semibold mb-1.5" style="font-size: 12.5px; color: var(--ink-500)">
                Email
            </label>
            <div class="relative flex items-center mb-3.5">
                <span class="absolute" style="left: 12px; color: var(--ink-400); pointer-events: none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/>
                        <polyline points="22 6 12 13 2 6"/>
                    </svg>
                </span>
                <input id="email" type="email" required autocomplete="email"
                       value="operator@crowdease.test"
                       placeholder="email@crowdease.test" class="input">
            </div>

            {{-- Password --}}
            <label class="block font-semibold mb-1.5" style="font-size: 12.5px; color: var(--ink-500)">
                Password
            </label>
            <div class="relative flex items-center mb-3.5">
                <span class="absolute" style="left: 12px; color: var(--ink-400); pointer-events: none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                <input id="password" type="password" required autocomplete="current-password"
                       placeholder="••••••••"
                       class="input">
            </div>

            {{-- Error message (hidden by default) --}}
            <div id="error" class="hidden mb-3" style="font-size: 13px; color: var(--c-high);
                       background: var(--c-high-bg); border: 1px solid var(--c-high-ring);
                       border-radius: 8px; padding: 10px 12px">
                Email atau password salah.
            </div>

            <button type="submit" id="submit-btn" class="btn-primary">Masuk</button>
        </form>

        {{-- Demo account hint --}}
        <div style="margin-top: 24px; padding: 12px;
                    background: var(--ink-25); border: 1px solid var(--ink-100);
                    border-radius: 10px; font-size: 11.5px; color: var(--ink-500)">
            <div class="font-bold" style="margin-bottom: 4px; color: var(--ink-700)">
                Demo akun
            </div>
            <div class="mono">operator@crowdease.test · secret123</div>
        </div>
    </div>

    <script>
        // Kalau sudah login (ada token), langsung ke dashboard
        if (localStorage.getItem('operator_token')) {
            window.location.href = '{{ route('operator.dashboard') }}';
        }

        const form     = document.getElementById('login-form');
        const errEl    = document.getElementById('error');
        const submitBtn = document.getElementById('submit-btn');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errEl.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memproses...';

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            try {
                const res = await fetch('/api/v1/admin/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({ email, password, device_name: 'web' }),
                });

                const json = await res.json();

                if (!json.success) {
                    errEl.textContent = json.error?.message || 'Login gagal. Coba lagi.';
                    errEl.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Masuk';
                    return;
                }

                // Simpan token + info user ke localStorage
                localStorage.setItem('operator_token', json.data.token);
                localStorage.setItem('operator_user',  JSON.stringify(json.data.user || {}));

                // Redirect ke dashboard
                window.location.href = '{{ route('operator.dashboard') }}';
            } catch (e) {
                errEl.textContent = 'Tidak bisa terhubung ke server. Pastikan backend jalan.';
                errEl.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Masuk';
            }
        });
    </script>
</body>
</html>
