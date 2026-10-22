<?php

namespace Database\Factories;

use App\Models\Postavka;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Postavka>
 */
class PostavkaFactory extends Factory
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
            'vrijedi_od' => '2025-01-01',
            'osnovica' => 399.51,
            'koeficijent' => 4.05,
            'minuli_rad' => 12,
            'fond_sati' => 174,
            'stalni_dodatak' => 120,
            'topli_obrok' => 66.36,
            'stopa_mio1' => 15,
            'stopa_mio2' => 5,
            'osobni_odbitak' => 600,
            'stopa_poreza' => 18,
            'sindikat_spiv' => 0.4,
            'spiv_hto' => 0.6,
            'bolovanje_postotak' => 90,
        ];
    }
}
