@extends('layouts.app')

@section('naslov', 'Uredi konstante')

@section('sadrzaj')
    <div class="actions">
        <h1 style="margin:0; flex:1">Konstante od {{ $postavka->vrijedi_od->format('n/Y') }}</h1>
        <form method="POST" action="{{ route('postavke.destroy', $postavka) }}" onsubmit="return confirm('Obrisati ovaj zapis konstanti?')">
            @csrf
            @method('DELETE')
            <button class="btn danger" type="submit">Obriši</button>
        </form>
    </div>
    <form method="POST" action="{{ route('postavke.update', $postavka) }}">
        @method('PUT')
        @include('postavke._forma')
    </form>
@endsection
