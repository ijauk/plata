{{-- Očekuje: $stavke (OsobniOdbitak::stavke), $osnovni (osnovni osobni odbitak u €) --}}
<table>
    <thead>
    <tr><th>Olakšica</th><th class="n">Koeficijent</th><th class="n">Iznos (€)</th></tr>
    </thead>
    <tbody>
    @foreach ($stavke as $stavka)
        <tr>
            <td>{{ $stavka['naziv'] }}</td>
            <td class="n">@broj($stavka['koeficijent'])</td>
            <td class="n">@eur($stavka['koeficijent'] * $osnovni)</td>
        </tr>
    @endforeach
    @php($faktor = array_sum(array_column($stavke, 'koeficijent')))
    <tr class="total">
        <td>Ukupno</td>
        <td class="n">@broj($faktor)</td>
        <td class="n">@eur(round($faktor * $osnovni, 2))</td>
    </tr>
    </tbody>
</table>
