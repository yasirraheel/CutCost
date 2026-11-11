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
        Schema::table('features', function (Blueprint $table) {
            $table->string('link_url')->nullable()->after('icon_svg');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('icon_svg');
        });

        Schema::table('how_it_works_steps', function (Blueprint $table) {
            $table->string('audience')->default('share')->after('icon_svg');
            $table->string('mockup_image_path')->nullable()->after('audience');
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->decimal('fee_flat', 8, 3)->default(0);
            $table->decimal('fee_percent', 8, 3)->default(0);
            $table->string('currency', 3)->default('GBP');
            $table->string('postal_regex')->nullable();
            $table->string('postal_example')->nullable();
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
        Schema::dropIfExists('countries');

        Schema::table('how_it_works_steps', function (Blueprint $table) {
            $table->dropColumn(['audience', 'mockup_image_path']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('link_url');
        });
    }
};
