<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Currencies offered by the captured wizard, exactly the 21 rows of
     * the capture's :currencies prop (see CurrenciesSeeder). The capture
     * id is the ISO 4217 numeric code, kept in iso_numeric; the local
     * auto-increment id stays the primary key, matching repo convention
     * (countries, brands and templates all use local ids).
     *
     * Wizard flags (data, not literals): is_available_in_wizard marks a
     * row offerable as a country's currency; always_offered_in_wizard
     * marks a row offered on every wizard run (EUR and USD, the capture's
     * [978, 840] base list; USD was added at the user's request even
     * though the prop has no USD row). No exchange rates are stored: the
     * capture has none and no conversion is built.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('symbol', 5);
            $table->string('name');
            $table->unsignedInteger('iso_numeric')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available_in_wizard')->default(true);
            $table->boolean('always_offered_in_wizard')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
