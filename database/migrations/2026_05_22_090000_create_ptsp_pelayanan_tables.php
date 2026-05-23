<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Loket PTSP ─────────────────────────────────────────────────────
        if (!Schema::hasTable('ptsp_lokets')) {
            Schema::create('ptsp_lokets', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 20)->unique();
                $table->string('nama', 80);
                $table->string('prefix_antrian', 5)->default('A');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('urutan')->default(1);
                $table->timestamps();
            });

            // Seed loket default
            \DB::table('ptsp_lokets')->insert([
                ['kode' => 'PTSP-1', 'nama' => 'Loket 1 – Informasi & Umum',      'prefix_antrian' => 'A', 'is_active' => 1, 'urutan' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['kode' => 'PTSP-2', 'nama' => 'Loket 2 – Pendaftaran Perkara',   'prefix_antrian' => 'B', 'is_active' => 1, 'urutan' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['kode' => 'PTSP-3', 'nama' => 'Loket 3 – AC & Salinan Putusan',  'prefix_antrian' => 'E', 'is_active' => 1, 'urutan' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['kode' => 'PTSP-4', 'nama' => 'Loket 4 – Keuangan',              'prefix_antrian' => 'K', 'is_active' => 1, 'urutan' => 4, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // ── 2. Antrian PTSP (pengganti ptsp_queue_tickets yang lebih lengkap) ─
        if (!Schema::hasTable('ptsp_antrian')) {
            Schema::create('ptsp_antrian', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal_antrian');
                $table->string('nomor_antrian', 20);           // A-001, B-002, dst
                $table->foreignId('loket_id')->constrained('ptsp_lokets')->cascadeOnDelete();
                $table->string('nama_pemohon', 120)->nullable();
                $table->string('nomor_perkara', 80)->nullable();  // link ke SIPP
                $table->string('keperluan', 255)->nullable();
                $table->string('status', 20)->default('waiting');  // waiting|called|served|skipped|cancelled
                $table->foreignId('dipanggil_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('dilayani_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('dipanggil_at')->nullable();
                $table->timestamp('dilayani_at')->nullable();
                $table->timestamp('selesai_at')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->index(['tanggal_antrian', 'status']);
                $table->index(['tanggal_antrian', 'loket_id']);
                $table->unique(['tanggal_antrian', 'nomor_antrian']);
            });
        }

        // ── 3. Penyerahan AC / Salinan Putusan ───────────────────────────────
        if (!Schema::hasTable('ptsp_penyerahan_ac')) {
            Schema::create('ptsp_penyerahan_ac', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_perkara', 80);
                $table->string('nomor_ac', 60)->nullable();        // Nomor Akta Cerai
                $table->date('tanggal_bht')->nullable();           // Berkekuatan Hukum Tetap
                $table->date('tanggal_penyerahan');
                $table->enum('jenis', ['ac', 'salput', 'ac_salput'])->default('ac');  // AC / Salput / Keduanya
                $table->string('nama_penerima', 120)->nullable();
                $table->enum('pihak_penerima', ['pihak1', 'pihak2', 'kuasa', 'lainnya'])->nullable();
                $table->string('nik_penerima', 20)->nullable();
                $table->string('foto_path', 500)->nullable();       // path foto KTP/penerima via kamera
                $table->text('catatan')->nullable();
                $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('antrian_id')->nullable()->constrained('ptsp_antrian')->nullOnDelete();
                $table->timestamps();

                $table->index('nomor_perkara');
                $table->index('tanggal_penyerahan');
            });
        }

        // ── 4. Laporan Harian PTSP ────────────────────────────────────────────
        if (!Schema::hasTable('ptsp_laporan_harian')) {
            Schema::create('ptsp_laporan_harian', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal');
                $table->unsignedInteger('total_antrian')->default(0);
                $table->unsignedInteger('total_dilayani')->default(0);
                $table->unsignedInteger('total_ac_diserahkan')->default(0);
                $table->unsignedInteger('total_salput_diserahkan')->default(0);
                $table->json('ringkasan_loket')->nullable();  // per-loket stats
                $table->timestamps();

                $table->unique('tanggal');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ptsp_laporan_harian');
        Schema::dropIfExists('ptsp_penyerahan_ac');
        Schema::dropIfExists('ptsp_antrian');
        Schema::dropIfExists('ptsp_lokets');
    }
};
