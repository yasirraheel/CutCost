<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stripe reference columns on users. These hold the Stripe Connect
     * account id and the Stripe customer id per user, both empty until
     * Stripe onboarding runs. No Stripe keys are stored anywhere; these
     * are reference columns only.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_connect_account_id')->nullable()->after('payout_convert_currency');
            $table->string('stripe_customer_id')->nullable()->after('stripe_connect_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['stripe_connect_account_id', 'stripe_customer_id']);
        });
    }
};
