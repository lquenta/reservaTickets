<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_layout_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seat_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // seat|stage|speaker
            $table->decimal('x', 8, 2)->default(0);
            $table->decimal('y', 8, 2)->default(0);
            $table->decimal('w', 8, 2)->default(48);
            $table->decimal('h', 8, 2)->default(48);
            $table->decimal('rotation', 8, 2)->default(0);
            $table->integer('z_index')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['venue_id', 'type']);
            $table->unique(['venue_id', 'seat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_layout_elements');
    }
};
