@extends('layouts.app')

@section('naslov', 'Novi korisnik')

@section('sadrzaj')
    <h1>Novi korisnik</h1>
    <p class="muted">Pošalji korisniku e-mail i početnu lozinku. Pri prvoj prijavi tražit će se da unese svoje konstante, a lozinku može promijeniti na stranici profila.</p>
    <form method="POST" action="{{ route('korisnici.store') }}">
        @include('korisnici._forma')
    </form>
@endsection
