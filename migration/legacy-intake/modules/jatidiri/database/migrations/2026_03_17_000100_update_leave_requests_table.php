<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->string('supervisor_nip', 64)->nullable()->index()->after('employee_name');
            $table->string('supervisor_name')->nullable()->after('supervisor_nip');
            $table->string('address')->nullable()->after('reason');
            $table->string('phone', 20)->nullable()->after('address');
            $table->string('leave_category', 100)->nullable()->after('leave_type'); // TAHUNAN, BESAR, SAKIT, MELAHIRKAN, ALASAN_PENTING, DI_LUAR_TANGGUNGAN_NEGARA
            $table->text('decision_notes')->nullable()->after('notes');
            $table->timestamp('approved_at')->nullable()->index()->after('last_synced_at');
            $table->timestamp('rejected_at')->nullable()->index()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'supervisor_nip',
                'supervisor_name',
                'address',
                'phone',
                'leave_category',
                'decision_notes',
                'approved_at',
                'rejected_at',
            ]);
        });
    }
};
