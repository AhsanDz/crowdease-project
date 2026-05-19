<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Halte pada sebuah koridor.
 *
 * Menyimpan posisi geografis (latitude/longitude) dan urutan
 * (sequence) halte dalam koridor. Dipakai oleh aplikasi penumpang
 * untuk menampilkan marker halte di peta Leaflet.
 *
 * @property int    $id
 * @property int    $route_id
 * @property string $name
 * @property string $latitude
 * @property string $longitude
 * @property int    $sequence
 */
class Stop extends Model
{
    /**
     * Atribut yang boleh diisi secara mass-assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'route_id',
        'name',
        'latitude',
        'longitude',
        'sequence',
    ];

    /**
     * Casting tipe atribut.
     *
     * Latitude/longitude dicast decimal:6 agar presisi 6 angka
     * di belakang koma terjaga (sesuai kolom decimal(9,6)).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude'  => 'decimal:6',
            'longitude' => 'decimal:6',
            'sequence'  => 'integer',
        ];
    }

    /**
     * Koridor tempat halte ini berada.
     *
     * @return BelongsTo<Route, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
