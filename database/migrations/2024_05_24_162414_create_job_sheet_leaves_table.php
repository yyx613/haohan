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
        Schema::create('job_sheet_leaves', function (Blueprint $table) {
            $table->bigInteger('job_sheet_id', false, true);
            $table->bigInteger('staff_id', false, true);
            $table->smallInteger('leave_type')->nullable(false);
            $table->foreign('job_sheet_id')->references('id')->on('job_sheets');
            $table->foreign('staff_id')->references('id')->on('staffs');
            $table->primary(['job_sheet_id', 'staff_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_sheet_leaves');
    }
};
