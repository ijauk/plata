@extends('layouts.app')

@section('naslov', 'Uredi '.$obracun->oznaka)

@section('sadrzaj')
    <h1>Uredi obračun {{ $obracun->oznaka }}</h1>
    <form method="POST" action="{{ route('obracuni.update', $obracun) }}">
        @method('PUT')
        @include('obracuni._forma')
    </form>
@endsection
