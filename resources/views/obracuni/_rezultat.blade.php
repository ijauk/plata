{{-- Očekuje: $rezultat (iz KalkulatorPlace), opcionalno $usporedba (drugi rezultat za stupac razlike) --}}
<table>
    <thead>
    <tr>
        <th>Stavka</th>
        <th class="n">Sati</th>
        <th class="n">Iznos (€)</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($rezultat['stavke'] as $stavka)
        <tr>
            <td><span class="muted">{{ $stavka['sifra'] }}</span> {{ $stavka['naziv'] }}</td>
            <td class="n">{{ $stavka['sati'] !== null ? \Illuminate\Support\Number::format($stavka['sati'], maxPrecision: 2, locale: 'hr') : '' }}</td>
            <td class="n">@eur($stavka['iznos'])</td>
        </tr>
    @endforeach
    <tr class="total"><td>Bruto</td><td class="n">@broj($rezultat['ukupno_sati'])</td><td class="n">@eur($rezultat['bruto'])</td></tr>
    <tr><td>− MIO I. stup</td><td></td><td class="n">@eur($rezultat['mio1'])</td></tr>
    <tr><td>− MIO II. stup</td><td></td><td class="n">@eur($rezultat['mio2'])</td></tr>
    <tr class="total"><td>Dohodak</td><td></td><td class="n">@eur($rezultat['dohodak'])</td></tr>
    <tr><td class="muted">Osobni odbitak <span class="muted">(faktor @broj($rezultat['faktor_odbitka']))</span></td><td></td><td class="n muted">@eur($rezultat['osobni_odbitak'])</td></tr>
    @if ($rezultat['neiskoristeni_odbitak'] > 0)
        <tr><td class="muted" colspan="2">↳ neiskorišteni dio odbitka u ovom mjesecu</td><td class="n muted">@eur($rezultat['neiskoristeni_odbitak'])</td></tr>
    @endif
    <tr><td class="muted">Porezna osnovica</td><td></td><td class="n muted">@eur($rezultat['porezna_osnovica'])</td></tr>
    <tr><td>− Porez na dohodak</td><td></td><td class="n">@eur($rezultat['porez'])</td></tr>
    <tr class="total"><td>Neto plaća</td><td></td><td class="n">@eur($rezultat['neto'])</td></tr>
    <tr><td>+ Topli obrok</td><td></td><td class="n">@eur($rezultat['topli_obrok'])</td></tr>
    <tr><td>− Sindikat SPIV</td><td></td><td class="n">@eur($rezultat['sindikat_spiv'])</td></tr>
    <tr><td>− SPIV-HTO</td><td></td><td class="n">@eur($rezultat['spiv_hto'])</td></tr>
    <tr class="big"><td>Za isplatu</td><td></td><td class="n">@eur($rezultat['isplata'])</td></tr>
    </tbody>
</table>
