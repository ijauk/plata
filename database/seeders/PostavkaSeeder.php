<?php

namespace Database\Seeders;

use App\Models\Postavka;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Konstante obračuna očitane s platnih lista 2025.–2026.
 */
class PostavkaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(User $korisnik): void
    {
        $postavke = [
            'vrijedi_od' => '2025-01-01',
            'osnovica' => 362.40,
            'koeficijent' => 4.05,
            'minuli_rad' => 11.5,
            'fond_sati' => 174,
            'stalni_dodatak' => 100,
            'topli_obrok' => 66.36,
            'stopa_mio1' => 15,
            'stopa_mio2' => 5,
            'osobni_odbitak' => 600,
            'stopa_poreza' => 18,
            'sindikat_spiv' => 0.4,
            'spiv_hto' => 0.6,
            'bolovanje_postotak' => 90,
            'napomena' => 'Početno stanje (platna lista 1/2025)',
        ];

        $promjene = [
            ['vrijedi_od' => '2025-05-01', 'osnovica' => 384.14, 'stalni_dodatak' => 120, 'napomena' => 'Nova osnovica i stalni dodatak'],
            ['vrijedi_od' => '2025-09-01', 'osnovica' => 399.51, 'napomena' => 'Nova osnovica'],
            ['vrijedi_od' => '2025-12-01', 'minuli_rad' => 12, 'napomena' => 'Minuli rad 12 %'],
        ];

        $this->spremi($korisnik, $postavke);

        foreach ($promjene as $promjena) {
            $postavke = [...$postavke, ...$promjena];
            $this->spremi($korisnik, $postavke);
        }
    }

    /**
     * @param  array<string, mixed>  $postavke
     */
    private function spremi(User $korisnik, array $postavke): void
    {
        $postojeca = $korisnik->postavke()->whereDate('vrijedi_od', $postavke['vrijedi_od'])->first() ?? new Postavka;
        $postojeca->fill($postavke)->user()->associate($korisnik)->save();
    }
}
