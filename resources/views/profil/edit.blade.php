@extends('layouts.app')

@section('naslov', 'Profil')

@section('sadrzaj')
    <h1>{{ auth()->user()->name }}</h1>
    <p class="muted">{{ auth()->user()->email }}@if (auth()->user()->is_admin) · administrator @endif</p>

    <div class="panel" style="max-width:420px">
        <h2 style="margin-top:0">Promjena lozinke</h2>
        <form method="POST" action="{{ route('profil.update') }}">
            @csrf
            @method('PUT')
            <div style="margin-bottom:12px">
                <label for="trenutna_lozinka">Trenutna lozinka</label>
                <input type="password" id="trenutna_lozinka" name="trenutna_lozinka" required autocomplete="current-password">
            </div>
            <div style="margin-bottom:12px">
                <label for="password">Nova lozinka</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <div style="margin-bottom:16px">
                <label for="password_confirmation">Ponovi novu lozinku</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button class="btn primary" type="submit">Promijeni lozinku</button>
        </form>
    </div>

    <div class="panel" style="max-width:420px">
        <h2 style="margin-top:0">Dvofaktorska prijava</h2>

        @if (session('kodovi_za_oporavak'))
            <div class="errors">
                <strong>Spremi kodove za oporavak</strong> (npr. u upravitelj lozinki). Svaki vrijedi jednom, a trebat će ti ako izgubiš mobitel. Više se neće prikazati.
                <pre style="font-size:15px; margin:8px 0 0">{{ implode("\n", session('kodovi_za_oporavak')) }}</pre>
            </div>
        @endif

        @if (auth()->user()->imaDvofaktorsku())
            <p style="margin-top:0">Uključena od {{ auth()->user()->two_factor_confirmed_at->format('j. n. Y.') }}. Pri prijavi se uz lozinku traži kod iz aplikacije.</p>
            <p class="muted">Preostalo kodova za oporavak: {{ count(auth()->user()->two_factor_recovery_codes ?? []) }}</p>
            <form method="POST" style="margin-bottom:0">
                @csrf
                <div style="margin-bottom:12px">
                    <label for="lozinka">Lozinka (za promjene)</label>
                    <input type="password" id="lozinka" name="lozinka" required autocomplete="current-password">
                </div>
                <div class="actions" style="margin-bottom:0">
                    <button class="btn" type="submit" formaction="{{ route('dvofaktorska.kodovi') }}">Novi kodovi za oporavak</button>
                    <button class="btn danger" type="submit" formaction="{{ route('dvofaktorska.destroy') }}" name="_method" value="DELETE">Isključi</button>
                </div>
            </form>
        @elseif ($qrKod)
            <p style="margin-top:0">Skeniraj QR kod u aplikaciji za autentifikaciju (Google Authenticator, Authy, 1Password…), pa upiši kod koji ti prikaže.</p>
            <div style="background:#fff; display:inline-block; padding:8px; border-radius:8px">{!! $qrKod !!}</div>
            <p class="muted" style="word-break:break-all">Ili ručno upiši ključ: <code>{{ auth()->user()->two_factor_secret }}</code></p>
            <form method="POST" action="{{ route('dvofaktorska.update') }}">
                @csrf
                @method('PUT')
                <div style="margin-bottom:12px">
                    <label for="kod">Kod iz aplikacije</label>
                    <input type="text" id="kod" name="kod" required autocomplete="one-time-code" inputmode="numeric">
                </div>
                <div class="actions" style="margin-bottom:0">
                    <button class="btn primary" type="submit">Potvrdi i uključi</button>
                    <button class="btn" type="submit" form="odustani-dvofaktorska">Odustani</button>
                </div>
            </form>
            <form id="odustani-dvofaktorska" method="POST" action="{{ route('dvofaktorska.destroy') }}">
                @csrf
                @method('DELETE')
            </form>
        @else
            <p style="margin-top:0">Uz lozinku, pri prijavi se traži i kod iz aplikacije na mobitelu. Netko tko sazna tvoju lozinku svejedno se ne može prijaviti.</p>
            <form method="POST" action="{{ route('dvofaktorska.store') }}">
                @csrf
                <button class="btn primary" type="submit">Uključi</button>
            </form>
        @endif
    </div>
@endsection
