<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('row');
            $table->unsignedTinyInteger('number');
            $table->string('label');
            $table->boolean('blocked')->default(false);
            $table->timestamps();
        });

        Schema::table('seats', function (Blueprint $table) {
            $table->unique(['venue_id', 'row', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
