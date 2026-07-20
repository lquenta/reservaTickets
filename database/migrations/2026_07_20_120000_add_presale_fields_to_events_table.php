<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('presale_enabled')->default(false)->after('sales_paused');
            $table->string('presale_discount_type', 20)->nullable()->after('presale_enabled');
            $table->decimal('presale_discount_value', 10, 2)->nullable()->after('presale_discount_type');
            $table->dateTime('presale_starts_at')->nullable()->after('presale_discount_value');
            $table->dateTime('presale_ends_at')->nullable()->after('presale_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'presale_enabled',
                'presale_discount_type',
                'presale_discount_value',
                'presale_starts_at',
                'presale_ends_at',
            ]);
        });
    }
};
