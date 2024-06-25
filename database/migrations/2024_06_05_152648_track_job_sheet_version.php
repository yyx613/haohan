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
            $table->integer('version')->nullable(false)->default(0);
        });

        Schema::table('job_sheet_histories', function (Blueprint $table) {
            $table->integer('version')->nullable(false)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_sheets', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('job_sheet_histories', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
