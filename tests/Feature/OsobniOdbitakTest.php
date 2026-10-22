<?php

namespace Tests\Feature;

use App\Models\Obracun;
use App\Models\Postavka;
use App\Models\User;
use App\Services\OsobniOdbitak;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OsobniOdbitakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: int, 1: float, 2: int, 3: int, 4: float}>
     */
    public static function obitelji(): array
    {
        return [
            'samo osnovni' => [0, 100, 0, 0, 1.0],
            'jedno dijete' => [1, 100, 0, 0, 1.5],
            'dvoje djece' => [2, 100, 0, 0, 2.2],
            'troje djece' => [3, 100, 0, 0, 3.2],
            'desetero djece' => [10, 100, 0, 0, 27.1],
            'dvoje djece, pola udjela' => [2, 50, 0, 0, 1.6],
            'uzdržavani i invalidnost' => [0, 100, 1, 1, 1.8],
            'sve skupa' => [1, 100, 2, 1, 2.8],
            'više od 10 djece se ograničava' => [12, 100, 0, 0, 27.1],
        ];
    }

    #[DataProvider('obitelji')]
    public function test_faktor_prema_tablici(int $djece, float $udio, int $uzdrzavani, int $invalidnost, float $faktor): void
    {
        $parametri = ['broj_djece' => $djece, 'udio_djeca' => $udio, 'broj_uzdrzavanih' => $uzdrzavani, 'broj_invalidnosti' => $invalidnost];

        $this->assertSame($faktor, OsobniOdbitak::faktor($parametri, Carbon::parse('2026-09-01')));
    }

    public function test_djeca_smanjuju_porez_u_obracunu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DatabaseSeeder::class);
        $admin->postavke()->whereDate('vrijedi_od', '2025-12-01')->update(['broj_djece' => 2]);

        $rujan = Obracun::query()->whereDate('period', '2026-09-01')->firstOrFail()->izracun();

        $this->assertSame(2.2, $rujan['faktor_odbitka']);
        $this->assertSame(1320.0, $rujan['osobni_odbitak']);
        $this->assertSame(99.37, $rujan['porez']);
        $this->assertSame(1809.59, $rujan['isplata']);
    }

    public function test_odbitak_veci_od_dohotka_daje_porez_nula(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DatabaseSeeder::class);
        $admin->postavke()->whereDate('vrijedi_od', '2025-12-01')->update(['broj_djece' => 5]);

        $rujan = Obracun::query()->whereDate('period', '2026-09-01')->firstOrFail()->izracun();

        $this->assertSame(3900.0, $rujan['osobni_odbitak']);
        $this->assertSame(0.0, $rujan['porez']);
        $this->assertSame(2027.97, $rujan['neiskoristeni_odbitak']);
    }

    public function test_simulator_s_djecom(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seed(DatabaseSeeder::class);
        $rujan = Obracun::query()->whereDate('period', '2026-09-01')->firstOrFail();

        $this->get(route('simulator', ['obracun' => $rujan->id, 'p' => ['broj_djece' => '2']]))
            ->assertOk()
            ->assertSee('Drugo dijete')
            ->assertViewHas('simulacija', fn (array $simulacija): bool => $simulacija['isplata'] === 1809.59)
            ->assertViewHas('godisnje', fn (array $godisnje): bool => count($godisnje) === 12 && collect($godisnje)->every(fn (array $red): bool => $red['razlika'] > 0));
    }

    public function test_obracun_prikazuje_razradu_odbitka(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DatabaseSeeder::class);
        $admin->postavke()->whereDate('vrijedi_od', '2025-12-01')->update(['broj_djece' => 1, 'broj_uzdrzavanih' => 1]);

        $this->actingAs($admin)
            ->get(route('obracuni.show', $admin->obracuni()->whereDate('period', '2026-09-01')->firstOrFail()))
            ->assertOk()
            ->assertSee('Prvo dijete')
            ->assertSee('Uzdržavani članovi / alimentacija × 1')
            ->assertSee('1.200,00');
    }

    public function test_promjena_od_rodenja_djeteta_ne_mijenja_stare_obracune(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DatabaseSeeder::class);

        $this->actingAs($admin)->post(route('postavke.store'), [
            ...$admin->postavke()->orderByDesc('vrijedi_od')->firstOrFail()->only(array_keys(Postavka::PARAMETRI)),
            'vrijedi_od' => '2026-09',
            'broj_djece' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1.0, Obracun::query()->whereDate('period', '2026-08-01')->firstOrFail()->izracun()['faktor_odbitka']);
        $this->assertSame(1.5, Obracun::query()->whereDate('period', '2026-09-01')->firstOrFail()->izracun()['faktor_odbitka']);
    }
}
