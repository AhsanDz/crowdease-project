<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Armada bus TransJakarta.
 *
 * Setiap kendaraan beroperasi di satu koridor dan mencatat
 * kepadatan penumpang secara periodik melalui perangkat IoT.
 *
 * @property int    $id
 * @property int    $route_id
 * @property string $plate_number
 * @property int    $capacity
 * @property string $status
 */
class Vehicle extends Model
{
    /**
     * Atribut yang boleh diisi secara mass-assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'route_id',
        'plate_number',
        'capacity',
        'status',
    ];

    /**
     * Casting tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    /**
     * Koridor tempat kendaraan ini beroperasi.
     *
     * @return BelongsTo<Route, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Seluruh riwayat pencatatan kepadatan kendaraan ini.
     *
     * @return HasMany<DensityLog, $this>
     */
    public function densityLogs(): HasMany
    {
        return $this->hasMany(DensityLog::class);
    }

    /**
     * Seluruh hasil forecast kepadatan kendaraan ini.
     *
     * @return HasMany<Forecast, $this>
     */
    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class);
    }

    /**
     * Pencatatan kepadatan TERBARU kendaraan ini.
     *
     * Berguna untuk endpoint GET /vehicles/{id}/density/current —
     * cukup panggil $vehicle->latestDensityLog tanpa query manual.
     *
     * @return HasOne<DensityLog, $this>
     */
    public function latestDensityLog(): HasOne
    {
        return $this->hasOne(DensityLog::class)->latestOfMany('recorded_at');
    }

    /**
     * Scope: hanya kendaraan berstatus aktif.
     *
     * @param  Builder<Vehicle>  $query
     * @return Builder<Vehicle>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
