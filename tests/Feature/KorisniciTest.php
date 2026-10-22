<?php

namespace Tests\Feature;

use App\Models\Obracun;
use App\Models\Postavka;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KorisniciTest extends TestCase
{
    use RefreshDatabase;

    public function test_gost_se_preusmjerava_na_prijavu(): void
    {
        $this->get(route('obracuni.index'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Prijava');
    }

    public function test_prijava_i_odjava(): void
    {
        $korisnik = User::factory()->create(['password' => 'tajna-lozinka']);

        $this->post(route('login'), ['email' => $korisnik->email, 'password' => 'kriva'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login'), ['email' => $korisnik->email, 'password' => 'tajna-lozinka'])->assertRedirect(route('obracuni.index'));
        $this->assertAuthenticatedAs($korisnik);

        $this->post(route('odjava'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_samo_admin_upravlja_korisnicima(): void
    {
        $this->actingAs(User::factory()->create())->get(route('korisnici.index'))->assertForbidden();
        $this->post(route('korisnici.store'), ['name' => 'X', 'email' => 'x@example.com', 'password' => '12345678'])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_admin_dodaje_korisnika(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('korisnici.index'))->assertOk()->assertSee($admin->email);

        $drugi = User::factory()->create();
        Postavka::factory()->for($drugi)->create();
        Obracun::factory()->for($drugi)->count(2)->create();
        $this->get(route('korisnici.index'))
            ->assertViewHas('korisnici', fn ($korisnici): bool => $korisnici->firstWhere('id', $drugi->id)->obracuni_count === 2
                && $korisnici->firstWhere('id', $drugi->id)->postavke_count === 1);

        $this->post(route('korisnici.store'), [
            'name' => 'Ana Anić',
            'email' => 'ana@example.com',
            'password' => 'pocetna-lozinka',
            'is_admin' => '0',
        ])->assertRedirect(route('korisnici.index'))->assertSessionHasNoErrors();

        $ana = User::query()->where('email', 'ana@example.com')->firstOrFail();

        $this->assertFalse($ana->is_admin);
        $this->assertTrue(Hash::check('pocetna-lozinka', $ana->password));
        $this->get(route('korisnici.index'))->assertSee('čeka prvu prijavu');
    }

    public function test_admin_ne_moze_obrisati_ni_razvlastiti_sebe(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('korisnici.destroy', $admin))->assertStatus(422);
        $this->put(route('korisnici.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'is_admin' => '0']);

        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_novi_korisnik_mora_prvo_unijeti_konstante(): void
    {
        $novi = User::factory()->create();

        $this->actingAs($novi)->get(route('obracuni.index'))->assertRedirect(route('postavke.create'));
        $this->get(route('simulator'))->assertRedirect(route('postavke.create'));
        $this->get(route('postavke.create'))->assertOk()->assertSee('Vaše konstante')->assertSee('value="15"', false);

        $this->post(route('postavke.store'), [
            'vrijedi_od' => '2026-01',
            'osnovica' => '399,51',
            'koeficijent' => '3,2',
            'minuli_rad' => '5',
            'fond_sati' => '174',
            'stalni_dodatak' => '0',
            'topli_obrok' => '66,36',
            'stopa_mio1' => '15',
            'stopa_mio2' => '5',
            'osobni_odbitak' => '600',
            'broj_djece' => '2',
            'udio_djeca' => '100',
            'broj_uzdrzavanih' => '0',
            'broj_invalidnosti' => '0',
            'stopa_poreza' => '20',
            'sindikat_spiv' => '0',
            'spiv_hto' => '0',
            'bolovanje_postotak' => '70',
        ])->assertRedirect(route('obracuni.create'))->assertSessionHasNoErrors();

        $this->assertSame($novi->id, Postavka::query()->firstOrFail()->user_id);
        $this->get(route('obracuni.index'))->assertOk();
    }

    public function test_korisnik_vidi_samo_svoje_podatke(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DatabaseSeeder::class);
        $adminovObracun = $admin->obracuni()->orderByDesc('period')->firstOrFail();
        $adminovePostavke = $admin->postavke()->firstOrFail();

        $drugi = User::factory()->create();
        Postavka::factory()->for($drugi)->create();
        $drugiObracun = Obracun::factory()->for($drugi)->create(['period' => '2026-09-01']);

        $this->actingAs($drugi);

        $this->get(route('obracuni.index'))->assertOk()->assertDontSee('1.679,99');
        $this->get(route('obracuni.show', $adminovObracun))->assertNotFound();
        $this->get(route('obracuni.edit', $adminovObracun))->assertNotFound();
        $this->delete(route('obracuni.destroy', $adminovObracun))->assertNotFound();
        $this->get(route('postavke.edit', $adminovePostavke))->assertNotFound();
        $this->get(route('simulator', ['obracun' => $adminovObracun->id]))->assertOk()->assertViewHas('odabrani', fn (Obracun $o): bool => $o->is($drugiObracun));

        $this->assertSame(22, Obracun::withoutGlobalScopes()->count());
        $this->assertSame(1, Obracun::query()->count());
    }

    public function test_isti_mjesec_mogu_imati_razliciti_korisnici(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DatabaseSeeder::class);
        $drugi = User::factory()->create();
        Postavka::factory()->for($drugi)->create();

        $this->actingAs($drugi)->post(route('obracuni.store'), [
            'period' => '2026-09',
            'sati_redovan' => '176',
            'sati_blagdan' => '0',
            'sati_dopust' => '0',
            'sati_godisnji' => '0',
            'sati_bolovanje' => '0',
            'ucinak' => '20',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Obracun::withoutGlobalScopes()->whereBelongsTo($drugi)->count());
        $this->assertSame(21, Obracun::withoutGlobalScopes()->whereBelongsTo($admin)->count());
    }

    public function test_promjena_lozinke(): void
    {
        $korisnik = User::factory()->create(['password' => 'stara-lozinka']);

        $this->actingAs($korisnik)->put(route('profil.update'), [
            'trenutna_lozinka' => 'kriva',
            'password' => 'nova-lozinka',
            'password_confirmation' => 'nova-lozinka',
        ])->assertSessionHasErrors('trenutna_lozinka');

        $this->put(route('profil.update'), [
            'trenutna_lozinka' => 'stara-lozinka',
            'password' => 'nova-lozinka',
            'password_confirmation' => 'nova-lozinka',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nova-lozinka', $korisnik->fresh()->password));
    }

    public function test_naredba_plata_admin_preuzima_zapise_bez_vlasnika(): void
    {
        $privremeni = User::factory()->admin()->create();
        $this->seed(DatabaseSeeder::class);
        Obracun::query()->update(['user_id' => null]);
        Postavka::query()->update(['user_id' => null]);
        $privremeni->delete();

        $this->artisan('plata:admin', ['email' => 'igor@example.com', '--name' => 'Igor'])
            ->expectsQuestion('Lozinka (barem 8 znakova)', 'admin-lozinka')
            ->expectsQuestion('Ponovi lozinku', 'admin-lozinka')
            ->expectsOutputToContain('Dodijeljeno: 21 obračuna i 4 zapisa konstanti.')
            ->assertSuccessful();

        $igor = User::query()->where('email', 'igor@example.com')->firstOrFail();

        $this->assertTrue($igor->is_admin);
        $this->assertSame(21, $igor->obracuni()->count());
        $this->assertSame(4, $igor->postavke()->count());
    }
}
