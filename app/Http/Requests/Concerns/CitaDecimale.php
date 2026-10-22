<?php

namespace App\Http\Requests\Concerns;

use App\Support\Decimalni;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Brojčana polja prihvaćaju decimalni zarez ("399,51") prije validacije.
 *
 * @mixin FormRequest
 */
trait CitaDecimale
{
    /**
     * @param  list<string>  $polja
     */
    protected function procitajDecimale(array $polja): void
    {
        $this->merge(collect($polja)
            ->filter(fn (string $polje): bool => $this->has($polje))
            ->mapWithKeys(fn (string $polje): array => [$polje => Decimalni::procitaj($this->input($polje))])
            ->all());
    }

    /**
     * Mjesec iz forme dolazi kao "YYYY-MM"; spremamo prvi dan mjeseca.
     */
    protected function procitajMjesec(string $polje): void
    {
        if (is_string($this->input($polje)) && preg_match('/^\d{4}-\d{2}$/', $this->input($polje))) {
            $this->merge([$polje => $this->input($polje).'-01']);
        }
    }
}
