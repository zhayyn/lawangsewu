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
        Schema::create('feature_permissions', function (Blueprint $table) {
            $table->id();
               $table->unsignedBigInteger('role_id')->nullable()->index();
               $table->unsignedBigInteger('user_id')->nullable()->index();
               $table->string('feature_key')->index();
               $table->boolean('enabled')->default(true);
            $table->timestamps();
           
               // Only one of role_id or user_id should be set per row
               $table->unique(['role_id', 'feature_key'], 'role_feature_unique');
               $table->unique(['user_id', 'feature_key'], 'user_feature_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_permissions');
    }
};
