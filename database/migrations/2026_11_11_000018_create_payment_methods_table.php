<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saved payment methods (cards). Only the brand, last four digits,
     * expiry and holder name are stored. A full card number is never
     * stored; the Stripe payment method reference goes in stripe_ref.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('brand');
            $table->char('last4', 4);
            $table->tinyInteger('exp_month');
            $table->smallInteger('exp_year');
            $table->string('holder_name');
            $table->boolean('is_main')->default(false);
            $table->string('stripe_ref')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
