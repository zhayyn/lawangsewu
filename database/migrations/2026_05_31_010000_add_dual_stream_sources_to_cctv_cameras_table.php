<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cctv_cameras', function (Blueprint $table): void {
            $table->text('primary_sd_src')->nullable()->after('iframe_src');
            $table->text('primary_hd_src')->nullable()->after('primary_sd_src');
            $table->text('fallback_src')->nullable()->after('primary_hd_src');
            $table->string('stream_provider', 40)->default('aco-badilag')->after('fallback_src');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cctv_cameras', function (Blueprint $table): void {
            $table->dropColumn([
                'primary_sd_src',
                'primary_hd_src',
                'fallback_src',
                'stream_provider',
            ]);
        });
    }
};
