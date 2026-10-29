<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('label', 80)->nullable()->after('referrer');
            $table->string('route_name', 80)->nullable()->after('label');
            $table->unsignedInteger('duration_ms')->nullable()->after('route_name');
            $table->string('view_token', 32)->nullable()->after('duration_ms')->index();
        });
    }

    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex(['view_token']);
            $table->dropColumn(['label', 'route_name', 'duration_ms', 'view_token']);
        });
    }
};
