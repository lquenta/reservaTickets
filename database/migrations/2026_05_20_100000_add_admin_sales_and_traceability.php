<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('sold_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('sale_type', 32)->default('standard')->after('sold_by_user_id');
            $table->timestamp('seller_delivery_acknowledged_at')->nullable()->after('payment_receipt_path');
            $table->foreignId('seller_delivery_acknowledged_by_user_id')->nullable()->after('seller_delivery_acknowledged_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('role')->constrained('users')->nullOnDelete();
            $table->string('provisioned_via', 32)->nullable()->after('created_by_user_id');
            $table->string('ci', 15)->nullable()->change();
        });

        Schema::table('reservation_audit_logs', function (Blueprint $table) {
            $table->foreignId('performed_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservation_audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('performed_by_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn('provisioned_via');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_delivery_acknowledged_by_user_id');
            $table->dropConstrainedForeignId('sold_by_user_id');
            $table->dropColumn([
                'sale_type',
                'seller_delivery_acknowledged_at',
            ]);
        });
    }
};
