<?php

namespace App\Models;

use App\Models\Concerns\PripadaKorisniku;
use App\Services\KalkulatorPlace;
use Carbon\CarbonInterface;
use Database\Factories\ObracunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * @property CarbonInterface $period
 */
#[Table('obracuni')]
#[Fillable([
    'period', 'sati_redovan', 'sati_blagdan', 'sati_dopust', 'sati_godisnji', 'sati_bolovanje', 'ucinak',
    'ostalo_bruto', 'ostalo_sati', 'prethodni_bruto', 'prethodni_sati', 'stvarni_bruto', 'stvarna_isplata', 'napomena',
])]
class Obracun extends Model
{
    /** @use HasFactory<ObracunFactory> */
    use HasFactory, PripadaKorisniku;

    public const SATI = [
        'sati_redovan' => 'Redovan rad',
        'sati_blagdan' => 'Blagdani',
        'sati_dopust' => 'Plaćeni dopust',
        'sati_godisnji' => 'Godišnji odmor',
        'sati_bolovanje' => 'Bolovanje',
    ];

    /**
     * Gdje se sati pojedine vrste nalaze na platnoj listi (odjeljak VI i šifra u rekapitulaciji).
     *
     * @var array<string, string>
     */
    public const SATI_NA_LISTICU = [
        'sati_redovan' => '2.1 redoviti rad · VP 001',
        'sati_blagdan' => '3.3 blagdani i neradni dani · VP 008',
        'sati_dopust' => '3.9 plaćeni dopust · VP 013',
        'sati_godisnji' => '3.1 godišnji odmor · VP 068',
        'sati_bolovanje' => '3.2 bolovanje na teret poslodavca · VP 020',
    ];

    /**
     * Prethodni mjesec, ako je već učitan (izbjegava ponovne upite pri obračunu niza).
     */
    public ?Obracun $ucitaniPrethodni = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $rezultat = null;

    /**
     * Obračunava zadane mjesece kronološki, tako da svaki koristi već izračunati prethodni.
     *
     * @param  Collection<int, Obracun>  $obracuni
     * @return Collection<int, Obracun>
     */
    public static function povezi(Collection $obracuni): Collection
    {
        $poRedu = $obracuni->sortBy('period')->values();
        $poRedu->each(function (Obracun $obracun, int $indeks) use ($poRedu): void {
            $prethodni = $poRedu->get($indeks - 1);
            if ($prethodni && $prethodni->user_id === $obracun->user_id && $prethodni->period->isSameMonth($obracun->period->copy()->subMonthNoOverflow())) {
                $obracun->ucitaniPrethodni = $prethodni;
            }
        });

        return $obracuni;
    }

    /**
     * Fond sati mjeseca: radni dani (pon–pet, uključujući blagdane) × 8 sati.
     */
    public static function fondSatiMjeseca(CarbonInterface $period): int
    {
        $pocetak = $period->copy()->startOfMonth();

        return $pocetak->diffInWeekdays($pocetak->copy()->addMonthNoOverflow()) * 8;
    }

    public function prethodni(): ?Obracun
    {
        return $this->ucitaniPrethodni ??= static::query()
            ->where('user_id', $this->user_id)
            ->whereDate('period', $this->period->copy()->subMonthNoOverflow()->startOfMonth()->toDateString())
            ->first();
    }

    /**
     * Bruto i sati prethodnog mjeseca: ručni unos ima prednost, zatim stvarni bruto s platne liste, pa izračun.
     *
     * @return array{bruto: float, sati: float, izvor: string}|null
     */
    public function prethodniMjesec(): ?array
    {
        if ($this->prethodni_bruto !== null && $this->prethodni_sati) {
            return ['bruto' => $this->prethodni_bruto, 'sati' => $this->prethodni_sati, 'izvor' => 'ručno upisano u ovom obračunu'];
        }

        $prethodni = $this->prethodni();
        if ($prethodni === null) {
            return null;
        }

        return [
            'bruto' => $prethodni->stvarni_bruto ?? $prethodni->izracun()['bruto'],
            'sati' => $prethodni->ukupnoSati(),
            'izvor' => $prethodni->stvarni_bruto !== null
                ? "stvarni bruto s platne liste {$prethodni->oznaka}"
                : "izračunati bruto za {$prethodni->oznaka}",
        ];
    }

    public function satnicaPrethodna(): ?float
    {
        $prethodni = $this->prethodniMjesec();

        return $prethodni && $prethodni['sati'] > 0 ? $prethodni['bruto'] / $prethodni['sati'] : null;
    }

    public function ukupnoSati(): float
    {
        return (float) ($this->sati_redovan + $this->sati_blagdan + $this->sati_dopust + $this->sati_godisnji
            + $this->sati_bolovanje + $this->ostalo_sati);
    }

    public function postavka(): Postavka
    {
        return Postavka::vazeceZa($this->user_id, $this->period)
            ?? throw new RuntimeException("Nema konstanti koje vrijede za {$this->oznaka}.");
    }

    /**
     * @return array<string, float|null>
     */
    public function unos(): array
    {
        return [
            ...array_map(fn (string $polje): float => (float) $this->{$polje}, array_combine(array_keys(self::SATI), array_keys(self::SATI))),
            'ucinak' => (float) $this->ucinak,
            'ostalo_bruto' => (float) $this->ostalo_bruto,
            'ostalo_sati' => (float) $this->ostalo_sati,
            'satnica_prethodna' => $this->satnicaPrethodna(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function izracun(): array
    {
        return $this->rezultat ??= app(KalkulatorPlace::class)->izracunaj($this->postavka()->parametri($this->period), $this->unos());
    }

    public function razlika(): ?float
    {
        return $this->stvarna_isplata === null ? null : round($this->izracun()['isplata'] - $this->stvarna_isplata, 2);
    }

    /**
     * Npr. "9/2026".
     */
    protected function oznaka(): Attribute
    {
        return Attribute::get(fn (): string => $this->period->format('n/Y'));
    }

    /**
     * Datum isplate: prvi dan idućeg mjeseca.
     */
    protected function datumIsplate(): Attribute
    {
        return Attribute::get(fn (): CarbonInterface => $this->period->copy()->addMonthNoOverflow()->startOfMonth());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'period' => 'date',
            'sati_redovan' => 'float',
            'sati_blagdan' => 'float',
            'sati_dopust' => 'float',
            'sati_godisnji' => 'float',
            'sati_bolovanje' => 'float',
            'ucinak' => 'float',
            'ostalo_bruto' => 'float',
            'ostalo_sati' => 'float',
            'prethodni_bruto' => 'float',
            'prethodni_sati' => 'float',
            'stvarni_bruto' => 'float',
            'stvarna_isplata' => 'float',
        ];
    }
}
