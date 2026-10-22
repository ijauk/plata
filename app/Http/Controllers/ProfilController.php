<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(): View
    {
        return view('profil.edit');
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
