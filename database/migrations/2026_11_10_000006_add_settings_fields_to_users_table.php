<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('referral_code')->nullable()->after('country');
            $table->string('language', 10)->default('en')->after('referral_code');
            $table->boolean('payout_low_amount')->default(false)->after('language');
            $table->boolean('payout_convert_currency')->default(true)->after('payout_low_amount');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'referral_code',
                'language',
                'payout_low_amount',
                'payout_convert_currency',
            ]);
        });
    }
};
