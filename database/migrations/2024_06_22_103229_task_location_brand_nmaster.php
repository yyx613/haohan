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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::dropIfExists('team_task_brand');

        Schema::dropIfExists('task_brands');

        Schema::create('brand_task', function (Blueprint $table) {
            $table->bigInteger('brand_id', false, true);
            $table->bigInteger('task_id', false, true);
            $table->foreign('brand_id')->references('id')->on('brands');
            $table->foreign('task_id')->references('id')->on('tasks');
            $table->primary(['brand_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_task');

        Schema::create('task_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->bigInteger('task_id', false, true);
            $table->foreign('task_id')->references('id')->on('tasks');
            $table->timestamps();
        });

        Schema::create('team_task_brand', function (Blueprint $table) {
            $table->bigInteger('team_task_id', false, true);
            $table->foreign('team_task_id')->references('id')->on('team_tasks');
            $table->bigInteger('task_brand_id', false, true);
            $table->foreign('task_brand_id')->references('id')->on('task_brands');
            $table->primary(['team_task_id', 'task_brand_id']);
            $table->timestamps();
        });

        Schema::dropIfExists('brands');

        Schema::dropIfExists('locations');

    }
};
