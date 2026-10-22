@extends('layouts.app')

@section('naslov', 'Nova promjena konstanti')

@section('sadrzaj')
    @if ($prviUnos)
        <h1>Vaše konstante</h1>
        <p class="muted">Prepišite ih s platne liste (dio „Opći podaci potrebni za obračun”). Zakonske stope su već popunjene – provjerite ih.
            „Vrijedi od” postavite na najstariji mjesec koji ćete obračunavati.</p>
    @else
        <h1>Nova promjena konstanti</h1>
        <p class="muted">Polja su popunjena zadnjim važećim vrijednostima – promijeni samo ono što se mijenja.</p>
    @endif
    <form method="POST" action="{{ route('postavke.store') }}">
        @include('postavke._forma')
    </form>
@endsection
