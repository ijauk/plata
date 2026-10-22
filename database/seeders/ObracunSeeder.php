<?php

namespace Database\Seeders;

use App\Models\Obracun;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sati i stvarni iznosi s platnih lista 1/2025 – 9/2026.
 */
class ObracunSeeder extends Seeder
{
    /**
     * Stupci: period, redovan, blagdan, dopust, godišnji, bolovanje, učinak, stvarni bruto, stvarna isplata.
     *
     * @var list<array{0: string, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: float, 8: float}>
     */
    public const PLATNE_LISTE = [
        ['2025-01', 168, 16, 0, 0, 0, 20, 2186.17, 1586.62],
        ['2025-02', 160, 0, 0, 0, 0, 25, 1981.04, 1454.11],
        ['2025-03', 112, 0, 40, 0, 16, 25, 2090.23, 1502.53],
        ['2025-04', 120, 8, 16, 32, 0, 20, 2258.15, 1615.03],
        ['2025-05', 128, 16, 0, 32, 0, 20, 2246.87, 1613.76],
        ['2025-06', 104, 8, 0, 56, 0, 25, 2233.07, 1594.81],
        ['2025-07', 160, 0, 16, 8, 0, 20, 2353.14, 1685.83],
        ['2025-08', 40, 16, 8, 104, 0, 25, 2255.44, 1587.14],
        ['2025-09', 176, 0, 0, 0, 0, 20, 2309.78, 1666.48],
        ['2025-10', 184, 0, 0, 0, 0, 20, 2409.32, 1730.78],
        ['2025-11', 136, 8, 16, 0, 0, 25, 2196.87, 1586.90],
        ['2025-12', 168, 16, 0, 0, 0, 20, 2439.32, 1750.15],
        ['2026-01', 160, 16, 0, 0, 0, 20, 2331.75, 1680.68],
        ['2026-02', 152, 0, 0, 8, 0, 25, 2204.80, 1595.34],
        ['2026-03', 160, 0, 16, 0, 0, 20, 2340.12, 1680.05],
        ['2026-04', 160, 8, 0, 8, 0, 20, 2332.38, 1678.07],
        ['2026-05', 160, 8, 0, 0, 0, 25, 2308.98, 1665.96],
        ['2026-06', 88, 16, 0, 72, 0, 20, 2429.26, 1716.51],
        ['2026-07', 80, 0, 0, 104, 0, 20, 2555.29, 1787.58],
        ['2026-08', 160, 8, 0, 0, 0, 25, 2314.06, 1669.24],
        ['2026-09', 160, 0, 0, 16, 0, 20, 2340.03, 1679.99],
    ];

    /**
     * Run the database seeds.
     */
    public function run(User $korisnik): void
    {
        foreach (self::PLATNE_LISTE as [$period, $redovan, $blagdan, $dopust, $godisnji, $bolovanje, $ucinak, $bruto, $isplata]) {
            $obracun = $korisnik->obracuni()->whereDate('period', "{$period}-01")->first() ?? new Obracun(['period' => "{$period}-01"]);
            $obracun->user()->associate($korisnik)->fill([
                'sati_redovan' => $redovan,
                'sati_blagdan' => $blagdan,
                'sati_dopust' => $dopust,
                'sati_godisnji' => $godisnji,
                'sati_bolovanje' => $bolovanje,
                'ucinak' => $ucinak,
                'stvarni_bruto' => $bruto,
                'stvarna_isplata' => $isplata,
            ])->save();
        }

        $korisnik->obracuni()->whereDate('period', '2025-01-01')->update([
            'prethodni_bruto' => 2090.78,
            'prethodni_sati' => 176,
            'napomena' => 'Bruto i sati 12/2024 preuzeti iz Excela (platna lista nije priložena).',
        ]);

        $korisnik->obracuni()->whereDate('period', '2025-04-01')->update([
            'ostalo_bruto' => 107.05,
            'ostalo_sati' => 6,
            'napomena' => 'Ostalo: obrada od poplave (rad blagdanom i neradnim danom).',
        ]);
    }
}
