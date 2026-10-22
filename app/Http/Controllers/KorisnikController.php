<?php

namespace App\Http\Controllers;

use App\Http\Requests\KorisnikRequest;
use App\Models\Scopes\VlasnikScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KorisnikController extends Controller
{
    public function index(): View
    {
        return view('korisnici.index', [
            'korisnici' => User::query()
                ->withCount([
                    'obracuni' => fn (Builder $upit) => $upit->withoutGlobalScope(VlasnikScope::class),
                    'postavke' => fn (Builder $upit) => $upit->withoutGlobalScope(VlasnikScope::class),
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('korisnici.create', ['korisnik' => new User]);
    }

    public function store(KorisnikRequest $request): RedirectResponse
    {
        $korisnik = new User;
        $korisnik->forceFill($request->podaci())->save();

        return redirect()->route('korisnici.index')
            ->with('status', "Korisnik {$korisnik->name} je dodan. Pri prvoj prijavi tražit će se unos konstanti.");
    }

    public function edit(User $korisnik): View
    {
        return view('korisnici.edit', ['korisnik' => $korisnik]);
    }

    public function update(KorisnikRequest $request, User $korisnik): RedirectResponse
    {
        $podaci = $request->podaci();

        if ($korisnik->is($request->user())) {
            $podaci['is_admin'] = true;
        }

        $korisnik->forceFill($podaci)->save();

        return redirect()->route('korisnici.index')->with('status', "Korisnik {$korisnik->name} je ažuriran.");
    }

    public function destroy(Request $request, User $korisnik): RedirectResponse
    {
        abort_if($korisnik->is($request->user()), 422, 'Ne možeš obrisati sam sebe.');

        $korisnik->delete();

        return redirect()->route('korisnici.index')->with('status', "Korisnik {$korisnik->name} i svi njegovi obračuni su obrisani.");
    }
}
