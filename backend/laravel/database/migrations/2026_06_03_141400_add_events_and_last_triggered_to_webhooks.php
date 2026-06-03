<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom events dan last_triggered_at yang hilang
     * dari migration 2026_05_06_073355_webhooks.
     */
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            if (! Schema::hasColumn('webhooks', 'events')) {
                $table->json('events')->nullable()->after('secret');
            }
            if (! Schema::hasColumn('webhooks', 'last_triggered_at')) {
                $table->timestamp('last_triggered_at')->nullable()->after('is_active');
            }
        });

        // Perlebar kolom secret dari 64 ke 128 agar sesuai ekspektasi controller
        Schema::table('webhooks', function (Blueprint $table) {
            $table->string('secret', 128)->change();
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn(['events', 'last_triggered_at']);
            $table->string('secret', 64)->change();
        });
    }
};
