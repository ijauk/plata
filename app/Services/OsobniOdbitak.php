<?php

namespace App\Services;

use App\Support\Decimalni;
use Carbon\CarbonInterface;

/**
 * Faktor osobnog odbitka prema tablici koeficijenata (osnovni odbitak × zbroj koeficijenata).
 *
 * Koeficijenti djece se zbrajaju: dvoje djece = 0,5 + 0,7. Kad se zakon promijeni,
 * dodaj novu verziju s datumom od kada vrijedi – stari obračuni zadržavaju staru tablicu.
 */
class OsobniOdbitak
{
    public const NAJVISE_DJECE = 10;

    /**
     * @var array<string, array{djeca: list<float>, uzdrzavani: float, invalidnost: float}>
     */
    public const TABLICE = [
        '2025-01-01' => [
            'djeca' => [0.5, 0.7, 1.0, 1.4, 1.9, 2.5, 3.2, 4.0, 4.9, 6.0],
            'uzdrzavani' => 0.5,
            'invalidnost' => 0.3,
        ],
    ];

    private const REDNI = ['Prvo', 'Drugo', 'Treće', 'Četvrto', 'Peto', 'Šesto', 'Sedmo', 'Osmo', 'Deveto', 'Deseto'];

    /**
     * Tablica koja vrijedi za zadani mjesec (za mjesece prije prve tablice koristi se prva).
     *
     * @return array{djeca: list<float>, uzdrzavani: float, invalidnost: float}
     */
    public static function tablica(CarbonInterface $period): array
    {
        $vazeca = array_values(self::TABLICE)[0];

        foreach (self::TABLICE as $vrijediOd => $tablica) {
            if ($vrijediOd <= $period->toDateString()) {
                $vazeca = $tablica;
            }
        }

        return $vazeca;
    }

    /**
     * Pojedine stavke faktora, npr. [['Osnovni osobni odbitak', 1.0], ['Prvo dijete', 0.5], …].
     *
     * @param  array{broj_djece: float, udio_djeca: float, broj_uzdrzavanih: float, broj_invalidnosti: float}  $parametri
     * @return list<array{naziv: string, koeficijent: float}>
     */
    public static function stavke(array $parametri, CarbonInterface $period): array
    {
        $tablica = self::tablica($period);
        $udio = $parametri['udio_djeca'] / 100;
        $stavke = [['naziv' => 'Osnovni osobni odbitak', 'koeficijent' => 1.0]];

        $brojDjece = min(self::NAJVISE_DJECE, max(0, (int) $parametri['broj_djece']));
        for ($dijete = 0; $dijete < $brojDjece; $dijete++) {
            $stavke[] = [
                'naziv' => self::REDNI[$dijete].' dijete'.($udio < 1 ? ' – udio '.Decimalni::zaUnos($parametri['udio_djeca'], 2).' %' : ''),
                'koeficijent' => round($tablica['djeca'][$dijete] * $udio, 4),
            ];
        }

        $uzdrzavani = max(0, (int) $parametri['broj_uzdrzavanih']);
        if ($uzdrzavani > 0) {
            $stavke[] = ['naziv' => "Uzdržavani članovi / alimentacija × {$uzdrzavani}", 'koeficijent' => $uzdrzavani * $tablica['uzdrzavani']];
        }

        $invalidnost = max(0, (int) $parametri['broj_invalidnosti']);
        if ($invalidnost > 0) {
            $stavke[] = ['naziv' => "Invalidnost (manja od 100 %) × {$invalidnost}", 'koeficijent' => $invalidnost * $tablica['invalidnost']];
        }

        return $stavke;
    }

    /**
     * @param  array{broj_djece: float, udio_djeca: float, broj_uzdrzavanih: float, broj_invalidnosti: float}  $parametri
     */
    public static function faktor(array $parametri, CarbonInterface $period): float
    {
        return round(array_sum(array_column(self::stavke($parametri, $period), 'koeficijent')), 4);
    }
}
