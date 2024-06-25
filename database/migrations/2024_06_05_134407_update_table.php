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
        Schema::table('teams', function (Blueprint $table) {
            $table->time('task_time')->nullable(false);
            $table->integer('overnight')->nullable(false)->default(0);
            $table->boolean('nightshift')->nullable(false)->default(false);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->timestamps();
        });

        Schema::create('task_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->bigInteger('task_id', false, true);
            $table->foreign('task_id')->references('id')->on('tasks');
            $table->timestamps();
        });

        Schema::table('team_tasks', function (Blueprint $table) {
            $table->renameColumn('description', 'location');
            $table->bigInteger('task_id', false, true)->nullable(true);
            $table->foreign('task_id')->references('id')->on('tasks');
            $table->dropColumn('name');
            $table->dropColumn('task_time');
        });

        Schema::create('team_task_brand', function (Blueprint $table) {
            $table->bigInteger('team_task_id', false, true);
            $table->foreign('team_task_id')->references('id')->on('team_tasks');
            $table->bigInteger('task_brand_id', false, true);
            $table->foreign('task_brand_id')->references('id')->on('task_brands');
            $table->primary(['team_task_id', 'task_brand_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('task_time');
            $table->dropColumn('overnight');
            $table->dropColumn('nightshift');
        });

        Schema::table('team_tasks', function (Blueprint $table) {
            $table->renameColumn('location', 'description');
            $table->dropColumn('task_id');
            $table->string('name')->nullable(false);
            $table->time('task_time')->nullable(false);
        });

        Schema::dropIfExists('team_task_brand');
        Schema::dropIfExists('task_brands');
        Schema::dropIfExists('tasks');
    }
};
