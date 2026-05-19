<?php

namespace App\Services;

use App\Models\DensityLog;
use App\Models\Forecast;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Layanan forecasting kepadatan jangka pendek.
 *
 * Memprediksi jumlah penumpang untuk 5, 10, dan 15 menit ke depan
 * berdasarkan riwayat pencatatan terbaru sebuah kendaraan.
 *
 * --- Model "moving_avg_v1" ---
 * Basis prediksi adalah rata-rata bergerak (moving average) dari N
 * pencatatan terakhir, lalu disesuaikan dengan tren jangka pendek
 * yang diredam (damped). Tren dihitung dari selisih rata-rata paruh
 * terbaru dengan paruh lama di dalam window.
 *
 *   prediksi(h) = rata_rata_window + tren * faktor_peredam(h)
 *
 * Faktor peredam < 1 mencegah ekstrapolasi tren meledak pada horizon
 * yang lebih jauh. Pendekatan ini sengaja dibuat sederhana namun
 * dapat dijelaskan — sesuai ruang lingkup proyek mata kuliah.
 *
 * Dipanggil oleh SensorReadingController setiap kali ada pencatatan
 * kepadatan baru masuk (titik integrasi TI-1).
 */
class ForecastingService
{
    /** Identitas versi model — disimpan di kolom forecasts.model_version. */
    private const MODEL_VERSION = 'moving_avg_v1';

    /** Jumlah pencatatan historis terbaru yang dipakai sebagai window. */
    private const WINDOW_SIZE = 5;

    /**
     * Horizon prediksi (menit ke depan) beserta faktor peredam tren.
     *
     * @var array<int, float>
     */
    private const HORIZONS = [
        5  => 0.5,
        10 => 0.8,
        15 => 1.0,
    ];

    /**
     * Hasilkan dan simpan forecast untuk sebuah kendaraan.
     *
     * Forecast lama kendaraan ini dihapus terlebih dahulu sehingga
     * tabel forecasts selalu memuat prediksi terkini saja.
     *
     * @return Collection<int, Forecast> Daftar forecast yang dibuat.
     */
    public function forecast(Vehicle $vehicle): Collection
    {
        $counts = $this->recentPassengerCounts($vehicle);

        if ($counts->isEmpty()) {
            // Belum ada data sama sekali — tidak bisa membuat prediksi.
            return collect();
        }

        $average = (float) $counts->avg();
        $trend   = $this->estimateTrend($counts);

        // Selalu simpan hanya prediksi terkini: hapus forecast lama.
        Forecast::where('vehicle_id', $vehicle->id)->delete();

        $forecasts = collect();

        foreach (self::HORIZONS as $minutesAhead => $dampingFactor) {
            $predicted = (int) round($average + $trend * $dampingFactor);
            $predicted = max(0, $predicted); // jumlah penumpang tidak negatif

            $forecasts->push(Forecast::create([
                'vehicle_id'      => $vehicle->id,
                'predicted_count' => $predicted,
                'predicted_for'   => now()->addMinutes($minutesAhead),
                'model_version'   => self::MODEL_VERSION,
            ]));
        }

        return $forecasts;
    }

    /**
     * Ambil passenger_count dari N pencatatan terakhir (terbaru dulu).
     *
     * @return Collection<int, int>
     */
    private function recentPassengerCounts(Vehicle $vehicle): Collection
    {
        return DensityLog::forVehicle($vehicle->id)
            ->limit(self::WINDOW_SIZE)
            ->pluck('passenger_count');
    }

    /**
     * Estimasi tren jangka pendek dari window pencatatan.
     *
     * Membandingkan rata-rata paruh terbaru dengan paruh lama.
     * Hasil positif berarti kepadatan sedang naik, negatif berarti turun.
     *
     * @param  Collection<int, int>  $counts  Terurut terbaru dulu.
     */
    private function estimateTrend(Collection $counts): float
    {
        if ($counts->count() < 2) {
            return 0.0;
        }

        $half      = (int) floor($counts->count() / 2);
        $recentAvg = (float) $counts->take($half)->avg();
        $olderAvg  = (float) $counts->slice($half)->avg();

        return $recentAvg - $olderAvg;
    }
}
