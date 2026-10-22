<?php

namespace App\Http\Controllers;

use App\Models\Obracun;
use App\Models\Postavka;
use App\Services\KalkulatorPlace;
use App\Services\OsobniOdbitak;
use App\Support\Decimalni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimulatorController extends Controller
{
    /**
     * "Što ako" – isti mjesec obračunat s izmijenjenim konstantama (i po želji satima),
     * uz procjenu učinka na zadnjih 12 obračuna.
     */
    public function index(Request $request, KalkulatorPlace $kalkulator): View|RedirectResponse
    {
        $obracuni = Obracun::povezi(Obracun::query()->orderBy('period')->get());

        if ($obracuni->isEmpty()) {
            return redirect()->route('obracuni.create')->with('status', 'Za simulaciju treba barem jedan obračun.');
        }

        $odabrani = $obracuni->firstWhere('id', (int) $request->query('obracun')) ?? $obracuni->last();

        $trenutniParametri = $odabrani->postavka()->parametri($odabrani->period);
        $trenutniUnos = $odabrani->unos();

        $izmjeneParametara = $this->izmjene($request->query('p', []), array_diff_key($trenutniParametri, ['faktor_odbitka' => true]));
        $izmjeneUnosa = $this->izmjene($request->query('u', []), array_diff_key($trenutniUnos, ['satnica_prethodna' => true]));

        $simuliraniParametri = Postavka::sFaktoromOdbitka([...$trenutniParametri, ...$izmjeneParametara], $odabrani->period);
        $simuliraniUnos = [...$trenutniUnos, ...$izmjeneUnosa];

        $trenutno = $kalkulator->izracunaj($trenutniParametri, $trenutniUnos);
        $simulacija = $kalkulator->izracunaj($simuliraniParametri, $simuliraniUnos);

        $poUcinku = $obracuni->pluck('ucinak')->push($simuliraniUnos['ucinak'])->unique()->sort()->values()
            ->map(fn (float $ucinak): array => [
                'ucinak' => $ucinak,
                'isplata' => $kalkulator->izracunaj($simuliraniParametri, [...$simuliraniUnos, 'ucinak' => $ucinak])['isplata'],
            ])->all();

        $godisnje = $izmjeneParametara === [] ? [] : $obracuni->slice(-12)->map(function (Obracun $obracun) use ($kalkulator, $izmjeneParametara): array {
            $parametri = Postavka::sFaktoromOdbitka([...$obracun->postavka()->parametri($obracun->period), ...$izmjeneParametara], $obracun->period);
            $sada = $obracun->izracun()['isplata'];
            $sim = $kalkulator->izracunaj($parametri, $obracun->unos())['isplata'];

            return ['obracun' => $obracun, 'sada' => $sada, 'simulacija' => $sim, 'razlika' => round($sim - $sada, 2)];
        })->values()->all();

        return view('simulator.index', [
            'obracuni' => $obracuni->sortByDesc('period'),
            'odabrani' => $odabrani,
            'trenutniParametri' => $trenutniParametri,
            'simuliraniParametri' => $simuliraniParametri,
            'trenutniUnos' => $trenutniUnos,
            'simuliraniUnos' => $simuliraniUnos,
            'izmjeneParametara' => $izmjeneParametara,
            'izmjeneUnosa' => $izmjeneUnosa,
            'trenutno' => $trenutno,
            'simulacija' => $simulacija,
            'godisnje' => $godisnje,
            'poUcinku' => $poUcinku,
            'oznakeParametara' => Postavka::PARAMETRI,
            'odbitakSada' => OsobniOdbitak::stavke($trenutniParametri, $odabrani->period),
            'odbitakSimulacija' => OsobniOdbitak::stavke($simuliraniParametri, $odabrani->period),
        ]);
    }

    /**
     * Vrijednosti iz forme koje su brojevi i razlikuju se od trenutnih.
     *
     * @param  array<string, float|null>  $trenutno
     * @return array<string, float>
     */
    private function izmjene(mixed $upisano, array $trenutno): array
    {
        if (! is_array($upisano)) {
            return [];
        }

        $izmjene = [];
        foreach ($trenutno as $kljuc => $vrijednost) {
            $novo = Decimalni::procitaj((string) ($upisano[$kljuc] ?? ''));
            if (is_numeric($novo) && (float) $novo >= 0 && abs((float) $novo - (float) $vrijednost) > 1e-9) {
                $izmjene[$kljuc] = (float) $novo;
            }
        }

        if (($izmjene['fond_sati'] ?? null) === 0.0) {
            unset($izmjene['fond_sati']);
        }

        return $izmjene;
    }
}
