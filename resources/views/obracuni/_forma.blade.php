@csrf
<div class="panel">
    <h2 style="margin-top:0">Mjesec i sati</h2>
    <div class="grid">
        <div>
            <label for="period_birac">Mjesec obračuna</label>
            <x-mjesec name="period" :value="old('period', $obracun->period?->format('Y-m'))" required />
        </div>
        @foreach (\App\Models\Obracun::SATI as $polje => $naziv)
            <div>
                <label for="{{ $polje }}">{{ $naziv }} (sati)</label>
                <input type="text" inputmode="decimal" id="{{ $polje }}" name="{{ $polje }}" value="{{ \App\Support\Decimalni::zaUnos(old($polje) ?? $obracun->{$polje} ?? 0) }}" required>
                <div class="pomoc">listić: {{ \App\Models\Obracun::SATI_NA_LISTICU[$polje] }}</div>
            </div>
        @endforeach
        <div>
            <label for="ucinak">Učinak / stimulacija (%)</label>
            <input type="text" inputmode="decimal" id="ucinak" name="ucinak" value="{{ \App\Support\Decimalni::zaUnos(old('ucinak') ?? $obracun->ucinak) }}" required>
        </div>
    </div>
    <p class="muted" style="margin-bottom:0">
        Fond sati za {{ $obracun->period?->format('n/Y') }}: {{ $obracun->period ? \App\Models\Obracun::fondSatiMjeseca($obracun->period) : '—' }}
        (radni dani × 8). Redovan rad = fond − blagdani − dopust − godišnji − bolovanje.
    </p>
</div>

<div class="panel">
    <h2 style="margin-top:0">Ostalo i prethodni mjesec <span class="muted" style="font-weight:normal">(neobavezno)</span></h2>
    <div class="grid">
        <div>
            <label for="ostalo_bruto">Ostala primanja – bruto (€)</label>
            <input type="text" inputmode="decimal" id="ostalo_bruto" name="ostalo_bruto" value="{{ \App\Support\Decimalni::zaUnos(old('ostalo_bruto') ?? $obracun->ostalo_bruto ?: null) }}">
        </div>
        <div>
            <label for="ostalo_sati">Ostala primanja – sati</label>
            <input type="text" inputmode="decimal" id="ostalo_sati" name="ostalo_sati" value="{{ \App\Support\Decimalni::zaUnos(old('ostalo_sati') ?? $obracun->ostalo_sati ?: null) }}">
        </div>
        <div>
            <label for="prethodni_bruto">Ukupni bruto prethodnog mjeseca (€)</label>
            <input type="text" inputmode="decimal" id="prethodni_bruto" name="prethodni_bruto" value="{{ \App\Support\Decimalni::zaUnos(old('prethodni_bruto') ?? $obracun->prethodni_bruto) }}">
        </div>
        <div>
            <label for="prethodni_sati">Ukupni sati prethodnog mjeseca</label>
            <input type="text" inputmode="decimal" id="prethodni_sati" name="prethodni_sati" value="{{ \App\Support\Decimalni::zaUnos(old('prethodni_sati') ?? $obracun->prethodni_sati) }}">
        </div>
    </div>
    <p class="muted" style="margin-bottom:0">Upiši samo ako za prethodni mjesec nema obračuna u aplikaciji – inače se preuzima automatski.
        S platne liste prethodnog mjeseca prepiši <strong>redak „Ukupno”</strong> iz rekapitulacije bruta na dnu (sati i bruto, npr. 168,00 i 4.111,04) –
        ne samo redovni rad. Iz toga se računa satnica za blagdane, dopust, godišnji i bolovanje.</p>
</div>

<div class="panel">
    <h2 style="margin-top:0">S platne liste <span class="muted" style="font-weight:normal">(kad stigne)</span></h2>
    <div class="grid">
        <div>
            <label for="stvarni_bruto">Stvarni bruto (€)</label>
            <input type="text" inputmode="decimal" id="stvarni_bruto" name="stvarni_bruto" value="{{ \App\Support\Decimalni::zaUnos(old('stvarni_bruto') ?? $obracun->stvarni_bruto) }}">
        </div>
        <div>
            <label for="stvarna_isplata">Stvarna isplata (€)</label>
            <input type="text" inputmode="decimal" id="stvarna_isplata" name="stvarna_isplata" value="{{ \App\Support\Decimalni::zaUnos(old('stvarna_isplata') ?? $obracun->stvarna_isplata) }}">
        </div>
    </div>
    <div style="margin-top:12px">
        <label for="napomena">Napomena</label>
        <textarea id="napomena" name="napomena" rows="2">{{ old('napomena', $obracun->napomena) }}</textarea>
    </div>
</div>

<div class="actions">
    <button class="btn primary" type="submit">Spremi i izračunaj</button>
    <a class="btn" href="{{ $obracun->exists ? route('obracuni.show', $obracun) : route('obracuni.index') }}">Odustani</a>
</div>
