<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * URL webhook eksternal yang terdaftar (TI-2, bonus).
 *
 * Saat peristiwa tertentu terjadi (mis. kepadatan melewati ambang),
 * sistem mengirim HTTP POST ke url ini. Kolom secret dipakai untuk
 * menghitung HMAC signature agar penerima dapat memverifikasi
 * keaslian payload.
 *
 * @property int    $id
 * @property string $name
 * @property string $url
 * @property string $secret
 * @property bool   $is_active
 */
class Webhook extends Model
{
    /**
     * Atribut yang boleh diisi secara mass-assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'url',
        'secret',
        'is_active',
    ];

    /**
     * Atribut yang disembunyikan saat di-serialize.
     * Secret tidak boleh bocor melalui response API.
     *
     * @var list<string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Casting tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events'            => 'array',
            'is_active'         => 'boolean',
            'total_deliveries'  => 'integer',
            'failed_deliveries' => 'integer',
            'last_triggered_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate secret saat webhook baru dibuat
        static::creating(function (Webhook $webhook) {
            if (empty($webhook->secret)) {
                $webhook->secret = 'whsec_' . Str::random(32);
            }
        });
    }

    public function recordDelivery(bool $success): void
    {
        $this->increment('total_deliveries');

        if (! $success) {
            $this->increment('failed_deliveries');
        }

        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Riwayat pengiriman webhook ini (untuk audit dan retry).
     *
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Scope: hanya webhook yang aktif.
     *
     * @param  Builder<Webhook>  $query
     * @return Builder<Webhook>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: webhook yang berlangganan event tertentu.
     *
     * Catatan: implementasi ini mengasumsikan semua webhook aktif
     * menerima semua event. Jika nanti ingin filter event per-webhook,
     * tambahkan kolom events (json) pada tabel webhooks.
     *
     * @param  Builder<Webhook>  $query
     * @return Builder<Webhook>
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('is_active', true);
    }
}
