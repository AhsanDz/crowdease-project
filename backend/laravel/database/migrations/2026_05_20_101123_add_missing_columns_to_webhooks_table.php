<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            // Tambah hanya kolom yang belum ada
            if (! Schema::hasColumn('webhooks', 'events')) {
                $table->json('events')->after('url');
            }
            if (! Schema::hasColumn('webhooks', 'secret')) {
                $table->string('secret', 64)->after('events');
            }
            if (! Schema::hasColumn('webhooks', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('secret');
            }
            if (! Schema::hasColumn('webhooks', 'total_deliveries')) {
                $table->unsignedInteger('total_deliveries')->default(0)->after('is_active');
            }
            if (! Schema::hasColumn('webhooks', 'failed_deliveries')) {
                $table->unsignedInteger('failed_deliveries')->default(0)->after('total_deliveries');
            }
            if (! Schema::hasColumn('webhooks', 'last_triggered_at')) {
                $table->dateTime('last_triggered_at')->nullable()->after('failed_deliveries');
            }
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn([
                'events', 'secret', 'is_active',
                'total_deliveries', 'failed_deliveries', 'last_triggered_at',
            ]);
        });
    }
};