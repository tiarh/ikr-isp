<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PATCH untuk Saleskit DB (shared).
 * Tambah kolom `router_name` di table `registrations` (WAJIB per jawaban operator #10)
 * — untuk generate PPPoE password (nama_router lowercase).
 *
 * Migration ini jalan di connection 'saleskit', bukan 'mysql'.
 */
return new class extends Migration {
    protected $connection = 'saleskit';

    public function up(): void
    {
        Schema::connection('saleskit')->table('registrations', function (Blueprint $table) {
            if (! Schema::connection('saleskit')->hasColumn('registrations', 'router_name')) {
                $table->string('router_name', 100)->nullable()->after('package');
                $table->index('router_name');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('saleskit')->table('registrations', function (Blueprint $table) {
            if (Schema::connection('saleskit')->hasColumn('registrations', 'router_name')) {
                $table->dropIndex(['router_name']);
                $table->dropColumn('router_name');
            }
        });
    }
};
