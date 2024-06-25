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
            $table->string('description')->nullable()->change();
            $table->string('remark')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_tasks', function (Blueprint $table) {
            $table->string('description')->nullable(false)->change();
            $table->string('remark')->nullable(false)->change();
        });
    }
};
