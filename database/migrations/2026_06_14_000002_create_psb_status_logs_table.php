<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('psb_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('psb_order_id');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->json('meta')->nullable(); // payload tambahan (coverage_distance, teknisi_id, dll)
            $table->timestamps();

            $table->foreign('psb_order_id')->references('id')->on('psb_orders')->onDelete('cascade');
            $table->index('psb_order_id');
            $table->index('to_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psb_status_logs');
    }
};
