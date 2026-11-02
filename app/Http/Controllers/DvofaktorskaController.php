<?php

namespace App\Http\Controllers;

use App\Services\Dvofaktorska;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Uključivanje i isključivanje dvofaktorske prijave na profilu.
 *
 * Uključuje se u dva koraka: prvo se napravi tajni ključ (QR kod), a tek kad korisnik
 * upiše ispravan kod iz aplikacije, prijava ga počinje tražiti.
 */
class DvofaktorskaController extends Controller
{
    public function store(Request $request, Dvofaktorska $dvofaktorska): RedirectResponse
    {
        if ($request->user()->imaDvofaktorsku()) {
            return redirect()->route('profil.edit');
        }

        $request->user()->forceFill([
            'two_factor_secret' => $dvofaktorska->noviTajniKljuc(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestamp' => null,
        ])->save();

        return redirect()->route('profil.edit');
    }

    public function update(Request $request, Dvofaktorska $dvofaktorska): RedirectResponse
    {
        $korisnik = $request->user();
        $kod = (string) $request->validate(['kod' => ['required', 'string']], ['kod.required' => 'Upiši kod iz aplikacije.'])['kod'];

        if ($korisnik->imaDvofaktorsku() || ! $dvofaktorska->provjeriKod($korisnik, $kod)) {
            throw ValidationException::withMessages(['kod' => 'Kod nije točan. Provjeri je li sat na mobitelu točan.']);
        }

        $kodovi = $dvofaktorska->noviKodoviZaOporavak();
        $korisnik->forceFill(['two_factor_recovery_codes' => $kodovi, 'two_factor_confirmed_at' => now()])->save();

        return redirect()->route('profil.edit')
            ->with('status', 'Dvofaktorska prijava je uključena.')
            ->with('kodovi_za_oporavak', $kodovi);
    }

    /**
     * Novi kodovi za oporavak; stari prestaju vrijediti.
     */
    public function kodovi(Request $request, Dvofaktorska $dvofaktorska): RedirectResponse
    {
        $this->potvrdiLozinku($request);

        $kodovi = $dvofaktorska->noviKodoviZaOporavak();
        $request->user()->forceFill(['two_factor_recovery_codes' => $kodovi])->save();

        return redirect()->route('profil.edit')
            ->with('status', 'Napravljeni su novi kodovi za oporavak.')
            ->with('kodovi_za_oporavak', $kodovi);
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()->imaDvofaktorsku()) {
            $this->potvrdiLozinku($request);
        }

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestamp' => null,
        ])->save();

        return redirect()->route('profil.edit')->with('status', 'Dvofaktorska prijava je isključena.');
    }

    private function potvrdiLozinku(Request $request): void
    {
        $request->validate([
            'lozinka' => ['required', 'current_password'],
        ], [
            'lozinka.required' => 'Upiši lozinku.',
            'lozinka.current_password' => 'Lozinka nije točna.',
        ]);
    }
}
