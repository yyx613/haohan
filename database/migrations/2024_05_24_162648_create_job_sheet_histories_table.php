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
        Schema::create('job_sheet_histories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_sheet_id', false, true);
            $table->smallInteger('history_type')->nullable(false);
            $table->foreign('job_sheet_id')->references('id')->on('job_sheets');
            $table->string('ref_id_1')->nullable();
            $table->string('ref_id_2')->nullable();
            $table->bigInteger('update_by', false, true);
            $table->foreign('update_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_sheet_histories');
    }
};
