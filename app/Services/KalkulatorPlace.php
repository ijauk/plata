<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Obračun plaće prema pravilima izvedenim iz platnih lista Hrvatskih voda (2025.–2026.).
 *
 * Svaka stavka zaokružuje se na cent (half-up), stimulacija se računa iz nezaokruženog
 * iznosa redovnog rada, a satnica bolovanja zaokružuje se na cent prije množenja sa satima.
 */
class KalkulatorPlace
{
    /**
     * @param  array{osnovica: float, koeficijent: float, minuli_rad: float, fond_sati: float, stalni_dodatak: float, topli_obrok: float, stopa_mio1: float, stopa_mio2: float, osobni_odbitak: float, faktor_odbitka: float, stopa_poreza: float, sindikat_spiv: float, spiv_hto: float, bolovanje_postotak: float}  $parametri
     * @param  array{sati_redovan: float, sati_blagdan: float, sati_dopust: float, sati_godisnji: float, sati_bolovanje: float, ucinak: float, ostalo_bruto: float, ostalo_sati: float, satnica_prethodna: ?float}  $unos
     * @return array{satnica: float, satnica_prethodna: ?float, satnica_bolovanja: ?float, stavke: list<array{sifra: string, naziv: string, sati: ?float, iznos: float}>, ukupno_sati: float, bruto: float, mio1: float, mio2: float, dohodak: float, faktor_odbitka: float, osobni_odbitak: float, neiskoristeni_odbitak: float, porezna_osnovica: float, porez: float, neto: float, topli_obrok: float, sindikat_spiv: float, spiv_hto: float, isplata: float}
     */
    public function izracunaj(array $parametri, array $unos): array
    {
        $satnica = $parametri['osnovica'] * $parametri['koeficijent'] * (1 + $parametri['minuli_rad'] / 100) / $parametri['fond_sati'];
        $satnicaPrethodna = $unos['satnica_prethodna'];

        $satiPoPrethodnoj = $unos['sati_blagdan'] + $unos['sati_dopust'] + $unos['sati_godisnji'] + $unos['sati_bolovanje'];
        if ($satiPoPrethodnoj > 0 && $satnicaPrethodna === null) {
            throw new InvalidArgumentException('Za blagdane, dopust, godišnji i bolovanje potrebna je satnica prethodnog mjeseca.');
        }

        $satnicaBolovanja = $satnicaPrethodna === null
            ? null
            : $this->zaokruzi($satnicaPrethodna * $parametri['bolovanje_postotak'] / 100);

        $redovanRad = $unos['sati_redovan'] * $satnica;

        $stavke = array_values(array_filter([
            $this->stavka('001', 'Redovan rad', $unos['sati_redovan'], $redovanRad),
            $this->stavka('008', 'Blagdani', $unos['sati_blagdan'], $unos['sati_blagdan'] * (float) $satnicaPrethodna),
            $this->stavka('013', 'Plaćeni dopust', $unos['sati_dopust'], $unos['sati_dopust'] * (float) $satnicaPrethodna),
            $this->stavka('068', 'Godišnji odmor', $unos['sati_godisnji'], $unos['sati_godisnji'] * (float) $satnicaPrethodna),
            $this->stavka('020', 'Bolovanje na teret poslodavca', $unos['sati_bolovanje'], $unos['sati_bolovanje'] * (float) $satnicaBolovanja),
            $this->stavka('—', 'Ostalo (prekovremeni i sl.)', $unos['ostalo_sati'], $unos['ostalo_bruto']),
            $this->stavka('030', 'Stalni dodatak', null, $parametri['stalni_dodatak']),
            $this->stavka('301', 'Stimulacija ('.$this->postotak($unos['ucinak']).')', null, $redovanRad * $unos['ucinak'] / 100),
        ], fn (array $stavka): bool => $stavka['iznos'] != 0 || $stavka['sifra'] === '001'));

        $bruto = $this->zaokruzi(array_sum(array_column($stavke, 'iznos')));
        $mio1 = $this->zaokruzi($bruto * $parametri['stopa_mio1'] / 100);
        $mio2 = $this->zaokruzi($bruto * $parametri['stopa_mio2'] / 100);
        $dohodak = $this->zaokruzi($bruto - $mio1 - $mio2);
        $osobniOdbitak = $this->zaokruzi($parametri['osobni_odbitak'] * $parametri['faktor_odbitka']);
        $poreznaOsnovica = max(0.0, $this->zaokruzi($dohodak - $osobniOdbitak));
        $porez = $this->zaokruzi($poreznaOsnovica * $parametri['stopa_poreza'] / 100);
        $neto = $this->zaokruzi($dohodak - $porez);

        $satiRada = $unos['sati_redovan'] + $unos['sati_blagdan'];
        $fondMjeseca = $satiRada + $unos['sati_dopust'] + $unos['sati_godisnji'] + $unos['sati_bolovanje'];
        $topliObrok = $fondMjeseca > 0 ? $this->zaokruzi($parametri['topli_obrok'] * $satiRada / $fondMjeseca) : 0.0;

        $sindikatSpiv = $this->zaokruzi($bruto * $parametri['sindikat_spiv'] / 100);
        $spivHto = $this->zaokruzi($bruto * $parametri['spiv_hto'] / 100);

        return [
            'satnica' => $satnica,
            'satnica_prethodna' => $satnicaPrethodna,
            'satnica_bolovanja' => $satnicaBolovanja,
            'stavke' => $stavke,
            'ukupno_sati' => $fondMjeseca + $unos['ostalo_sati'],
            'bruto' => $bruto,
            'mio1' => $mio1,
            'mio2' => $mio2,
            'dohodak' => $dohodak,
            'faktor_odbitka' => $parametri['faktor_odbitka'],
            'osobni_odbitak' => $osobniOdbitak,
            'neiskoristeni_odbitak' => max(0.0, $this->zaokruzi($osobniOdbitak - $dohodak)),
            'porezna_osnovica' => $poreznaOsnovica,
            'porez' => $porez,
            'neto' => $neto,
            'topli_obrok' => $topliObrok,
            'sindikat_spiv' => $sindikatSpiv,
            'spiv_hto' => $spivHto,
            'isplata' => $this->zaokruzi($neto + $topliObrok - $sindikatSpiv - $spivHto),
        ];
    }

    /**
     * @return array{sifra: string, naziv: string, sati: ?float, iznos: float}
     */
    private function stavka(string $sifra, string $naziv, ?float $sati, float $iznos): array
    {
        return ['sifra' => $sifra, 'naziv' => $naziv, 'sati' => $sati, 'iznos' => $this->zaokruzi($iznos)];
    }

    private function zaokruzi(float $iznos): float
    {
        return round($iznos, 2, PHP_ROUND_HALF_UP);
    }

    private function postotak(float $vrijednost): string
    {
        return rtrim(rtrim(number_format($vrijednost, 2, ',', ''), '0'), ',').' %';
    }
}
