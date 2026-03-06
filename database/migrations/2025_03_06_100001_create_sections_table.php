<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('has_seats')->default(true);
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->unique(['venue_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
