<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Dvofaktorska prijava kodom iz aplikacije (Google Authenticator, Authy, 1Password…).
 *
 * Kod vrijedi 30 s, uz toleranciju od jednog koraka zbog razlike u satu. Svaki kod
 * prolazi samo jednom: pamti se zadnji iskorišteni vremenski korak.
 */
class Dvofaktorska
{
    public const BROJ_KODOVA_ZA_OPORAVAK = 8;

    public function __construct(private Google2FA $google2fa) {}

    public function noviTajniKljuc(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * QR kod (SVG) koji aplikacija skenira pri uključivanju.
     */
    public function qrKod(User $korisnik): string
    {
        $url = $this->google2fa->getQRCodeUrl(config('app.name'), $korisnik->email, $korisnik->two_factor_secret);

        return (new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd)))->writeString($url);
    }

    /**
     * Provjerava kod iz aplikacije i odbija kod koji je već jednom iskorišten.
     */
    public function provjeriKod(User $korisnik, string $kod): bool
    {
        $kod = preg_replace('/\s+/', '', $kod);

        if ($korisnik->two_factor_secret === null || ! preg_match('/^\d{6}$/', $kod)) {
            return false;
        }

        $korak = $this->google2fa->verifyKeyNewer($korisnik->two_factor_secret, $kod, $korisnik->two_factor_last_timestamp ?? 0, 1);

        if ($korak === false) {
            return false;
        }

        $korisnik->forceFill(['two_factor_last_timestamp' => $korak])->save();

        return true;
    }

    /**
     * @return list<string>
     */
    public function noviKodoviZaOporavak(): array
    {
        return collect(range(1, self::BROJ_KODOVA_ZA_OPORAVAK))
            ->map(fn (): string => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    /**
     * Kod za oporavak (kad mobitel nije pri ruci) može se iskoristiti samo jednom.
     */
    public function iskoristiKodZaOporavak(User $korisnik, string $kod): bool
    {
        $kod = Str::lower(trim($kod));
        $kodovi = $korisnik->two_factor_recovery_codes ?? [];
        $pronadeni = collect($kodovi)->first(fn (string $postojeci): bool => hash_equals($postojeci, $kod));

        if ($pronadeni === null) {
            return false;
        }

        $korisnik->forceFill(['two_factor_recovery_codes' => array_values(array_diff($kodovi, [$pronadeni]))])->save();

        return true;
    }
}
