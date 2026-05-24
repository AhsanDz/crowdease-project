<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;

/**
 * Controller untuk halaman web dasbor operator.
 *
 * Auth dilakukan di sisi client (JS cek localStorage token + redirect).
 * Controller di sini cuma serve view; data semua diambil JS via API admin.
 *
 * Pendekatan client-side auth dipilih karena:
 *   1. Konsisten dengan arsitektur API-first (token-based, stateless)
 *   2. Demo TI-4 lebih eksplisit (operator UI ≠ backend API)
 *   3. Tidak perlu setup session/CSRF tambahan untuk operator
 *
 * Trade-off: token tersimpan di localStorage (rawan XSS).
 * Untuk produksi, ganti ke httpOnly cookie atau Sanctum SPA mode.
 */
class PageController extends Controller
{
    /** GET /operator/login */
    public function login()
    {
        return view('operator.login');
    }

    /** GET /operator/dashboard */
    public function dashboard()
    {
        return view('operator.dashboard');
    }

    /**
     * GET /operator/{page} untuk halaman yang belum di-implementasi.
     * Tampilkan placeholder "Akan tersedia di Fase X" supaya tidak 404.
     */
    public function comingSoon(string $page)
    {
        $titles = [
            'routes'   => ['Koridor',   'Kelola koridor TransJakarta',                'Fase 4'],
            'vehicles' => ['Armada',    'Kelola armada bus',                          'Fase 4'],
            'stops'    => ['Halte',     'Kelola halte beserta posisi geografis',      'Fase 4'],
            'apikeys'  => ['API Keys',  'Kelola kunci akses untuk perangkat IoT',     'Fase 5'],
            'webhooks' => ['Webhooks',  'Kelola webhook untuk notifikasi keluar',     'Fase 5'],
        ];

        if (!isset($titles[$page])) {
            abort(404);
        }

        [$title, $subtitle, $phase] = $titles[$page];

        return view('operator.coming-soon', [
            'pageKey'  => $page,
            'title'    => $title,
            'subtitle' => $subtitle,
            'phase'    => $phase,
        ]);
    }
}
