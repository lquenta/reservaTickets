<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('address')->nullable();
            $table->string('plan_image_path')->nullable();
            $table->unsignedTinyInteger('seat_rows');
            $table->unsignedTinyInteger('seat_columns');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
