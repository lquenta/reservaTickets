<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedTinyInteger('col_start')->nullable()->after('row_end');
            $table->unsignedTinyInteger('col_end')->nullable()->after('col_start');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['col_start', 'col_end']);
        });
    }
};
