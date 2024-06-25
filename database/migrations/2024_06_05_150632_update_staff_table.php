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
        Schema::create('groupings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->timestamps();
        });

        Schema::create('work_natures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->timestamps();
        });

        Schema::table('staffs', function (Blueprint $table) {
            $table->bigInteger('grouping_id', false, true)->nullable(true);
            $table->foreign('grouping_id')->references('id')->on('groupings');
            $table->bigInteger('work_nature_id', false, true)->nullable(true);
            $table->foreign('work_nature_id')->references('id')->on('work_natures');
            $table->integer('seq_no', false, true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropColumn('grouping_id');
            $table->dropColumn('work_nature_id');
            $table->dropColumn('seq_no');
        });

        Schema::dropIfExists('groupings');
        Schema::dropIfExists('work_natures');
    }
};
