<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Dvofaktorska;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class DvofaktorskaPrijavaTest extends TestCase
{
    use RefreshDatabase;

    private function kod(User $korisnik): string
    {
        return app(Google2FA::class)->getCurrentOtp($korisnik->two_factor_secret);
    }

    public function test_korisnik_ukljucuje_dvofaktorsku(): void
    {
        $korisnik = User::factory()->create();
        $this->actingAs($korisnik);

        $this->post(route('dvofaktorska.store'))->assertRedirect(route('profil.edit'));
        $korisnik->refresh();

        $this->assertNotNull($korisnik->two_factor_secret);
        $this->assertFalse($korisnik->imaDvofaktorsku());
        $this->get(route('profil.edit'))->assertOk()->assertSee('<svg', false)->assertSee($korisnik->two_factor_secret);

        $this->put(route('dvofaktorska.update'), ['kod' => '000000'])->assertSessionHasErrors('kod');
        $this->assertFalse($korisnik->refresh()->imaDvofaktorsku());

        $this->put(route('dvofaktorska.update'), ['kod' => $this->kod($korisnik)])
            ->assertRedirect(route('profil.edit'))
            ->assertSessionHas('kodovi_za_oporavak', fn (array $kodovi): bool => count($kodovi) === Dvofaktorska::BROJ_KODOVA_ZA_OPORAVAK);

        $this->assertTrue($korisnik->refresh()->imaDvofaktorsku());
        $this->get(route('profil.edit'))->assertSee('Preostalo kodova za oporavak: 8');
    }

    public function test_prijava_trazi_kod_iz_aplikacije(): void
    {
        $korisnik = User::factory()->dvofaktorska()->create(['password' => 'tajna-lozinka']);

        $this->get(route('prijava.kod'))->assertRedirect(route('login'));

        $this->post(route('login'), ['email' => $korisnik->email, 'password' => 'tajna-lozinka'])->assertRedirect(route('prijava.kod'));
        $this->assertGuest();
        $this->get(route('prijava.kod'))->assertOk()->assertSee('Kod za prijavu');

        $this->post(route('prijava.kod'), ['kod' => '000000'])->assertSessionHasErrors('kod');
        $this->assertGuest();

        $this->post(route('prijava.kod'), ['kod' => $this->kod($korisnik)])->assertRedirect(route('obracuni.index'));
        $this->assertAuthenticatedAs($korisnik);
    }

    public function test_isti_kod_ne_prolazi_dvaput(): void
    {
        $korisnik = User::factory()->dvofaktorska()->create();
        $dvofaktorska = app(Dvofaktorska::class);
        $kod = $this->kod($korisnik);

        $this->assertTrue($dvofaktorska->provjeriKod($korisnik, $kod));
        $this->assertFalse($dvofaktorska->provjeriKod($korisnik->refresh(), $kod));
    }

    public function test_kod_za_oporavak_vrijedi_jednom(): void
    {
        $korisnik = User::factory()->dvofaktorska()->create(['password' => 'tajna-lozinka']);

        $this->post(route('login'), ['email' => $korisnik->email, 'password' => 'tajna-lozinka']);
        $this->post(route('prijava.kod'), ['kod' => 'AAAAA-BBBBB'])->assertRedirect(route('obracuni.index'));
        $this->assertAuthenticatedAs($korisnik);
        $this->assertSame(['ccccc-ddddd'], $korisnik->refresh()->two_factor_recovery_codes);

        $this->post(route('odjava'));
        $this->post(route('login'), ['email' => $korisnik->email, 'password' => 'tajna-lozinka']);
        $this->post(route('prijava.kod'), ['kod' => 'aaaaa-bbbbb'])->assertSessionHasErrors('kod');
        $this->assertGuest();
    }

    public function test_korak_s_kodom_istjece(): void
    {
        $korisnik = User::factory()->dvofaktorska()->create(['password' => 'tajna-lozinka']);

        $this->post(route('login'), ['email' => $korisnik->email, 'password' => 'tajna-lozinka']);
        $this->travel(6)->minutes();

        $this->post(route('prijava.kod'), ['kod' => $this->kod($korisnik)])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_iskljucivanje_trazi_lozinku(): void
    {
        $korisnik = User::factory()->dvofaktorska()->create(['password' => 'tajna-lozinka']);
        $this->actingAs($korisnik);

        $this->post(route('dvofaktorska.store'))->assertRedirect(route('profil.edit'));
        $this->delete(route('dvofaktorska.destroy'), ['lozinka' => 'kriva'])->assertSessionHasErrors('lozinka');
        $this->assertTrue($korisnik->refresh()->imaDvofaktorsku());

        $this->delete(route('dvofaktorska.destroy'), ['lozinka' => 'tajna-lozinka'])->assertSessionHasNoErrors();
        $this->assertFalse($korisnik->refresh()->imaDvofaktorsku());
        $this->assertNull($korisnik->two_factor_secret);
    }

    public function test_admin_iskljucuje_dvofaktorsku_korisniku(): void
    {
        $korisnik = User::factory()->dvofaktorska()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('korisnici.edit', $korisnik))->assertSee('iskljuci_dvofaktorsku');

        $this->put(route('korisnici.update', $korisnik), [
            'name' => $korisnik->name,
            'email' => $korisnik->email,
            'is_admin' => '0',
            'iskljuci_dvofaktorsku' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($korisnik->refresh()->imaDvofaktorsku());
    }
}
