<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>Listić {{ $obracun->oznaka }}</title>
    {{-- Dompdf ne podržava flex/grid: raspored je u tablicama, a DejaVu Sans pokriva č, ć, đ, š, ž. --}}
    <style>
        @page { margin: 18mm 15mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #1d2430; }
        h1 { font-size: 15pt; margin: 0; }
        h2 { font-size: 10.5pt; margin: 14px 0 4px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 3px 6px; border-bottom: 0.5pt solid #d0d5dd; text-align: left; vertical-align: top; }
        th { color: #667085; font-weight: bold; font-size: 8.5pt; }
        td.n, th.n { text-align: right; white-space: nowrap; }
        tr.total td { font-weight: bold; }
        tr.big td { font-size: 11pt; font-weight: bold; border-top: 1pt solid #1d2430; }
        .muted { color: #667085; }
        .zaglavlje td { border: none; padding: 0; vertical-align: bottom; }
        .sazetak td { border: 0.5pt solid #d0d5dd; padding: 6px 8px; width: 33%; }
        .sazetak .label { color: #667085; font-size: 8pt; }
        .sazetak .value { font-size: 13pt; font-weight: bold; }
        .stupci > tbody > tr > td { border: none; padding: 0; width: 50%; }
        .stupci > tbody > tr > td:first-child { padding-right: 10px; }
        .stupci > tbody > tr > td:last-child { padding-left: 10px; }
        .podnozje { margin-top: 18px; font-size: 7.5pt; color: #667085; }
    </style>
</head>
<body>
<table class="zaglavlje">
    <tr>
        <td><h1>Platni listić za {{ $obracun->oznaka }}</h1></td>
        <td class="n muted">{{ auth()->user()?->name }}</td>
    </tr>
</table>

<table class="sazetak" style="margin-top:10px">
    <tr>
        <td>
            <div class="label">Za isplatu ({{ $obracun->datum_isplate->format('j. n. Y.') }})</div>
            <div class="value">@eur($rezultat['isplata']) €</div>
        </td>
        <td>
            <div class="label">Bruto</div>
            <div class="value">@eur($rezultat['bruto']) €</div>
        </td>
        <td>
            <div class="label">Stvarna isplata</div>
            <div class="value">
                @if ($obracun->stvarna_isplata !== null)
                    @eur($obracun->stvarna_isplata) €
                @else
                    <span class="muted">—</span>
                @endif
            </div>
            @if ($obracun->stvarna_isplata !== null)
                <div class="label">razlika @eur($obracun->razlika()) €</div>
            @endif
        </td>
    </tr>
</table>

<table class="stupci">
    <tr>
        <td>
            <h2>Obračun</h2>
            @include('obracuni._rezultat')
        </td>
        <td>
            <h2>Satnice</h2>
            <table>
                <tr><td>Redovna satnica</td><td class="n">@broj($rezultat['satnica']) €</td></tr>
                <tr><td>Satnica prethodnog mjeseca</td><td class="n">{{ $rezultat['satnica_prethodna'] !== null ? \Illuminate\Support\Number::format($rezultat['satnica_prethodna'], maxPrecision: 4, locale: 'hr').' €' : '—' }}</td></tr>
                <tr><td>Satnica bolovanja</td><td class="n">{{ $rezultat['satnica_bolovanja'] !== null ? \Illuminate\Support\Number::format($rezultat['satnica_bolovanja'], precision: 2, locale: 'hr').' €' : '—' }}</td></tr>
            </table>

            <h2>Osobni odbitak</h2>
            @include('obracuni._odbitak', ['stavke' => $odbitak, 'osnovni' => $postavka->osobni_odbitak])

            @if ($obracun->napomena)
                <h2>Napomena</h2>
                <p style="margin:0">{{ $obracun->napomena }}</p>
            @endif
        </td>
    </tr>
</table>

<div class="podnozje">
    Konstante vrijede od {{ $postavka->vrijedi_od->format('n/Y') }} · izrađeno {{ now()->format('j. n. Y. H:i') }}
</div>
</body>
</html>
