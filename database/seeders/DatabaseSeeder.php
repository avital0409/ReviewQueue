<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Prevent UniqueConstraintViolationException by truncating tables before seeding
        Schema::disableForeignKeyConstraints();
        User::truncate();
        Item::truncate();
        Schema::enableForeignKeyConstraints();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(ItemSeeder::class);
    }
}
