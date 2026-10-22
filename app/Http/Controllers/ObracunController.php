<?php

namespace App\Http\Controllers;

use App\Http\Requests\ObracunRequest;
use App\Models\Obracun;
use App\Services\OsobniOdbitak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ObracunController extends Controller
{
    public function index(): View
    {
        $obracuni = Obracun::povezi(Obracun::query()->get())->sortByDesc('period');

        return view('obracuni.index', ['obracuni' => $obracuni]);
    }

    public function create(): View
    {
        $zadnji = Obracun::query()->orderByDesc('period')->first();
        $period = $zadnji ? $zadnji->period->copy()->addMonthNoOverflow() : Carbon::now()->startOfMonth();

        $obracun = new Obracun([
            'period' => $period,
            'sati_redovan' => Obracun::fondSatiMjeseca($period),
            'ucinak' => $zadnji->ucinak ?? 20,
        ]);

        return view('obracuni.create', ['obracun' => $obracun]);
    }

    public function store(ObracunRequest $request): RedirectResponse
    {
        $obracun = Obracun::query()->create($request->podaci());

        return redirect()->route('obracuni.show', $obracun)->with('status', "Obračun za {$obracun->oznaka} je spremljen.");
    }

    public function show(Obracun $obracun): View
    {
        $postavka = $obracun->postavka();

        return view('obracuni.show', [
            'obracun' => $obracun,
            'postavka' => $postavka,
            'rezultat' => $obracun->izracun(),
            'odbitak' => OsobniOdbitak::stavke($postavka->parametri($obracun->period), $obracun->period),
        ]);
    }

    public function edit(Obracun $obracun): View
    {
        return view('obracuni.edit', ['obracun' => $obracun]);
    }

    public function update(ObracunRequest $request, Obracun $obracun): RedirectResponse
    {
        $obracun->update($request->podaci());

        return redirect()->route('obracuni.show', $obracun)->with('status', "Obračun za {$obracun->oznaka} je ažuriran.");
    }

    public function destroy(Obracun $obracun): RedirectResponse
    {
        $obracun->delete();

        return redirect()->route('obracuni.index')->with('status', "Obračun za {$obracun->oznaka} je obrisan.");
    }
}
