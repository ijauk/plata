@extends('layouts.app')

@section('naslov', 'Uredi korisnika')

@section('sadrzaj')
    <div class="actions">
        <h1 style="margin:0; flex:1">{{ $korisnik->name }}</h1>
        @unless ($korisnik->is(auth()->user()))
            <form method="POST" action="{{ route('korisnici.destroy', $korisnik) }}" onsubmit="return confirm('Obrisati korisnika {{ $korisnik->name }} i sve njegove obračune?')">
                @csrf
                @method('DELETE')
                <button class="btn danger" type="submit">Obriši korisnika</button>
            </form>
        @endunless
    </div>
    <form method="POST" action="{{ route('korisnici.update', $korisnik) }}">
        @method('PUT')
        @include('korisnici._forma')
    </form>
@endsection
