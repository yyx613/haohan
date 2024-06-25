<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->integer('seq_no', false, true)->default(0);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->integer('seq_no', false, true)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropColumn('seq_no');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('seq_no');
        });
    }
};
