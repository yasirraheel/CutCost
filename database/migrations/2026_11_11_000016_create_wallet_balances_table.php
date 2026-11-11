<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wallet balances: one row per user, metric and currency. Metric is
     * 'pending', 'available' or 'total_earnings'. EUR figures are read
     * from EUR rows, never converted from another currency: no exchange
     * rate is invented anywhere, so a currency with no offers has no
     * rows. available_on is the date the available amount can be
     * transferred (the 9th of the month in the payout policy).
     */
    public function up(): void
    {
        Schema::create('wallet_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('metric');
            $table->char('currency', 3);
            $table->decimal('amount', 10, 2);
            $table->date('available_on')->nullable();
            $table->unique(['user_id', 'metric', 'currency']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_balances');
    }
};
