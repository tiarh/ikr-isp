<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('psb_orders', function (Blueprint $table) {
            $table->id();

            // ── Shared key (FK string ke saleskit.registrations.id) ──
            $table->string('registration_id')->index();

            // ── Staff assignments (FK ke saleskit.users.id, multi-teknisi di pivot) ──
            $table->unsignedBigInteger('sales_id')->nullable();
            $table->unsignedBigInteger('sales_leader_id')->nullable();
            $table->unsignedBigInteger('leader_teknisi_id')->nullable();
            $table->unsignedBigInteger('primary_teknisi_id')->nullable();

            // ── Network/infra refs (FieldOps) ──
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('router_id')->nullable();
            $table->unsignedBigInteger('olt_id')->nullable();
            $table->unsignedBigInteger('odp_asset_id')->nullable();
            $table->string('odp_code', 50)->nullable(); // e.g. 'ODP-MLG-001'
            $table->decimal('odp_lat', 10, 7)->nullable();
            $table->decimal('odp_lng', 10, 7)->nullable();
            $table->decimal('odp_distance_m', 8, 2)->nullable();
            $table->enum('coverage_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('olt_type', ['c300', 'hioso'])->nullable();
            $table->string('olt_port_label', 50)->nullable();
            $table->string('onu_serial', 100)->nullable();

            // ── Customer info (mirror saleskit) ──
            $table->string('customer_name');
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_nik', 20)->nullable();
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('package', 20)->nullable(); // 10M/15M/25M/...

            // ── PPPoE (auto-gen) ──
            $table->string('router_name', 50)->nullable();
            $table->string('pppoe_user')->nullable();
            $table->string('pppoe_password')->nullable();
            $table->timestamp('pppoe_generated_at')->nullable();

            // ── Provisioning ──
            $table->enum('provisioning_status', ['pending', 'running', 'done', 'failed'])->default('pending');
            $table->json('provisioning_log')->nullable();
            $table->timestamp('provisioned_at')->nullable();

            // ── Photos (6 files) ──
            $table->string('foto_rumah_path')->nullable();
            $table->string('foto_modem_path')->nullable();
            $table->string('foto_ktp_path')->nullable();
            $table->string('foto_odp_path')->nullable();
            $table->string('foto_odp_dalam_path')->nullable();
            $table->string('foto_router_path')->nullable();

            // ── Measurements ──
            $table->decimal('redaman_odp', 6, 2)->nullable();    // dB
            $table->decimal('redaman_router', 6, 2)->nullable(); // dB
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_long', 10, 7)->nullable();

            // ── BAI ──
            $table->string('bai_pdf_path')->nullable();
            $table->timestamp('bai_signed_at')->nullable();

            // ── Reject flow (jawaban #6: reject → balik ke provisioning) ──
            $table->string('previous_status', 30)->nullable(); // status sebelum reject, untuk revert
            $table->text('revision_note')->nullable();

            // ── State machine ──
            $table->enum('status', [
                'draft', 'submitted', 'coverage_ok', 'assigned',
                'provisioning', 'photos', 'done', 'rejected',
            ])->default('draft');

            // ── eBilling sync result ──
            $table->unsignedBigInteger('ebilling_customer_id')->nullable();
            $table->timestamp('ebilling_synced_at')->nullable();
            $table->json('ebilling_sync_log')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('coverage_status');
            $table->index('provisioning_status');
            $table->index('primary_teknisi_id');
            $table->index('olt_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psb_orders');
    }
};
