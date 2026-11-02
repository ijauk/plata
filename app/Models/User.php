<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return HasMany<Obracun, $this>
     */
    public function obracuni(): HasMany
    {
        return $this->hasMany(Obracun::class);
    }

    /**
     * @return HasMany<Postavka, $this>
     */
    public function postavke(): HasMany
    {
        return $this->hasMany(Postavka::class);
    }

    /**
     * Novi korisnik prije prvog obračuna mora unijeti svoje konstante.
     */
    public function imaKonstante(): bool
    {
        return $this->postavke()->exists();
    }

    /**
     * Dvofaktorska prijava je uključena tek kad korisnik potvrdi prvi kod iz aplikacije.
     */
    public function imaDvofaktorsku(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
