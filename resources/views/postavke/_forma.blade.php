@csrf
<div class="panel">
    <div class="grid">
        <div>
            <label for="vrijedi_od">Vrijedi od (mjesec)</label>
            <input type="month" id="vrijedi_od" name="vrijedi_od" value="{{ old('vrijedi_od', $postavka->vrijedi_od?->format('Y-m')) }}" required>
        </div>
        @foreach (\App\Models\Postavka::PARAMETRI as $kljuc => $naziv)
            <div>
                <label for="{{ $kljuc }}">{{ $naziv }}</label>
                <input type="text" inputmode="decimal" id="{{ $kljuc }}" name="{{ $kljuc }}" value="{{ \App\Support\Decimalni::zaUnos(old($kljuc) ?? $postavka->{$kljuc}) }}" required>
            </div>
        @endforeach
    </div>
    <details style="margin-top:12px">
        <summary class="muted">Tablica koeficijenata osobnog odbitka</summary>
        @php($tablica = \App\Services\OsobniOdbitak::tablica($postavka->vrijedi_od ?? now()))
        <p class="muted">Faktor = 1 (osnovni) + zbroj koeficijenata. Koeficijenti djece se zbrajaju – dvoje djece: 0,5 + 0,7.
            Ako odbitak za djecu dijelite s drugim roditeljem, upišite svoj udio (npr. 50 %).</p>
        <table style="width:auto">
            @foreach ($tablica['djeca'] as $indeks => $koeficijent)
                <tr><td>{{ $indeks + 1 }}. dijete</td><td class="n">@broj($koeficijent)</td></tr>
            @endforeach
            <tr><td>Uzdržavani član / alimentacija</td><td class="n">@broj($tablica['uzdrzavani'])</td></tr>
            <tr><td>Invalidnost (manja od 100 %)</td><td class="n">@broj($tablica['invalidnost'])</td></tr>
        </table>
    </details>
    <div style="margin-top:12px">
        <label for="napomena">Napomena</label>
        <input type="text" id="napomena" name="napomena" value="{{ old('napomena', $postavka->napomena) }}" placeholder="npr. Nova osnovica prema dodatku KU">
    </div>
</div>
<div class="actions">
    <button class="btn primary" type="submit">Spremi</button>
    <a class="btn" href="{{ route('postavke.index') }}">Odustani</a>
</div>
