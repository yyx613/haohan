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
        Schema::table('job_sheets', function (Blueprint $table) {
            $table->smallInteger('status_flag')->nullable(false)->default(0);
        });

        DB::unprepared('ALTER TABLE job_sheet_leaves DROP PRIMARY KEY, ADD PRIMARY KEY (job_sheet_id, staff_id, leave_type)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_sheets', function (Blueprint $table) {
            $table->dropColumn('status_flag');
        });


        DB::unprepared('ALTER TABLE job_sheet_leaves DROP PRIMARY KEY, ADD PRIMARY KEY (job_sheet_id, staff_id)');
    }
};
