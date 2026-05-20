<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Koridor TransJakarta (mis. K1: Blok M - Kota).
 *
 * CATATAN PENTING: nama model ini sama dengan facade
 * Illuminate\Support\Facades\Route. Di file yang memakai keduanya
 * (jarang terjadi di controller), gunakan alias saat import, mis:
 *   use App\Models\Route as BusRoute;
 *
 * @property int    $id
 * @property string $code
 * @property string $name
 * @property string $color
 * @property bool   $is_active
 */
class Route extends Model
{
    /**
     * Atribut yang boleh diisi secara mass-assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'color',
        'is_active',
    ];

    /**
     * Casting tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Halte-halte pada koridor ini, terurut sesuai sequence.
     *
     * @return HasMany<Stop, $this>
     */
    
    /**
     * Armada bus yang beroperasi di koridor ini.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function activeVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class)->where('is_active', true);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class)->orderBy('sequence');
    }

    /**
     * Scope: hanya koridor yang aktif.
     *
     * @param  Builder<Route>  $query
     * @return Builder<Route>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
