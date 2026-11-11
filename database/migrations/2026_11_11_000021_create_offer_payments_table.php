<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment records per shared offer, member and month, backing the
     * Payments tab on the shared-subscription detail page (Raw Pages 2).
     * The month is stored as the first day of the month; paid_at is the
     * day the member's payment was recorded. No rate or amount is ever
     * converted here: rows keep the offer's own currency.
     */
    public function up(): void
    {
        Schema::create('offer_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('offer_members')->nullOnDelete();
            $table->date('month');
            $table->string('status', 20)->default('pending');
            $table->date('paid_at')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->timestamps();

            $table->unique(['offer_id', 'member_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_payments');
    }
};
