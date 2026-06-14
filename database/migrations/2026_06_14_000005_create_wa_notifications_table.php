<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WA Notification queue (jawaban #9: notif per status transition).
 * Outbox pattern — worker kirim via evolution-api.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('wa_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type', 50); // 'psb_order' / 'teknisi' / 'sales'
            $table->unsignedBigInteger('notifiable_id');
            $table->string('channel', 30); // 'wa_group_teknisi', 'wa_group_sales', 'wa_individual'
            $table->string('recipient', 50); // nomor / group id
            $table->text('message');
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_notifications');
    }
};
