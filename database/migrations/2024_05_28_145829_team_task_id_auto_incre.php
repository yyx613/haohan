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
        Schema::dropIfExists('team_tasks');

        Schema::create('team_tasks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('team_id', false, true);
            $table->integer('task_no', false, true);
            $table->foreign('team_id')->references('id')->on('teams');
            $table->string('name')->nullable(false);
            $table->time('task_time')->nullable(false);
            $table->string('description')->nullable(false);
            $table->string('remark')->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_tasks');

        Schema::create('team_tasks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('team_id', false, true);
            $table->integer('task_no', false, true);
            $table->foreign('team_id')->references('id')->on('teams');
            $table->string('name')->nullable(false);
            $table->time('task_time')->nullable(false);
            $table->string('description')->nullable(false);
            $table->string('remark')->nullable(false);
            $table->timestamps();
        });
    }
};
