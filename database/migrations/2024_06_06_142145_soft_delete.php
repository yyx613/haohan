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
            $table->softDeletes();
        });

        Schema::table('groupings', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('work_natures', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('task_brands', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('groupings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('work_natures', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('task_brands', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
