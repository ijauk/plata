<?php

namespace App\Http\Controllers;

use App\Services\Dvofaktorska;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(Request $request, Dvofaktorska $dvofaktorska): View
    {
        $korisnik = $request->user();
        $uPostavljanju = $korisnik->two_factor_secret !== null && ! $korisnik->imaDvofaktorsku();

        return view('profil.edit', [
            'qrKod' => $uPostavljanju ? $dvofaktorska->qrKod($korisnik) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $podaci = $request->validate([
            'trenutna_lozinka' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'trenutna_lozinka.required' => 'Upiši trenutnu lozinku.',
            'trenutna_lozinka.current_password' => 'Trenutna lozinka nije točna.',
            'password.required' => 'Upiši novu lozinku.',
            'password.confirmed' => 'Nova lozinka i potvrda se ne podudaraju.',
            'password.min' => 'Nova lozinka mora imati barem 8 znakova.',
        ]);

        $request->user()->update(['password' => $podaci['password']]);

        return redirect()->route('profil.edit')->with('status', 'Lozinka je promijenjena.');
    }
}
