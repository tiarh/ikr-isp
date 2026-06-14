<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HiOS manual checklist (jawaban #3: perlu checklist step manual).
 * Setiap item harus dicentang teknisi sebelum bisa mark provisioning = done.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('psb_hioso_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('psb_order_id');
            $table->string('item_key', 50); // e.g. 'cable_connected', 'sn_registered', etc
            $table->string('item_label');
            $table->boolean('is_checked')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('psb_order_id')->references('id')->on('psb_orders')->onDelete('cascade');
            $table->unique(['psb_order_id', 'item_key']);
            $table->index('psb_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psb_hioso_checklists');
    }
};
