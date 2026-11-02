@extends('layouts.app')

@section('naslov', 'Prijava')

@section('sadrzaj')
    <div class="panel" style="max-width:380px; margin:40px auto">
        <h1>Kod za prijavu</h1>
        <p class="muted" style="margin-top:0">Upiši 6-znamenkasti kod iz aplikacije za autentifikaciju. Ako nemaš mobitel pri ruci, upiši jedan od kodova za oporavak.</p>
        <form method="POST" action="{{ route('prijava.kod') }}">
            @csrf
            <div style="margin-bottom:16px">
                <label for="kod">Kod</label>
                <input type="text" id="kod" name="kod" required autofocus autocomplete="one-time-code">
            </div>
            <button class="btn primary" type="submit" style="width:100%">Potvrdi</button>
        </form>
        <p style="margin-bottom:0"><a href="{{ route('login') }}">Natrag na prijavu</a></p>
    </div>
@endsection
