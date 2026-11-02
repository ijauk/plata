<?php

namespace Tests\Feature;

use App\Models\Obracun;
use App\Models\Postavka;
use App\Models\User;
use App\Services\KalkulatorPlace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KalkulatorPlaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->admin()->create();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Topli obrok i MIO II. stup s platnih lista 1/2025 – 9/2026.
     *
     * @return array<string, array{0: string, 1: float, 2: float}>
     */
    public static function platneListe(): array
    {
        $podaci = [
            '2025-01' => [66.36, 109.31], '2025-02' => [66.36, 99.05], '2025-03' => [44.24, 104.51],
            '2025-04' => [48.26, 112.91], '2025-05' => [54.29, 112.34], '2025-06' => [44.24, 111.65],
            '2025-07' => [57.70, 117.66], '2025-08' => [22.12, 112.77], '2025-09' => [66.36, 115.49],
            '2025-10' => [66.36, 120.47], '2025-11' => [59.72, 109.84], '2025-12' => [66.36, 121.97],
            '2026-01' => [66.36, 116.59], '2026-02' => [63.04, 110.24], '2026-03' => [60.33, 117.01],
            '2026-04' => [63.34, 116.62], '2026-05' => [66.36, 115.45], '2026-06' => [39.21, 121.46],
            '2026-07' => [28.85, 127.76], '2026-08' => [66.36, 115.70], '2026-09' => [60.33, 117.00],
        ];

        $slucajevi = [];
        foreach ($podaci as $period => [$topliObrok, $mio2]) {
            $slucajevi[$period] = [$period, $topliObrok, $mio2];
        }

        return $slucajevi;
    }

    #[DataProvider('platneListe')]
    public function test_obracun_odgovara_platnoj_listi(string $period, float $topliObrok, float $mio2): void
    {
        $obracun = Obracun::query()->whereDate('period', "{$period}-01")->firstOrFail();
        $rezultat = $obracun->izracun();

        $this->assertSame($obracun->stvarni_bruto, $rezultat['bruto'], 'bruto');
        $this->assertSame($topliObrok, $rezultat['topli_obrok'], 'topli obrok');
        $this->assertSame($mio2, $rezultat['mio2'], 'MIO II. stup');
        $this->assertEqualsWithDelta($obracun->stvarna_isplata, $rezultat['isplata'], 0.011, 'isplata');
    }

    public function test_stavke_odgovaraju_platnim_listama(): void
    {
        $stavke = fn (string $period): array => array_column(
            Obracun::query()->whereDate('period', "{$period}-01")->firstOrFail()->izracun()['stavke'], 'iznos', 'sifra'
        );

        $this->assertSame(['001' => 1580.08, '008' => 190.07, '030' => 100.0, '301' => 316.02], $stavke('2025-01'));
        $this->assertSame(['001' => 1053.38, '013' => 495.26, '020' => 178.24, '030' => 100.0, '301' => 263.35], $stavke('2025-03'));
        $this->assertSame(['001' => 398.78, '008' => 204.62, '013' => 102.31, '068' => 1330.04, '030' => 120.0, '301' => 99.69], $stavke('2025-08'));
        $this->assertSame(['001' => 833.18, '068' => 1435.47, '030' => 120.0, '301' => 166.64], $stavke('2026-07'));
    }

    public function test_prethodni_mjesec_bez_stvarnog_bruta_koristi_izracun(): void
    {
        Obracun::query()->whereDate('period', '2026-06-01')->update(['stvarni_bruto' => null]);

        $srpanj = Obracun::query()->whereDate('period', '2026-07-01')->firstOrFail();

        $this->assertSame(1787.58, $srpanj->izracun()['isplata']);
    }

    public function test_bez_satnice_prethodnog_mjeseca_nema_obracuna_godisnjeg(): void
    {
        $parametri = Obracun::query()->firstOrFail()->postavka()->parametri();

        $this->expectException(InvalidArgumentException::class);

        app(KalkulatorPlace::class)->izracunaj($parametri, [
            'sati_redovan' => 160, 'sati_blagdan' => 0, 'sati_dopust' => 0, 'sati_godisnji' => 16, 'sati_bolovanje' => 0,
            'ucinak' => 20, 'ostalo_bruto' => 0, 'ostalo_sati' => 0, 'satnica_prethodna' => null,
        ]);
    }

    public function test_drugi_korisnik_bez_sindikata_s_bolovanjem(): void
    {
        $korisnica = User::factory()->create();
        Postavka::factory()->for($korisnica)->create([
            'vrijedi_od' => '2026-06-01',
            'koeficijent' => 7.811,
            'minuli_rad' => 15.5,
            'sindikat_spiv' => 0,
            'spiv_hto' => 0,
        ]);

        Obracun::factory()->for($korisnica)->create([
            'period' => '2026-08-01', 'sati_redovan' => 152, 'sati_blagdan' => 8, 'sati_bolovanje' => 8, 'ucinak' => 15,
            'prethodni_bruto' => 4481.58, 'prethodni_sati' => 184, 'stvarni_bruto' => 4111.04, 'stvarna_isplata' => 2868.05,
        ]);
        $rujan = Obracun::factory()->for($korisnica)->create([
            'period' => '2026-09-01', 'sati_redovan' => 160, 'sati_godisnji' => 8, 'sati_bolovanje' => 8, 'ucinak' => 10,
            'stvarni_bruto' => 4137.61, 'stvarna_isplata' => 2882.60,
        ]);
        $kolovoz = $korisnica->obracuni()->whereDate('period', '2026-08-01')->firstOrFail();

        $this->assertSame(
            ['001' => 3148.55, '008' => 194.85, '020' => 175.36, '030' => 120.0, '301' => 472.28],
            array_column($kolovoz->izracun()['stavke'], 'iznos', 'sifra'),
        );
        $this->assertSame(63.20, $kolovoz->izracun()['topli_obrok']);
        $this->assertEqualsWithDelta(2868.05, $kolovoz->izracun()['isplata'], 0.011, 'MIO I. stup se kod poslodavca ponekad zaokružuje drukčije');

        $this->assertSame(
            ['001' => 3314.26, '068' => 195.76, '020' => 176.16, '030' => 120.0, '301' => 331.43],
            array_column($rujan->izracun()['stavke'], 'iznos', 'sifra'),
        );
        $this->assertSame('stvarni bruto s platne liste 8/2026', $rujan->prethodniMjesec()['izvor']);
        $this->assertSame(2882.60, $rujan->izracun()['isplata']);
    }
}
