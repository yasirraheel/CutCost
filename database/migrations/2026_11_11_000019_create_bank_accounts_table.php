<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Connected bank accounts. Only the bank name, a masked reference
     * (asterisks plus the last digits) and the currency are stored. A
     * full IBAN is never stored; the Stripe bank account reference
     * goes in stripe_ref.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('masked_reference');
            $table->char('currency', 3);
            $table->string('stripe_ref')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
