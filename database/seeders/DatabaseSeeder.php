<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Platne liste 2025.–2026. upisuju se prvom administratoru (napravi ga s `php artisan plata:admin`).
     */
    public function run(): void
    {
        $admin = User::query()->where('is_admin', true)->orderBy('id')->first()
            ?? throw new RuntimeException('Nema administratora – prvo pokreni `php artisan plata:admin tvoj@email.hr`.');

        $this->callWith([PostavkaSeeder::class, ObracunSeeder::class], ['korisnik' => $admin]);
    }
}
