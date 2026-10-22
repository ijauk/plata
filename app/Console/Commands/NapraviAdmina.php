<?php

namespace App\Console\Commands;

use App\Models\Obracun;
use App\Models\Postavka;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('plata:admin {email : E-mail za prijavu} {--name= : Ime (za novog korisnika)}')]
#[Description('Napravi administratora (ili postojećeg korisnika proglasi adminom) i dodijeli mu obračune i konstante bez vlasnika')]
class NapraviAdmina extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
            $this->error('E-mail nije ispravan.');

            return self::FAILURE;
        }

        $korisnik = User::query()->where('email', $email)->first();

        if ($korisnik === null) {
            $lozinka = (string) $this->secret('Lozinka (barem 8 znakova)');

            if (mb_strlen($lozinka) < 8 || $lozinka !== $this->secret('Ponovi lozinku')) {
                $this->error('Lozinka je prekratka ili se ne podudara.');

                return self::FAILURE;
            }

            $korisnik = new User;
            $korisnik->forceFill([
                'name' => $this->option('name') ?: $this->ask('Ime', explode('@', $email)[0]),
                'email' => $email,
                'password' => $lozinka,
            ]);
        }

        $korisnik->forceFill(['is_admin' => true])->save();

        $obracuni = Obracun::query()->whereNull('user_id')->update(['user_id' => $korisnik->id]);
        $postavke = Postavka::query()->whereNull('user_id')->update(['user_id' => $korisnik->id]);

        $this->info("Administrator: {$korisnik->name} <{$korisnik->email}>");
        $this->line("Dodijeljeno: {$obracuni} obračuna i {$postavke} zapisa konstanti.");

        return self::SUCCESS;
    }
}
