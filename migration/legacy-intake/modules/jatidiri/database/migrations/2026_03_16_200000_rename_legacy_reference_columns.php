<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE leave_requests DROP INDEX leave_requests_legacy_id_source_system_unique');
        DB::statement('ALTER TABLE leave_requests CHANGE legacy_id source_reference_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE leave_requests ADD UNIQUE leave_requests_source_reference_id_source_system_unique (source_reference_id, source_system)');

        DB::statement('ALTER TABLE study_permit_requests DROP INDEX study_permit_requests_legacy_id_source_system_unique');
        DB::statement('ALTER TABLE study_permit_requests CHANGE legacy_id source_reference_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE study_permit_requests ADD UNIQUE study_permit_requests_source_reference_id_source_system_unique (source_reference_id, source_system)');

        DB::statement('ALTER TABLE duty_letters DROP INDEX duty_letters_legacy_id_source_system_unique');
        DB::statement('ALTER TABLE duty_letters CHANGE legacy_id source_reference_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE duty_letters ADD UNIQUE duty_letters_source_reference_id_source_system_unique (source_reference_id, source_system)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE leave_requests DROP INDEX leave_requests_source_reference_id_source_system_unique');
        DB::statement('ALTER TABLE leave_requests CHANGE source_reference_id legacy_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE leave_requests ADD UNIQUE leave_requests_legacy_id_source_system_unique (legacy_id, source_system)');

        DB::statement('ALTER TABLE study_permit_requests DROP INDEX study_permit_requests_source_reference_id_source_system_unique');
        DB::statement('ALTER TABLE study_permit_requests CHANGE source_reference_id legacy_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE study_permit_requests ADD UNIQUE study_permit_requests_legacy_id_source_system_unique (legacy_id, source_system)');

        DB::statement('ALTER TABLE duty_letters DROP INDEX duty_letters_source_reference_id_source_system_unique');
        DB::statement('ALTER TABLE duty_letters CHANGE source_reference_id legacy_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE duty_letters ADD UNIQUE duty_letters_legacy_id_source_system_unique (legacy_id, source_system)');
    }
};