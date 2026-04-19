<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->unsignedSmallInteger('layout_canvas_width')->nullable()->after('seat_columns');
            $table->unsignedSmallInteger('layout_canvas_height')->nullable()->after('layout_canvas_width');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(['layout_canvas_width', 'layout_canvas_height']);
        });
    }
};
