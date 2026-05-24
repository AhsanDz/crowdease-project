<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;

/**
 * Controller untuk halaman web dasbor operator.
 *
 * Auth dilakukan di sisi client (JS cek localStorage token + redirect).
 * Controller di sini cuma serve view; data semua diambil JS via API admin.
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

    /** GET /operator/routes — Fase 4: CRUD Koridor */
    public function routes()
    {
        return view('operator.routes');
    }

    /** GET /operator/vehicles — Fase 4: CRUD Armada */
    public function vehicles()
    {
        return view('operator.vehicles');
    }

    /** GET /operator/stops — Fase 4: CRUD Halte */
    public function stops()
    {
        return view('operator.stops');
    }

    /**
     * GET /operator/{page} untuk halaman yang belum di-implementasi.
     * Setelah Fase 4, hanya apikeys dan webhooks yang masih placeholder.
     */
    public function comingSoon(string $page)
    {
        $titles = [
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
