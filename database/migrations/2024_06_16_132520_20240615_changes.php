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
        Schema::table('staffs', function (Blueprint $table) {
            $table->boolean('can_drive_lorry')->default(false);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->string('name')->nullable(false)->default('');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('rented')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropColumn('can_drive_lorry');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('rented');
        });
    }
};
