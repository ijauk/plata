<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrijavaController extends Controller
{
    public function create(): View
    {
        return view('auth.prijava');
    }

    public function store(Request $request): RedirectResponse
    {
        $podaci = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Upiši e-mail.',
            'email.email' => 'E-mail nije ispravan.',
            'password.required' => 'Upiši lozinku.',
        ]);

        $korisnik = Auth::getProvider()->retrieveByCredentials(['email' => $podaci['email']]);

        if (! $korisnik instanceof User || ! Auth::getProvider()->validateCredentials($korisnik, $podaci)) {
            throw ValidationException::withMessages(['email' => 'Pogrešan e-mail ili lozinka.']);
        }

        if ($korisnik->imaDvofaktorsku()) {
            $request->session()->put(DvofaktorskaPrijavaController::KLJUC_SESIJE, [
                'id' => $korisnik->id,
                'zapamti' => $request->boolean('zapamti'),
                'istjece' => now()->addMinutes(5)->getTimestamp(),
            ]);

            return redirect()->route('prijava.kod');
        }

        Auth::login($korisnik, $request->boolean('zapamti'));
        $request->session()->regenerate();

        return redirect()->intended(route('obracuni.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
