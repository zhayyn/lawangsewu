<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdms_service_records', function (Blueprint $table) {
            $table->string('proof_photo_path')->nullable()->after('status');
        });

        Schema::table('tdms_maintenance_schedules', function (Blueprint $table) {
            $table->string('proof_photo_path')->nullable()->after('completion_notes');
        });
    }

    public function down(): void
    {
        Schema::table('tdms_service_records', function (Blueprint $table) {
            $table->dropColumn('proof_photo_path');
        });

        Schema::table('tdms_maintenance_schedules', function (Blueprint $table) {
            $table->dropColumn('proof_photo_path');
        });
    }
};
