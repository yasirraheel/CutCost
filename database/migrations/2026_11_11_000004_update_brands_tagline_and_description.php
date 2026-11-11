<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The brands tagline column already exists in the original content
     * migration for fresh installs, so it is only added when missing.
     * short_description is widened to TEXT with a raw statement because
     * doctrine/dbal is not installed and ->change() is unavailable.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('brands', 'tagline')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->string('tagline')->nullable();
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE brands MODIFY short_description TEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * Only the short_description type is narrowed back. The tagline
     * column is left alone because fresh installs already had it before
     * this migration ran.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE brands MODIFY short_description VARCHAR(255) NULL');
        }
    }
};
