<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil prediksi kepadatan jangka pendek.
 *
 * Dihasilkan oleh ForecastingService setiap kali ada pencatatan
 * kepadatan baru. Bersifat immutable — sebuah prediksi tidak diubah
 * setelah dibuat, sehingga tabel hanya punya kolom created_at.
 *
 * @property int    $id
 * @property int    $vehicle_id
 * @property int    $predicted_count
 * @property \Illuminate\Support\Carbon $predicted_for
 * @property string $model_version
 */
class Forecast extends Model
{
    /**
     * Tabel hanya punya created_at, tidak ada updated_at.
     */
    const UPDATED_AT = null;

    /**
     * Atribut yang boleh diisi secara mass-assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_id',
        'predicted_count',
        'predicted_for',
        'model_version',
    ];

    /**
     * Casting tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'predicted_count' => 'integer',
            'predicted_for'   => 'datetime',
        ];
    }

    /**
     * Kendaraan yang diprediksi.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Scope: forecast yang masih relevan (predicted_for di masa depan),
     * terurut dari yang paling dekat.
     *
     * @param  Builder<Forecast>  $query
     * @return Builder<Forecast>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('predicted_for', '>=', now())
                     ->orderBy('predicted_for');
    }

    /**
     * Scope: forecast untuk kendaraan tertentu.
     *
     * @param  Builder<Forecast>  $query
     * @return Builder<Forecast>
     */
    public function scopeForVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }
}
