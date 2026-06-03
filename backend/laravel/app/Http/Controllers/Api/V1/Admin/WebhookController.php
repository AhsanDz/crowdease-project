<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookDispatchService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /** GET /api/v1/admin/webhooks */
    public function index(): JsonResponse
    {
        $webhooks = Webhook::orderByDesc('created_at')
            ->get()
            ->map(fn ($w) => $this->format($w));

        return ApiResponse::success($webhooks);
    }

    /** POST /api/v1/admin/webhooks */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'   => 'required|string|max:100',
            'url'    => 'required|url|max:500',
            'events' => 'nullable|array',
            'events.*' => 'string|in:density.alert,density.critical,*',
            'secret' => 'nullable|string|max:128',
        ]);

        $webhook = Webhook::create([
            'name'   => $data['name'],
            'url'    => $data['url'],
            'events' => $data['events'] ?? ['density.alert'],
            'secret' => $data['secret'] ?? bin2hex(random_bytes(16)),
        ]);

        return ApiResponse::success($this->format($webhook), ['message' => 'Webhook berhasil dibuat.'], 201);
    }

    /** PUT /api/v1/admin/webhooks/{webhook} */
    public function update(Request $request, Webhook $webhook): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|required|string|max:100',
            'url'       => 'sometimes|required|url|max:500',
            'events'    => 'sometimes|nullable|array',
            'events.*'  => 'string|in:density.alert,density.critical,*',
            'secret'    => 'sometimes|nullable|string|max:128',
            'is_active' => 'sometimes|boolean',
        ]);

        $webhook->update($data);

        return ApiResponse::success($this->format($webhook->fresh()));
    }

    /** DELETE /api/v1/admin/webhooks/{webhook} */
    public function destroy(Webhook $webhook): JsonResponse
    {
        $webhook->delete();

        return ApiResponse::success(null, 'Webhook berhasil dihapus.');
    }

    /**
     * POST /api/v1/admin/webhooks/{webhook}/test
     *
     * Kirim test payload ke URL webhook.
     * Berguna saat demo untuk membuktikan TI-2 tanpa menunggu density event.
     */
    public function test(Webhook $webhook): JsonResponse
    {
        try {
            WebhookDispatchService::dispatchTo($webhook, 'webhook.test', [
                'message'    => 'Test ping dari CrowdEase Operator Dashboard',
                'webhook_id' => $webhook->id,
                'webhook'    => $webhook->name,
            ]);

            return ApiResponse::success([
                'webhook_id' => $webhook->id,
                'url'        => $webhook->url,
            ], 'Test payload berhasil dikirim ke ' . $webhook->url);
        } catch (\Throwable $e) {
            return ApiResponse::error('DELIVERY_FAILED',
                'Gagal mengirim ke ' . $webhook->url . ': ' . $e->getMessage(),
                422
            );
        }
    }

    private function format(Webhook $w): array
    {
        return [
            'id'                => $w->id,
            'name'              => $w->name,
            'url'               => $w->url,
            'events'            => $w->events ?? ['density.alert'],
            'is_active'         => (bool) $w->is_active,
            'last_triggered_at' => $w->last_triggered_at?->diffForHumans(),
            'created_at'        => $w->created_at->toDateTimeString(),
        ];
    }
}
