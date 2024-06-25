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
        Schema::table('team_tasks', function (Blueprint $table) {
            $table->string('name')->nullable()->default('');
            $table->bigInteger('location_id', false, true)->nullable();
            $table->foreign('location_id')->references('id')->on('locations');
            $table->string('location_name')->nullable()->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_tasks', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('location_id');
            $table->dropColumn('location_name');
        });
    }
};
