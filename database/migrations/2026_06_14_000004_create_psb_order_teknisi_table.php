<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-teknisi per PSB (jawaban #8).
 * Satu PSB bisa punya 1 lead teknisi + 1+ assistant teknisi.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('psb_order_teknisi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('psb_order_id');
            $table->unsignedBigInteger('teknisi_id'); // FK ke saleskit.users.id
            $table->enum('role', ['lead', 'assistant'])->default('lead');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('psb_order_id')->references('id')->on('psb_orders')->onDelete('cascade');
            $table->unique(['psb_order_id', 'teknisi_id']);
            $table->index('teknisi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psb_order_teknisi');
    }
};
