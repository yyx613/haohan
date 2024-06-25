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
            $table->unsignedBigInteger('id');
        });

        DB::unprepared('ALTER TABLE team_tasks DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('ALTER TABLE team_tasks DROP PRIMARY KEY, ADD PRIMARY KEY (team_id, task_no)');

        Schema::table('team_tasks', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
};
