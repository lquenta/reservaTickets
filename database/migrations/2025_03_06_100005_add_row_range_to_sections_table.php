<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedTinyInteger('row_start')->nullable()->after('capacity');
            $table->unsignedTinyInteger('row_end')->nullable()->after('row_start');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['row_start', 'row_end']);
        });
    }
};
