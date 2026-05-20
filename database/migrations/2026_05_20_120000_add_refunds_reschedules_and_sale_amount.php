<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('sale_amount', 10, 2)->nullable()->after('confirmed_payment_at');
            $table->timestamp('refunded_at')->nullable()->after('sale_amount');
            $table->foreignId('refunded_by_user_id')->nullable()->after('refunded_at')->constrained('users')->nullOnDelete();
            $table->text('refund_reason')->nullable()->after('refunded_by_user_id');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('refund_reason');
        });

        Schema::create('event_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->dateTime('previous_starts_at');
            $table->dateTime('new_starts_at');
            $table->text('reason')->nullable();
            $table->foreignId('performed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_reschedules');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['refunded_by_user_id']);
            $table->dropColumn([
                'sale_amount',
                'refunded_at',
                'refunded_by_user_id',
                'refund_reason',
                'refund_amount',
            ]);
        });
    }
};
