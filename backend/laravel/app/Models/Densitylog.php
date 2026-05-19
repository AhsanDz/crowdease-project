<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pencatatan kepadatan kendaraan dari perangkat IoT (TI-1).
 *
 * Setiap baris adalah satu pembacaan sensor: jumlah penumpang
 * pada waktu tertentu. Bersifat immutable (write-once) — sebuah
 * pencatatan tidak pernah diubah setelah dibuat, sehingga tabel
 * hanya punya kolom created_at (tanpa updated_at).
 *
 * @property int    $id
 * @property int    $vehicle_id
 * @property int    $passenger_count
 * @property int    $capacity_at_time
 * @property string $occupancy_ratio
 * @property \Illuminate\Support\Carbon $recorded_at
 * @property string $occupancy_level  Aksesor terkomputasi
 */
class DensityLog extends Model
{
    /**
     * Tabel hanya punya created_at, tidak ada updated_at.
     * Set UPDATED_AT ke null agar Eloquent tidak mencoba mengisinya.
     */
    const UPDATED_AT = null;

    /**
     * Atribut yang boleh diisi secara mass-assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_id',
        'passenger_count',
        'capacity_at_time',
        'occupancy_ratio',
        'recorded_at',
    ];

    /**
     * Atribut terkomputasi yang ikut disertakan saat serialize.
     *
     * @var list<string>
     */
    protected $appends = [
        'occupancy_level',
    ];

    /**
     * Casting tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'passenger_count'  => 'integer',
            'capacity_at_time' => 'integer',
            'occupancy_ratio'  => 'decimal:3',
            'recorded_at'      => 'datetime',
        ];
    }

    /**
     * Kendaraan yang dicatat.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Tingkat kepadatan berdasarkan occupancy_ratio.
     *
     * - low        : ratio < 0.5   (hijau)
     * - medium     : 0.5 <= ratio < 0.8 (kuning)
     * - high       : 0.8 <= ratio <= 1.0 (merah)
     * - overcrowded: ratio > 1.0   (merah tua)
     *
     * @return Attribute<string, never>
     */
    protected function occupancyLevel(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $ratio = (float) $this->occupancy_ratio;

                return match (true) {
                    $ratio < 0.5  => 'low',
                    $ratio < 0.8  => 'medium',
                    $ratio <= 1.0 => 'high',
                    default       => 'overcrowded',
                };
            }
        );
    }

    /**
     * Scope: pencatatan dalam N menit terakhir (default 60 menit).
     *
     * @param  Builder<DensityLog>  $query
     * @return Builder<DensityLog>
     */
    public function scopeRecent(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope: pencatatan untuk kendaraan tertentu, terbaru dulu.
     *
     * @param  Builder<DensityLog>  $query
     * @return Builder<DensityLog>
     */
    public function scopeForVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId)
                     ->orderByDesc('recorded_at');
    }
}
