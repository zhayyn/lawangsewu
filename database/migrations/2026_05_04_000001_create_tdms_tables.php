<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Kategori Aset ────────────────────────────────────────────
        Schema::create('tdms_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');         // misal: Jaringan, Server, PC, Printer, Peripheral
            $table->string('icon')->nullable(); // emoji atau icon identifier
            $table->string('color', 32)->nullable(); // warna accent (hex)
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Aset IT ──────────────────────────────────────────────────
        Schema::create('tdms_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique(); // Auto-generated: TDMS-PC-0001
            $table->foreignId('category_id')->constrained('tdms_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->json('specifications')->nullable(); // CPU, RAM, Storage, OS, IP, dsb
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('location')->nullable();    // Ruangan/gedung
            $table->string('assigned_to')->nullable(); // Nama/jabatan pemegang
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'maintenance', 'broken', 'retired'])->default('active');
            $table->string('qr_token', 64)->unique()->nullable(); // untuk QR Code
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Jadwal Pemeliharaan ──────────────────────────────────────
        Schema::create('tdms_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('tdms_assets')->cascadeOnDelete();
            $table->string('maintenance_type'); // preventive, corrective, inspection
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->date('completed_date')->nullable();
            $table->enum('status', ['pending', 'overdue', 'completed', 'cancelled'])->default('pending');
            $table->integer('interval_days')->nullable(); // untuk recurring
            $table->foreignId('assigned_to_user')->nullable()->constrained('users')->nullOnDelete();
            $table->text('completion_notes')->nullable();
            $table->timestamps();
        });

        // ── Catatan Perbaikan (Tiket Servis) ─────────────────────────
        Schema::create('tdms_service_records', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // SR-2026-0001
            $table->foreignId('asset_id')->constrained('tdms_assets')->restrictOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('issue_description');
            $table->text('action_taken')->nullable();
            $table->json('parts_replaced')->nullable(); // [{name, qty, cost}]
            $table->decimal('repair_cost', 15, 2)->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'waiting_parts', 'resolved', 'closed', 'cancelled'])->default('open');
            $table->timestamp('sla_deadline')->nullable();  // berdasarkan priority
            $table->timestamp('date_in')->useCurrent();
            $table->timestamp('date_out')->nullable();
            $table->timestamps();
        });

        // ── Pengajuan Penghapusan / Penggantian ──────────────────────
        Schema::create('tdms_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('tdms_assets')->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('reason');
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->enum('type', ['repair_vs_replace', 'end_of_life', 'upgrade', 'lost'])->default('repair_vs_replace');
            $table->enum('approval_status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->text('approval_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdms_replacements');
        Schema::dropIfExists('tdms_service_records');
        Schema::dropIfExists('tdms_maintenance_schedules');
        Schema::dropIfExists('tdms_assets');
        Schema::dropIfExists('tdms_categories');
    }
};
