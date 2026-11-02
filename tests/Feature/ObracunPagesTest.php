<?php

namespace Tests\Feature;

use App\Models\Obracun;
use App\Models\Postavka;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObracunPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $korisnik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->korisnik = User::factory()->admin()->create();
        $this->actingAs($this->korisnik);
    }

    public function test_stranice_se_prikazuju(): void
    {
        $this->seed(DatabaseSeeder::class);
        $obracun = Obracun::query()->orderByDesc('period')->firstOrFail();

        $this->get(route('obracuni.index'))->assertOk()->assertSee('9/2026')->assertSee('1.679,99');
        $this->get(route('obracuni.show', $obracun))->assertOk()->assertSee('Za isplatu')->assertSee('1.679,99');
        $this->get(route('obracuni.create'))->assertOk()->assertSee('2026-10')->assertSee('3.3 blagdani i neradni dani · VP 008');
        $this->get(route('obracuni.edit', $obracun))->assertOk()
            ->assertSee('<input type="hidden" name="period" id="period" value="2026-09"', false)
            ->assertSee('data-mjesec-birac="period"', false)
            ->assertSee('tempus-dominus@6.10.4/dist/locales/hr.js', false);
        $this->get(route('postavke.index'))->assertOk()->assertSee('399,51');
        $this->get(route('postavke.create'))->assertOk();
        $this->get(route('simulator'))->assertOk()->assertSee('Simulator');
    }

    public function test_listic_se_preuzima_kao_pdf(): void
    {
        $this->seed(DatabaseSeeder::class);
        $obracun = Obracun::query()->orderByDesc('period')->firstOrFail();

        $this->get(route('obracuni.show', $obracun))->assertSee(route('obracuni.pdf', $obracun));

        $odgovor = $this->get(route('obracuni.pdf', $obracun))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('listic-2026-09.pdf');

        $this->assertStringStartsWith('%PDF-', $odgovor->getContent());
    }

    public function test_novi_obracun_preuzima_satnicu_prethodnog_mjeseca(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('obracuni.store'), [
            'period' => '2026-10',
            'sati_redovan' => 160,
            'sati_blagdan' => 0,
            'sati_dopust' => 0,
            'sati_godisnji' => 16,
            'sati_bolovanje' => 0,
            'ucinak' => 20,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $listopad = Obracun::query()->whereDate('period', '2026-10-01')->firstOrFail();

        $this->assertEqualsWithDelta(2340.03 / 176, $listopad->satnicaPrethodna(), 1e-9);
        $this->assertSame(212.73, collect($listopad->izracun()['stavke'])->firstWhere('sifra', '068')['iznos']);
    }

    public function test_prvi_obracun_s_godisnjim_trazi_prethodni_mjesec(): void
    {
        Postavka::factory()->for($this->korisnik)->create();

        $this->post(route('obracuni.store'), [
            'period' => '2025-03',
            'sati_redovan' => 152,
            'sati_blagdan' => 0,
            'sati_dopust' => 0,
            'sati_godisnji' => 16,
            'sati_bolovanje' => 0,
            'ucinak' => 20,
        ])->assertSessionHasErrors('prethodni_bruto');

        $this->assertDatabaseCount('obracuni', 0);
    }

    public function test_nova_promjena_konstanti_vrijedi_od_svog_mjeseca(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('postavke.store'), [
            ...Postavka::query()->orderByDesc('vrijedi_od')->firstOrFail()->parametri(),
            'vrijedi_od' => '2026-09',
            'osnovica' => '420,00',
        ])->assertRedirect(route('postavke.index'))->assertSessionHasNoErrors();

        $kolovoz = Obracun::query()->whereDate('period', '2026-08-01')->firstOrFail();
        $rujan = Obracun::query()->whereDate('period', '2026-09-01')->firstOrFail();

        $this->assertSame(399.51, $kolovoz->postavka()->osnovica);
        $this->assertSame(420.0, $rujan->postavka()->osnovica);
        $this->assertGreaterThan($rujan->stvarna_isplata, $rujan->izracun()['isplata']);
    }

    public function test_simulator_pokazuje_razliku_za_promijenjenu_osnovicu(): void
    {
        $this->seed(DatabaseSeeder::class);
        $obracun = Obracun::query()->orderByDesc('period')->firstOrFail();

        $odgovor = $this->get(route('simulator', ['obracun' => $obracun->id, 'p' => ['osnovica' => '420,00']]));

        $odgovor->assertOk()
            ->assertViewHas('izmjeneParametara', ['osnovica' => 420.0])
            ->assertViewHas('godisnje', fn (array $godisnje): bool => count($godisnje) === 12 && $godisnje[11]['razlika'] > 0)
            ->assertViewHas('simulacija', fn (array $simulacija): bool => $simulacija['isplata'] > 1679.99);
    }

    public function test_simulator_pokazuje_isplatu_za_svaki_ucinak(): void
    {
        $this->seed(DatabaseSeeder::class);
        $rujan = Obracun::query()->whereDate('period', '2026-09-01')->firstOrFail();

        $this->get(route('simulator', ['obracun' => $rujan->id]))
            ->assertOk()
            ->assertSee('20 % → 1.679,99 €')
            ->assertViewHas('poUcinku', fn (array $poUcinku): bool => array_column($poUcinku, 'ucinak') === [20.0, 25.0]
                && $poUcinku[1]['isplata'] > $poUcinku[0]['isplata']);

        $this->get(route('simulator', ['obracun' => $rujan->id, 'u' => ['ucinak' => '22,5']]))
            ->assertOk()
            ->assertViewHas('izmjeneUnosa', ['ucinak' => 22.5])
            ->assertViewHas('godisnje', [])
            ->assertViewHas('poUcinku', fn (array $poUcinku): bool => array_column($poUcinku, 'ucinak') === [20.0, 22.5, 25.0]);
    }

    public function test_simulator_bez_izmjena_ne_racuna_godisnji_ucinak(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('simulator'))->assertOk()->assertViewHas('izmjeneParametara', [])->assertViewHas('godisnje', []);
    }
}
