<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'first_name' => 'Demo',
                'last_name' => 'User',
                'username' => 'demo-user',
                'password' => 'Demo1234!',
                'role' => 'user',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'username' => 'demo-admin',
                'password' => 'Admin1234!',
                'role' => 'admin',
            ],
        );

        $this->call(CurrenciesSeeder::class);
        $this->call(ContentSeeder::class);
        $this->call(TemplateSeeder::class);
        $this->call(ReassuranceSeeder::class);
        $this->call(BrandFaqSeeder::class);
        $this->call(CountriesSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(FeatureDestinationPagesSeeder::class);
        $this->call(OfferSeeder::class);
        $this->call(RawPages2DemoSeeder::class);
    }
}
