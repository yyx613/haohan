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
        Schema::create('team_task_brand', function (Blueprint $table) {
            $table->bigInteger('team_id', false, true);
            $table->bigInteger('brand_id', false, true);
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('brand_id')->references('id')->on('brands');
            $table->primary(['team_id', 'brand_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_task_brand');
    }
};
