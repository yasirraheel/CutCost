<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the columns the user-side Create Subscription flow needs.
     *
     * On offers: the owner, the draft/active status, the template, category
     * and country, the wizard fields (name, description, share, friends,
     * total seats, total plan price, custom price flag, frequency,
     * visibility, position, guest email), the secured description and the
     * credentials JSON, plus the draft hash. The brand becomes nullable so
     * a custom subscription without a brand can be stored. Existing seeded
     * offers have no owner, so they are marked active and keep showing on
     * brand detail pages.
     *
     * On templates: the three captured fields the wizard reads, cost
     * sharing, credential type and frequency, so get-templates can return
     * the same record shape as the capture.
     */
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropForeign(['brand_id']);
            $table->unsignedBigInteger('brand_id')->nullable()->change();
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();

            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft')->after('user_id');
            $table->string('hash', 32)->nullable()->after('status');
            $table->foreignId('template_id')->nullable()->after('brand_id')->constrained('templates')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('template_id')->constrained('categories')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->after('category_id')->constrained('countries')->nullOnDelete();
            $table->string('name', 80)->nullable()->after('title');
            $table->text('description')->nullable()->after('name');
            $table->unsignedTinyInteger('share')->default(1)->after('description');
            $table->unsignedTinyInteger('friends')->nullable()->after('share');
            $table->unsignedTinyInteger('total_seats')->nullable()->after('friends');
            $table->decimal('total_price', 8, 2)->nullable()->after('total_seats');
            $table->boolean('custom_price')->default(false)->after('total_price');
            $table->unsignedTinyInteger('frequency')->default(2)->after('custom_price');
            $table->unsignedTinyInteger('visibility')->nullable()->after('frequency');
            $table->decimal('lat', 10, 7)->nullable()->after('visibility');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->string('email')->nullable()->after('lng');
            $table->json('secured_description')->nullable()->after('email');
            $table->json('credentials')->nullable()->after('secured_description');
        });

        Schema::table('templates', function (Blueprint $table): void {
            $table->unsignedTinyInteger('cost_sharing')->default(1)->after('available_seats');
            $table->unsignedTinyInteger('credentials_type')->default(0)->after('cost_sharing');
            $table->unsignedTinyInteger('frequency')->default(2)->after('credentials_type');
        });

        DB::table('offers')->whereNull('user_id')->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->dropColumn(['cost_sharing', 'credentials_type', 'frequency']);
        });

        Schema::table('offers', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['template_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['country_id']);
            $table->dropForeign(['brand_id']);
            $table->dropColumn([
                'user_id', 'status', 'hash', 'template_id', 'category_id', 'country_id',
                'name', 'description', 'share', 'friends', 'total_seats', 'total_price',
                'custom_price', 'frequency', 'visibility', 'lat', 'lng', 'email',
                'secured_description', 'credentials',
            ]);
            $table->unsignedBigInteger('brand_id')->nullable(false)->change();
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();
        });
    }
};
