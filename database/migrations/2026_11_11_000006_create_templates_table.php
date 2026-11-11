<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->integer('seats')->nullable();
            $table->integer('shareable_seats')->nullable();
            $table->integer('available_seats')->nullable();
            $table->decimal('price_with_fees', 8, 2);
            $table->decimal('retail_price', 8, 2)->nullable();
            $table->string('currency', 3)->default('GBP');
            $table->text('description')->nullable();
            $table->text('description_seo')->nullable();
            $table->string('title_seo')->nullable();
            $table->text('helper_create')->nullable();
            $table->text('helper_join')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
