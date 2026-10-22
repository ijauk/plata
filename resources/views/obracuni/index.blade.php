@extends('layouts.app')

@section('naslov', 'Obračuni')

@section('sadrzaj')
    <div class="actions">
        <h1 style="margin:0; flex:1">Obračuni</h1>
        <a class="btn primary" href="{{ route('obracuni.create') }}">+ Novi obračun</a>
    </div>

    <div class="panel">
        <table>
            <thead>
            <tr>
                <th>Mjesec</th>
                <th>Isplata</th>
                <th class="n">Sati</th>
                <th class="n">Učinak</th>
                <th class="n">Bruto</th>
                <th class="n">Izračun isplate</th>
                <th class="n">Stvarna isplata</th>
                <th class="n">Razlika</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($obracuni as $obracun)
                @php($rezultat = $obracun->izracun())
                <tr>
                    <td><a href="{{ route('obracuni.show', $obracun) }}">{{ $obracun->oznaka }}</a></td>
                    <td class="muted">{{ $obracun->datum_isplate->format('j. n. Y.') }}</td>
                    <td class="n">@broj($rezultat['ukupno_sati'])</td>
                    <td class="n">@broj($obracun->ucinak) %</td>
                    <td class="n">@eur($rezultat['bruto'])</td>
                    <td class="n"><strong>@eur($rezultat['isplata'])</strong></td>
                    <td class="n">
                        @if ($obracun->stvarna_isplata !== null)
                            @eur($obracun->stvarna_isplata)
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td class="n">
                        @if ($obracun->razlika() !== null)
                            <span @class(['muted' => abs($obracun->razlika()) < 0.015, 'minus' => abs($obracun->razlika()) >= 0.015])>@eur($obracun->razlika())</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">Još nema obračuna.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="muted">Razlika = izračun − stvarna isplata s platne liste. Odstupanja do 0,01 € dolaze od zaokruživanja MIO I. stupa u obračunu poslodavca.</p>
@endsection
