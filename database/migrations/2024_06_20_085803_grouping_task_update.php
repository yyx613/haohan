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
            $table->integer('no_of_booth')->default(0);
        });

        Schema::table('groupings', function (Blueprint $table) {
            $table->integer('seq_no', false, true)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_tasks', function (Blueprint $table) {
            $table->dropColumn('no_of_booth');
        });

        Schema::table('groupings', function (Blueprint $table) {
            $table->dropColumn('seq_no');
        });
    }
};