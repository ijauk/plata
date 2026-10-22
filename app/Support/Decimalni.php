<?php

namespace App\Support;

/**
 * Decimalni brojevi s hrvatskim zarezom: "1.234,56" ili "399,51" pri unosu, "399,51" pri prikazu u formi.
 */
class Decimalni
{
    /**
     * Pretvara upisani tekst u oblik koji PHP razumije ("399,51" → "399.51").
     * Ako tekst sadrži zarez, točke se smatraju razdjelnikom tisućica.
     */
    public static function procitaj(mixed $vrijednost): mixed
    {
        if (! is_string($vrijednost)) {
            return $vrijednost;
        }

        $vrijednost = str_replace([' ', "\u{00A0}"], '', trim($vrijednost));

        if (str_contains($vrijednost, ',')) {
            $vrijednost = str_replace(['.', ','], ['', '.'], $vrijednost);
        }

        return $vrijednost;
    }

    /**
     * Broj za polje forme: zarez kao decimalna oznaka, bez razdjelnika tisućica i suvišnih nula.
     */
    public static function zaUnos(mixed $vrijednost, int $decimala = 4): string
    {
        if ($vrijednost === null || $vrijednost === '') {
            return '';
        }

        if (! is_numeric($vrijednost)) {
            return (string) $vrijednost;
        }

        $tekst = number_format((float) $vrijednost, $decimala, ',', '');

        return str_contains($tekst, ',') ? rtrim(rtrim($tekst, '0'), ',') : $tekst;
    }
}
