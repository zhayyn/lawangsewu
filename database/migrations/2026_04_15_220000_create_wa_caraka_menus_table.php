<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_menus', function (Blueprint $table) {
            $table->id();

            // Menu command — the number the user types ('1', '2', … '16', or sub-menu like '5a')
            $table->string('command', 8);

            // Human readable label
            $table->string('label');

            // How the chatbot handles this command:
            //   direct = reply immediately with response_text
            //   input  = ask for input first (prompt_text), then process with response_query
            //   prompt = show a sub-menu list (e.g. daftar jenis perkara)
            $table->enum('type', ['direct', 'input', 'prompt'])->default('direct');

            // Static response text (used for 'direct' type)
            $table->text('response_text')->nullable();

            // Dynamic SQL or identifier for query-based answers (used for 'input' type)
            // Placeholder '#' will be replaced with user input
            $table->text('response_query')->nullable();

            // SIPP query type — routes to SippService::query($type, $input) when set.
            // e.g. 'status_perkara', 'jadwal_sidang', 'akta_cerai', 'biaya_panjar', 'pembayaran'
            $table->string('sipp_query_type', 50)->nullable();

            // Prompt text to send before waiting for input (used for 'input' and 'prompt' types)
            $table->text('prompt_text')->nullable();

            // Extra text appended after dynamic response
            $table->text('extra_text')->nullable();

            // Optional description for admin UI
            $table->string('description')->nullable();

            // Ticket type created when this menu is used (null = no ticket)
            $table->string('creates_ticket_type', 24)->nullable();

            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique('command');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_menus');
    }
};
