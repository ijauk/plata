@extends('layouts.app')

@section('naslov', 'Obračun '.$obracun->oznaka)

@section('sadrzaj')
    <div class="actions">
        <h1 style="margin:0; flex:1">Obračun za {{ $obracun->oznaka }}</h1>
        <a class="btn" href="{{ route('simulator', ['obracun' => $obracun->id]) }}">Simuliraj</a>
        <a class="btn" href="{{ route('obracuni.pdf', $obracun) }}">PDF</a>
        <a class="btn" href="{{ route('obracuni.edit', $obracun) }}">Uredi</a>
        <form method="POST" action="{{ route('obracuni.destroy', $obracun) }}" onsubmit="return confirm('Obrisati obračun {{ $obracun->oznaka }}?')">
            @csrf
            @method('DELETE')
            <button class="btn danger" type="submit">Obriši</button>
        </form>
    </div>

    <div class="kpi">
        <div class="panel">
            <div class="label">Izračun isplate ({{ $obracun->datum_isplate->format('j. n. Y.') }})</div>
            <div class="value">@eur($rezultat['isplata']) €</div>
        </div>
        <div class="panel">
            <div class="label">Stvarna isplata</div>
            <div class="value">
                @if ($obracun->stvarna_isplata !== null)
                    @eur($obracun->stvarna_isplata) €
                    <span class="muted" style="font-size:14px; font-weight:normal">razlika @eur($obracun->razlika()) €</span>
                @else
                    <span class="muted">—</span>
                @endif
            </div>
        </div>
        <div class="panel">
            <div class="label">Bruto</div>
            <div class="value">@eur($rezultat['bruto']) €</div>
            @if ($obracun->stvarni_bruto !== null && abs($obracun->stvarni_bruto - $rezultat['bruto']) >= 0.005)
                <div class="minus">platna lista: @eur($obracun->stvarni_bruto) €</div>
            @endif
        </div>
    </div>

    <div class="cols">
        <div class="panel">
            @include('obracuni._rezultat')
        </div>
        <div>
            <div class="panel">
                <h2 style="margin-top:0">Satnice</h2>
                <table>
                    <tr><td>Redovna satnica</td><td class="n">@broj($rezultat['satnica']) €</td></tr>
                    <tr><td>Satnica prethodnog mjeseca <span class="muted">(blagdani, dopust, GO)</span></td><td class="n">{{ $rezultat['satnica_prethodna'] !== null ? \Illuminate\Support\Number::format($rezultat['satnica_prethodna'], maxPrecision: 4, locale: 'hr').' €' : '—' }}</td></tr>
                    @if ($prethodniMjesec)
                        <tr><td colspan="2" class="muted" style="white-space:normal">
                            = @eur($prethodniMjesec['bruto']) € / @broj($prethodniMjesec['sati']) h – {{ $prethodniMjesec['izvor'] }}
                        </td></tr>
                    @endif
                    <tr><td>Satnica bolovanja</td><td class="n">{{ $rezultat['satnica_bolovanja'] !== null ? \Illuminate\Support\Number::format($rezultat['satnica_bolovanja'], precision: 2, locale: 'hr').' €' : '—' }}</td></tr>
                </table>
            </div>
            <div class="panel">
                <h2 style="margin-top:0">Osobni odbitak</h2>
                @include('obracuni._odbitak', ['stavke' => $odbitak, 'osnovni' => $postavka->osobni_odbitak])
            </div>
            <div class="panel">
                <h2 style="margin-top:0">Konstante <span class="muted" style="font-weight:normal">(vrijede od {{ $postavka->vrijedi_od->format('n/Y') }})</span></h2>
                <table>
                    @foreach (\App\Models\Postavka::PARAMETRI as $kljuc => $naziv)
                        <tr><td>{{ $naziv }}</td><td class="n">@broj($postavka->{$kljuc})</td></tr>
                    @endforeach
                </table>
            </div>
            @if ($obracun->napomena)
                <div class="panel"><strong>Napomena:</strong> {{ $obracun->napomena }}</div>
            @endif
        </div>
    </div>
@endsection
