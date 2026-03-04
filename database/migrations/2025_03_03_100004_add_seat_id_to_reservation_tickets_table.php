<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_tickets', function (Blueprint $table) {
            $table->foreignId('seat_id')->nullable()->after('reservation_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservation_tickets', function (Blueprint $table) {
            $table->dropForeign(['seat_id']);
        });
    }
};
