<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payout history: one row per transfer made to a user. The label is
     * 'Transfer made' in the capture; other labels are unknown, so the
     * column exists but the seeder only uses the observed one. The year
     * is derived from paid_at, so no separate year column is stored.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label')->default('Transfer made');
            $table->date('paid_at');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
