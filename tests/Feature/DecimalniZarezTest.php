<?php

namespace Tests\Feature;

use App\Models\Obracun;
use App\Models\User;
use App\Support\Decimalni;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DecimalniZarezTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function upisi(): array
    {
        return [
            'zarez' => ['399,51', '399.51'],
            'tisućice i zarez' => ['1.234,56', '1234.56'],
            'razmaci' => [' 2 340,03 ', '2340.03'],
            'točka bez zareza' => ['399.51', '399.51'],
            'cijeli broj' => ['176', '176'],
            'prazno' => ['', ''],
            'null' => [null, null],
        ];
    }

    #[DataProvider('upisi')]
    public function test_citanje_upisa(mixed $upis, mixed $ocekivano): void
    {
        $this->assertSame($ocekivano, Decimalni::procitaj($upis));
    }

    public function test_prikaz_u_formi(): void
    {
        $this->assertSame('399,51', Decimalni::zaUnos(399.51));
        $this->assertSame('4,05', Decimalni::zaUnos(4.05));
        $this->assertSame('174', Decimalni::zaUnos(174.0));
        $this->assertSame('0,4', Decimalni::zaUnos(0.4));
        $this->assertSame('2340,03', Decimalni::zaUnos(2340.03));
        $this->assertSame('', Decimalni::zaUnos(null));
    }

    public function test_forme_prikazuju_i_primaju_zarez(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seed(DatabaseSeeder::class);
        $rujan = Obracun::query()->whereDate('period', '2026-09-01')->firstOrFail();

        $this->get(route('obracuni.edit', $rujan))->assertOk()->assertSee('value="2340,03"', false)->assertSee('value="1679,99"', false);
        $this->get(route('postavke.create'))->assertOk()->assertSee('value="399,51"', false)->assertSee('value="66,36"', false);
        $this->get(route('simulator', ['obracun' => $rujan->id, 'p' => ['osnovica' => '420,5']]))
            ->assertOk()
            ->assertSee('value="420,5"', false)
            ->assertViewHas('izmjeneParametara', ['osnovica' => 420.5]);

        $this->put(route('obracuni.update', $rujan), [
            'period' => '2026-09',
            'sati_redovan' => '159,5',
            'sati_blagdan' => '0',
            'sati_dopust' => '0',
            'sati_godisnji' => '16,5',
            'sati_bolovanje' => '0',
            'ucinak' => '22,5',
            'stvarni_bruto' => '2.340,03',
            'stvarna_isplata' => '1679,99',
        ])->assertSessionHasNoErrors();

        $rujan->refresh();
        $this->assertSame(159.5, $rujan->sati_redovan);
        $this->assertSame(16.5, $rujan->sati_godisnji);
        $this->assertSame(22.5, $rujan->ucinak);
        $this->assertSame(2340.03, $rujan->stvarni_bruto);
    }

    public function test_neispravan_broj_javlja_gresku(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seed(DatabaseSeeder::class);
        $rujan = Obracun::query()->whereDate('period', '2026-09-01')->firstOrFail();

        $this->put(route('obracuni.update', $rujan), [
            'period' => '2026-09',
            'sati_redovan' => '16o',
            'sati_blagdan' => '0',
            'sati_dopust' => '0',
            'sati_godisnji' => '0',
            'sati_bolovanje' => '0',
            'ucinak' => '20',
        ])->assertSessionHasErrors('sati_redovan');
    }
}
