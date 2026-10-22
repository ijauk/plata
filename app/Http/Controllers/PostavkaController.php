<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostavkaRequest;
use App\Models\Postavka;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PostavkaController extends Controller
{
    public function index(): View
    {
        return view('postavke.index', ['postavke' => Postavka::query()->orderByDesc('vrijedi_od')->get()]);
    }

    public function create(): View
    {
        $zadnja = Postavka::query()->orderByDesc('vrijedi_od')->first();

        $postavka = $zadnja ? $zadnja->replicate(['napomena']) : new Postavka(Postavka::ZAKONSKE_ZADANE);
        $postavka->vrijedi_od = $zadnja ? Carbon::now()->startOfMonth() : Carbon::now()->startOfYear();

        return view('postavke.create', ['postavka' => $postavka, 'prviUnos' => $zadnja === null]);
    }

    public function store(PostavkaRequest $request): RedirectResponse
    {
        $prviUnos = ! $request->user()->imaKonstante();
        $postavka = Postavka::query()->create($request->validated());

        if ($prviUnos) {
            return redirect()->route('obracuni.create')->with('status', 'Konstante su spremljene. Sada možete napraviti prvi obračun.');
        }

        return redirect()->route('postavke.index')->with('status', 'Konstante od '.$postavka->vrijedi_od->format('n/Y').' su spremljene.');
    }

    public function edit(Postavka $postavka): View
    {
        return view('postavke.edit', ['postavka' => $postavka]);
    }

    public function update(PostavkaRequest $request, Postavka $postavka): RedirectResponse
    {
        $postavka->update($request->validated());

        return redirect()->route('postavke.index')->with('status', 'Konstante od '.$postavka->vrijedi_od->format('n/Y').' su ažurirane.');
    }

    public function destroy(Postavka $postavka): RedirectResponse
    {
        $postavka->delete();

        return redirect()->route('postavke.index')->with('status', 'Zapis konstanti je obrisan.');
    }
}
