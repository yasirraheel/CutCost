<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('username')->nullable()->unique()->after('last_name');
            $table->date('birthdate')->nullable()->after('username');
            $table->string('phone', 50)->nullable()->after('birthdate');
            $table->string('intl_phone', 50)->nullable()->after('phone');
            $table->string('address1')->nullable()->after('intl_phone');
            $table->string('address2')->nullable()->after('address1');
            $table->string('city')->nullable()->after('address2');
            $table->string('zip', 30)->nullable()->after('city');
            $table->string('state')->nullable()->after('zip');
            $table->string('country', 2)->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'username',
                'birthdate',
                'phone',
                'intl_phone',
                'address1',
                'address2',
                'city',
                'zip',
                'state',
                'country',
            ]);
        });
    }
};
