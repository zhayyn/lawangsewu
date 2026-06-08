<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_caraka_conversations', function (Blueprint $table) {
            // Foto profil WA (URL CDN WhatsApp, expire ~24 jam)
            $table->string('profile_photo_url', 512)->nullable()->after('remote_name');

            // Nomor HP yang terresolve dari @lid — agar bisa ditampilkan ke operator
            $table->string('resolved_number', 64)->nullable()->after('profile_photo_url');

            // Waktu terakhir profil di-refresh dari server runtime
            $table->timestamp('profile_synced_at')->nullable()->after('resolved_number');
        });
    }

    public function down(): void
    {
        Schema::table('wa_caraka_conversations', function (Blueprint $table) {
            $table->dropColumn(['profile_photo_url', 'resolved_number', 'profile_synced_at']);
        });
    }
};
