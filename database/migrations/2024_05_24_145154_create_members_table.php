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
        Schema::create('team_members', function (Blueprint $table) {
            $table->bigInteger('team_id', false, true);
            $table->bigInteger('staff_id', false, true);
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('staff_id')->references('id')->on('staffs');
            $table->primary(['team_id', 'staff_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
