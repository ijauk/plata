<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Dvofaktorska;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Drugi korak prijave: nakon točne lozinke traži se kod iz aplikacije ili kod za oporavak.
 */
class DvofaktorskaPrijavaController extends Controller
{
    public const KLJUC_SESIJE = 'prijava.dvofaktorska';

    public function create(Request $request): View|RedirectResponse
    {
        if ($this->korisnikNaCekanju($request) === null) {
            return redirect()->route('login');
        }

        return view('auth.kod');
    }

    public function store(Request $request, Dvofaktorska $dvofaktorska): RedirectResponse
    {
        $korisnik = $this->korisnikNaCekanju($request);

        if ($korisnik === null) {
            return redirect()->route('login')->withErrors(['email' => 'Prijava je istekla, pokušaj ponovno.']);
        }

        $kod = (string) $request->validate(['kod' => ['required', 'string', 'max:20']], ['kod.required' => 'Upiši kod.'])['kod'];

        if (! $dvofaktorska->provjeriKod($korisnik, $kod) && ! $dvofaktorska->iskoristiKodZaOporavak($korisnik, $kod)) {
            throw ValidationException::withMessages(['kod' => 'Kod nije točan.']);
        }

        $zapamti = (bool) $request->session()->pull(self::KLJUC_SESIJE)['zapamti'];

        Auth::login($korisnik, $zapamti);
        $request->session()->regenerate();

        return redirect()->intended(route('obracuni.index'));
    }

    private function korisnikNaCekanju(Request $request): ?User
    {
        $naCekanju = $request->session()->get(self::KLJUC_SESIJE);

        if (! is_array($naCekanju) || $naCekanju['istjece'] < now()->getTimestamp()) {
            $request->session()->forget(self::KLJUC_SESIJE);

            return null;
        }

        return User::query()->find($naCekanju['id']);
    }
}
