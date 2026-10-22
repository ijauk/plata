<?php

namespace Database\Factories;

use App\Models\Obracun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Obracun>
 */
class ObracunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'period' => fake()->unique()->dateTimeBetween('2025-01-01', '2026-12-31')->format('Y-m-01'),
            'sati_redovan' => 168,
            'ucinak' => fake()->randomElement([20, 25]),
        ];
    }

    /**
     * Mjesec s godišnjim odmorom, za koji treba satnica prethodnog mjeseca.
     */
    public function sGodisnjim(float $sati = 16): static
    {
        return $this->state(fn (array $attributes): array => [
            'sati_redovan' => 168 - $sati,
            'sati_godisnji' => $sati,
            'prethodni_bruto' => 2340.03,
            'prethodni_sati' => 176,
        ]);
    }
}
