<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_section', function (Blueprint $table) {
            $table->string('presale_discount_type', 20)->nullable()->after('price');
            $table->decimal('presale_discount_value', 10, 2)->nullable()->after('presale_discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('event_section', function (Blueprint $table) {
            $table->dropColumn(['presale_discount_type', 'presale_discount_value']);
        });
    }
};
