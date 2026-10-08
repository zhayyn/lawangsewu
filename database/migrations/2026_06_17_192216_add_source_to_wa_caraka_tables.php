<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `source` ke tabel WA Caraka untuk membedakan
 * instance kantor (PTSP) vs instance personal (WSL).
 *
 * source = 'office'   → WaCaraka PTSP (default, data existing tidak terpengaruh)
 * source = 'personal' → WaCaraka Personal (runtime WSL via Tailscale)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_caraka_conversations', function (Blueprint $table) {
            $table->string('source', 20)->default('office')->after('id')->index();
        });

        Schema::table('wa_caraka_messages', function (Blueprint $table) {
            $table->string('source', 20)->default('office')->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('wa_caraka_conversations', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('wa_caraka_messages', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
