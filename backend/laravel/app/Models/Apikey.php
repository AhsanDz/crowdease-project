<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * API key untuk autentikasi perangkat IoT (TI-1).
 *
 * Key disimpan dalam bentuk hash (kolom key_hash). Nilai asli hanya
 * ditampilkan satu kali saat dibuat. Validasi key dilakukan oleh
 * middleware ApiKeyAuth dengan membandingkan hash.
 *
 * Catatan: tabel api_keys tidak punya kolom created_at/updated_at,
 * sehingga $timestamps di-set false. Jika ingin menampilkan tanggal
 * pembuatan di dashboard, tambah $table->timestamps() pada migrasi
 * lalu hapus baris $timestamps = false di bawah.
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $name
 * @property string      $key_hash
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 */
class ApiKey extends Model
{
    /**
     * Tabel ini tidak memiliki kolom timestamp.
     */
    public $timestamps = false;

    /**
     * Atribut yang boleh diisi secara mass-assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'key_hash',
        'last_used_at',
        'revoked_at',
    ];

    /**
     * Atribut yang disembunyikan saat di-serialize.
     *
     * @var list<string>
     */
    protected $hidden = [
        'key_hash',
    ];

    /**
     * Casting tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at'   => 'datetime',
        ];
    }

    /**
     * Operator yang membuat API key ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Apakah API key masih aktif (belum dicabut).
     */
    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * Scope: hanya API key yang belum dicabut.
     *
     * @param  Builder<ApiKey>  $query
     * @return Builder<ApiKey>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }
}
