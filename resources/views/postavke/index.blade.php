@extends('layouts.app')

@section('naslov', 'Konstante')

@section('sadrzaj')
    <div class="actions">
        <h1 style="margin:0; flex:1">Konstante obračuna</h1>
        <a class="btn primary" href="{{ route('postavke.create') }}">+ Nova promjena</a>
    </div>
    <p class="muted">Svaki redak vrijedi od navedenog mjeseca do sljedeće promjene. Kad se nešto promijeni (osnovica, minuli rad…), dodaj novi redak – stari obračuni ostaju netaknuti.</p>

    <div class="panel">
        <table>
            <thead>
            <tr>
                <th>Vrijedi od</th>
                @foreach (\App\Models\Postavka::PARAMETRI as $naziv)
                    <th class="n">{{ $naziv }}</th>
                @endforeach
                <th>Napomena</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($postavke as $indeks => $postavka)
                @php($starija = $postavke->get($indeks + 1))
                <tr>
                    <td><strong>{{ $postavka->vrijedi_od->format('n/Y') }}</strong></td>
                    @foreach (\App\Models\Postavka::PARAMETRI_KLJUCEVI as $kljuc)
                        <td @class(['n', 'plus' => $starija && $starija->{$kljuc} != $postavka->{$kljuc}])>@broj($postavka->{$kljuc})</td>
                    @endforeach
                    <td class="muted">{{ $postavka->napomena }}</td>
                    <td><a href="{{ route('postavke.edit', $postavka) }}">uredi</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="muted">Zeleno = vrijednost promijenjena u odnosu na prethodni redak.</p>
@endsection
