@extends('layouts.app')

@section('naslov', 'Novi obračun')

@section('sadrzaj')
    <h1>Novi obračun</h1>
    <form method="POST" action="{{ route('obracuni.store') }}">
        @include('obracuni._forma')
    </form>
@endsection
