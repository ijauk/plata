@extends('layouts.app')

@section('naslov', 'Korisnici')

@section('sadrzaj')
    <div class="actions">
        <h1 style="margin:0; flex:1">Korisnici</h1>
        <a class="btn primary" href="{{ route('korisnici.create') }}">+ Novi korisnik</a>
    </div>

    <div class="panel">
        <table>
            <thead>
            <tr>
                <th>Ime</th>
                <th>E-mail</th>
                <th>Uloga</th>
                <th class="n">Obračuna</th>
                <th>Konstante</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($korisnici as $korisnik)
                <tr>
                    <td><strong>{{ $korisnik->name }}</strong></td>
                    <td>{{ $korisnik->email }}</td>
                    <td>{{ $korisnik->is_admin ? 'administrator' : 'korisnik' }}@if ($korisnik->imaDvofaktorsku()) <span class="muted">· 2FA</span>@endif</td>
                    <td class="n">{{ $korisnik->obracuni_count }}</td>
                    <td>
                        @if ($korisnik->postavke_count > 0)
                            unesene
                        @else
                            <span class="muted">čeka prvu prijavu</span>
                        @endif
                    </td>
                    <td><a href="{{ route('korisnici.edit', $korisnik) }}">uredi</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="muted">Svaki korisnik vidi samo svoje obračune i konstante. Administrator dodaje korisnike, ali ne vidi njihove plaće.</p>
@endsection
