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
            $table->dropColumn('location_name');
            $table->renameColumn('location', 'location_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_tasks', function (Blueprint $table) {
            $table->renameColumn('location_name', 'location');
            $table->string('location_name')->nullable()->default('');
        });
    }
};
