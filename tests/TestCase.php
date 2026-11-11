<?php

namespace Tests;

use Database\Seeders\CurrenciesSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Currencies are reference data: admin validation
        // (exists:currencies,code) and every price payload resolve through
        // the table, so seed the 22 rows (21 capture + USD) whenever the
        // test DB has been migrated (RefreshDatabase tests). Tests without
        // a migrated DB (no currencies table) skip this.
        if (Schema::hasTable('currencies')) {
            $this->seed(CurrenciesSeeder::class);
        }
    }
}
