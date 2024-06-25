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
            $table->bigInteger('update_by', false, true)->nullable();
            $table->foreign('update_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_sheets', function (Blueprint $table) {
            $table->dropColumn('update_by');
        });
    }
};
