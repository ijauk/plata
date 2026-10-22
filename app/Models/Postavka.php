<?php

namespace App\Models;

use App\Models\Concerns\PripadaKorisniku;
use App\Services\OsobniOdbitak;
use Carbon\CarbonInterface;
use Database\Factories\PostavkaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('postavke')]
#[Fillable(['vrijedi_od', ...self::PARAMETRI_KLJUCEVI, 'napomena'])]
class Postavka extends Model
{
    /** @use HasFactory<PostavkaFactory> */
    use HasFactory, PripadaKorisniku;

    /**
     * Konstante obračuna s oznakama za prikaz.
     *
     * @var array<string, string>
     */
    public const PARAMETRI = [
        'osnovica' => 'Osnovica (€)',
        'koeficijent' => 'Koeficijent',
        'minuli_rad' => 'Minuli rad (%)',
        'fond_sati' => 'Prosječni mjesečni fond sati',
        'stalni_dodatak' => 'Stalni dodatak (€)',
        'topli_obrok' => 'Topli obrok – puni iznos (€)',
        'stopa_mio1' => 'MIO I. stup (%)',
        'stopa_mio2' => 'MIO II. stup (%)',
        'osobni_odbitak' => 'Osnovni osobni odbitak (€)',
        'broj_djece' => 'Broj uzdržavane djece',
        'udio_djeca' => 'Moj udio u odbitku za djecu (%)',
        'broj_uzdrzavanih' => 'Uzdržavani članovi / alimentacija',
        'broj_invalidnosti' => 'Osobe s invaliditetom (< 100 %)',
        'stopa_poreza' => 'Porez na dohodak (%)',
        'sindikat_spiv' => 'Sindikat SPIV (% bruta)',
        'spiv_hto' => 'SPIV-HTO (% bruta)',
        'bolovanje_postotak' => 'Bolovanje (% satnice preth. mjeseca)',
    ];

    public const PARAMETRI_KLJUCEVI = [
        'osnovica', 'koeficijent', 'minuli_rad', 'fond_sati', 'stalni_dodatak', 'topli_obrok', 'stopa_mio1',
        'stopa_mio2', 'osobni_odbitak', 'broj_djece', 'udio_djeca', 'broj_uzdrzavanih', 'broj_invalidnosti', 'stopa_poreza', 'sindikat_spiv', 'spiv_hto', 'bolovanje_postotak',
    ];

    /**
     * Zakonske i uobičajene vrijednosti kojima se popunjava prvi unos novog korisnika;
     * osobne konstante (osnovica, koeficijent, minuli rad…) korisnik upisuje sam.
     *
     * @var array<string, float>
     */
    public const ZAKONSKE_ZADANE = [
        'fond_sati' => 174,
        'stopa_mio1' => 15,
        'stopa_mio2' => 5,
        'osobni_odbitak' => 600,
        'broj_djece' => 0,
        'udio_djeca' => 100,
        'broj_uzdrzavanih' => 0,
        'broj_invalidnosti' => 0,
        'stalni_dodatak' => 0,
        'sindikat_spiv' => 0,
        'spiv_hto' => 0,
        'bolovanje_postotak' => 90,
    ];

    /**
     * Korisnikove postavke koje vrijede za zadani mjesec (zadnje s datumom početka do tog mjeseca).
     */
    public static function vazeceZa(int $userId, CarbonInterface $period): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('vrijedi_od', '<=', $period->copy()->endOfMonth()->toDateString())
            ->orderByDesc('vrijedi_od')
            ->first();
    }

    /**
     * Parametri za kalkulator, s faktorom osobnog odbitka izračunatim prema tablici za zadani mjesec.
     *
     * @return array<string, float>
     */
    public function parametri(?CarbonInterface $period = null): array
    {
        $parametri = array_map(fn (string $kljuc): float => (float) $this->{$kljuc}, array_combine(self::PARAMETRI_KLJUCEVI, self::PARAMETRI_KLJUCEVI));

        return self::sFaktoromOdbitka($parametri, $period ?? $this->vrijedi_od);
    }

    /**
     * @param  array<string, float>  $parametri
     * @return array<string, float>
     */
    public static function sFaktoromOdbitka(array $parametri, CarbonInterface $period): array
    {
        return [...$parametri, 'faktor_odbitka' => OsobniOdbitak::faktor($parametri, $period)];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['user_id' => 'integer', 'vrijedi_od' => 'date', ...array_fill_keys(self::PARAMETRI_KLJUCEVI, 'float')];
    }
}
