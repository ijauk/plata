@extends('layouts.app')

@section('naslov', 'Simulator')

@php
    $redovi = [
        'bruto' => 'Bruto',
        'mio1' => 'MIO I. stup',
        'mio2' => 'MIO II. stup',
        'dohodak' => 'Dohodak',
        'porez' => 'Porez',
        'neto' => 'Neto plaća',
        'topli_obrok' => 'Topli obrok',
        'sindikat_spiv' => 'Sindikat SPIV',
        'spiv_hto' => 'SPIV-HTO',
    ];
    $razlikaIsplate = round($simulacija['isplata'] - $trenutno['isplata'], 2);
    $nazivSati = [...\App\Models\Obracun::SATI, 'ostalo_bruto' => 'Ostalo bruto (€)', 'ostalo_sati' => 'Ostalo sati'];
@endphp

@section('sadrzaj')
    <h1>Simulator „što ako”</h1>

    <form method="GET" action="{{ route('simulator') }}" class="actions">
        <label for="obracun" style="margin:0">Polazni mjesec</label>
        <select id="obracun" name="obracun" style="width:auto" onchange="this.form.submit()">
            @foreach ($obracuni as $obracun)
                <option value="{{ $obracun->id }}" @selected($obracun->is($odabrani))>{{ $obracun->oznaka }}</option>
            @endforeach
        </select>
        <noscript><button class="btn" type="submit">Odaberi</button></noscript>
        <span class="muted">Sati i satnica prethodnog mjeseca preuzimaju se iz odabranog obračuna.</span>
    </form>

    <form method="GET" action="{{ route('simulator') }}">
        <input type="hidden" name="obracun" value="{{ $odabrani->id }}">

        <div class="panel">
            <h2 style="margin-top:0">Konstante</h2>
            <div class="grid">
                @foreach ($oznakeParametara as $kljuc => $naziv)
                    <div>
                        <label for="p_{{ $kljuc }}">{{ $naziv }} <span class="muted">· sada @broj($trenutniParametri[$kljuc])</span></label>
                        <input type="text" inputmode="decimal" id="p_{{ $kljuc }}" name="p[{{ $kljuc }}]" value="@unos($simuliraniParametri[$kljuc])" @class(['changed' => array_key_exists($kljuc, $izmjeneParametara)])>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h2 style="margin-top:0">Učinak (stimulacija)</h2>
            <div class="actions" style="margin-bottom:0">
                <div style="width:160px">
                    <label for="u_ucinak">Učinak (%) <span class="muted">· u {{ $odabrani->oznaka }} @broj($trenutniUnos['ucinak'])</span></label>
                    <input type="text" inputmode="decimal" id="u_ucinak" name="u[ucinak]" value="@unos($simuliraniUnos['ucinak'])" @class(['changed' => array_key_exists('ucinak', $izmjeneUnosa)])>
                </div>
                @foreach ($poUcinku as $varijanta)
                    <button type="submit" style="align-self:flex-end" @class(['btn', 'primary' => $varijanta['ucinak'] == $simuliraniUnos['ucinak']])
                            onclick="document.getElementById('u_ucinak').value = '@unos($varijanta['ucinak'])'">
                        @broj($varijanta['ucinak']) % → @eur($varijanta['isplata']) €
                    </button>
                @endforeach
            </div>
            <p class="muted" style="margin-bottom:0">Gumbi pokazuju isplatu uz svaki učinak koji ste dosad imali (sa simuliranim konstantama i satima) – klik ga odabire.
                Učinak se mijenja svaki mjesec, pa se ne prenosi na usporedbu zadnjih 12 mjeseci: tamo svaki mjesec zadržava svoj stvarni učinak.</p>
        </div>

        <details class="panel" @if (array_diff_key($izmjeneUnosa, ['ucinak' => true])) open @endif>
            <summary><strong>Sati</strong> <span class="muted">(neobavezno – npr. „što ako uzmem 5 dana godišnjeg”)</span></summary>
            <div class="grid" style="margin-top:12px">
                @foreach ($nazivSati as $kljuc => $naziv)
                    <div>
                        <label for="u_{{ $kljuc }}">{{ $naziv }} <span class="muted">· sada @broj($trenutniUnos[$kljuc])</span></label>
                        <input type="text" inputmode="decimal" id="u_{{ $kljuc }}" name="u[{{ $kljuc }}]" value="@unos($simuliraniUnos[$kljuc])" @class(['changed' => array_key_exists($kljuc, $izmjeneUnosa)])>
                    </div>
                @endforeach
            </div>
        </details>

        <div class="actions">
            <button class="btn primary" type="submit">Izračunaj</button>
            <a class="btn" href="{{ route('simulator', ['obracun' => $odabrani->id]) }}">Vrati na stvarne vrijednosti</a>
        </div>
    </form>

    <div class="kpi">
        <div class="panel">
            <div class="label">Isplata za {{ $odabrani->oznaka }} – sada</div>
            <div class="value">@eur($trenutno['isplata']) €</div>
        </div>
        <div class="panel">
            <div class="label">Isplata – simulacija</div>
            <div class="value">@eur($simulacija['isplata']) €</div>
        </div>
        <div class="panel">
            <div class="label">Razlika mjesečno</div>
            <div @class(['value', 'plus' => $razlikaIsplate > 0, 'minus' => $razlikaIsplate < 0])>{{ $razlikaIsplate > 0 ? '+' : '' }}@eur($razlikaIsplate) €</div>
            <div class="muted">bruto {{ $simulacija['bruto'] - $trenutno['bruto'] > 0 ? '+' : '' }}@eur($simulacija['bruto'] - $trenutno['bruto']) €</div>
        </div>
    </div>

    <div class="cols">
        <div class="panel">
            <h2 style="margin-top:0">Usporedba</h2>
            <table>
                <thead>
                <tr><th>Stavka</th><th class="n">Sada</th><th class="n">Simulacija</th><th class="n">Razlika</th></tr>
                </thead>
                <tbody>
                @php($naziviStavki = collect($trenutno['stavke'])->merge($simulacija['stavke'])->pluck('naziv', 'sifra'))
                @foreach ($naziviStavki as $sifra => $naziv)
                    @php($a = collect($trenutno['stavke'])->firstWhere('sifra', $sifra)['iznos'] ?? 0)
                    @php($b = collect($simulacija['stavke'])->firstWhere('sifra', $sifra)['iznos'] ?? 0)
                    <tr>
                        <td><span class="muted">{{ $sifra }}</span> {{ $naziv }}</td>
                        <td class="n">@eur($a)</td>
                        <td class="n">@eur($b)</td>
                        <td @class(['n', 'plus' => $b > $a, 'minus' => $b < $a])>{{ abs($b - $a) < 0.005 ? '' : \Illuminate\Support\Number::format($b - $a, precision: 2, locale: 'hr') }}</td>
                    </tr>
                @endforeach
                @foreach ($redovi as $kljuc => $naziv)
                    @php($a = $trenutno[$kljuc])
                    @php($b = $simulacija[$kljuc])
                    <tr @class(['total' => in_array($kljuc, ['bruto', 'dohodak', 'neto'])])>
                        <td>{{ $naziv }}</td>
                        <td class="n">@eur($a)</td>
                        <td class="n">@eur($b)</td>
                        <td @class(['n', 'plus' => $b > $a, 'minus' => $b < $a])>{{ abs($b - $a) < 0.005 ? '' : \Illuminate\Support\Number::format($b - $a, precision: 2, locale: 'hr') }}</td>
                    </tr>
                @endforeach
                <tr class="big">
                    <td>Za isplatu</td>
                    <td class="n">@eur($trenutno['isplata'])</td>
                    <td class="n">@eur($simulacija['isplata'])</td>
                    <td class="n">{{ $razlikaIsplate == 0 ? '' : \Illuminate\Support\Number::format($razlikaIsplate, precision: 2, locale: 'hr') }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div>
        @if ($odbitakSada != $odbitakSimulacija || $trenutniParametri['osobni_odbitak'] != $simuliraniParametri['osobni_odbitak'])
            <div class="panel">
                <h2 style="margin-top:0">Osobni odbitak – sada</h2>
                @include('obracuni._odbitak', ['stavke' => $odbitakSada, 'osnovni' => $trenutniParametri['osobni_odbitak']])
                <h2>Osobni odbitak – simulacija</h2>
                @include('obracuni._odbitak', ['stavke' => $odbitakSimulacija, 'osnovni' => $simuliraniParametri['osobni_odbitak']])
                @if ($simulacija['neiskoristeni_odbitak'] > 0)
                    <p class="muted" style="margin-bottom:0">Odbitak je veći od dohotka – porez je 0, a @eur($simulacija['neiskoristeni_odbitak']) € odbitka ostaje neiskorišteno u ovom mjesecu.</p>
                @endif
            </div>
        @endif
        <div class="panel">
            <h2 style="margin-top:0">Učinak na zadnjih 12 mjeseci</h2>
            @if ($godisnje === [])
                <p class="muted">Promijeni neku konstantu da vidiš koliko bi to iznosilo kroz zadnjih 12 obračuna (sa stvarnim satima svakog mjeseca).</p>
            @else
                <table>
                    <thead>
                    <tr><th>Mjesec</th><th class="n">Sada</th><th class="n">Simulacija</th><th class="n">Razlika</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($godisnje as $red)
                        <tr>
                            <td>{{ $red['obracun']->oznaka }}</td>
                            <td class="n">@eur($red['sada'])</td>
                            <td class="n">@eur($red['simulacija'])</td>
                            <td @class(['n', 'plus' => $red['razlika'] > 0, 'minus' => $red['razlika'] < 0])>@eur($red['razlika'])</td>
                        </tr>
                    @endforeach
                    @php($ukupno = round(array_sum(array_column($godisnje, 'razlika')), 2))
                    <tr class="big">
                        <td>Ukupno</td>
                        <td class="n">@eur(array_sum(array_column($godisnje, 'sada')))</td>
                        <td class="n">@eur(array_sum(array_column($godisnje, 'simulacija')))</td>
                        <td class="n">@eur($ukupno)</td>
                    </tr>
                    </tbody>
                </table>
                <p class="muted" style="margin-bottom:0">Promijenjene konstante primijenjene su na svaki mjesec umjesto tada važećih; sati i učinak su stvarni za svaki mjesec. Satnica prethodnog mjeseca (blagdani, GO) ostaje stvarna, pa je učinak na te sate malo podcijenjen.</p>
            @endif
        </div>
        </div>
    </div>
@endsection
