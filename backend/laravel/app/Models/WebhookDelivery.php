<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log pengiriman webhook (TI-2, bonus).
 *
 * Setiap baris mencatat satu percobaan pengiriman webhook ke URL
 * eksternal: event apa, payload yang dikirim, percobaan ke berapa,
 * dan status hasilnya (pending/delivered/failed).
 *
 * @property int    $id
 * @property int    $webhook_id
 * @property string $event
 * @property array  $payload
 * @property int    $attempt
 * @property string $status
 */
class WebhookDelivery extends Model
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
        'webhook_id',
        'event',
        'payload',
        'attempt',
        'status',
    ];

    /**
     * Casting tipe atribut.
     *
     * Payload disimpan sebagai JSON di database, dicast ke array
     * agar mudah diakses sebagai struktur data PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempt' => 'integer',
        ];
    }

    /**
     * Webhook yang menjadi induk pengiriman ini.
     *
     * @return BelongsTo<Webhook, $this>
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Scope: pengiriman yang masih menunggu (belum tuntas).
     *
     * @param  Builder<WebhookDelivery>  $query
     * @return Builder<WebhookDelivery>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: pengiriman yang berhasil.
     *
     * @param  Builder<WebhookDelivery>  $query
     * @return Builder<WebhookDelivery>
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope: pengiriman yang gagal final.
     *
     * @param  Builder<WebhookDelivery>  $query
     * @return Builder<WebhookDelivery>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
