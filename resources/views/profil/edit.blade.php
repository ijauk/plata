@extends('layouts.app')

@section('naslov', 'Profil')

@section('sadrzaj')
    <h1>{{ auth()->user()->name }}</h1>
    <p class="muted">{{ auth()->user()->email }}@if (auth()->user()->is_admin) · administrator @endif</p>

    <div class="panel" style="max-width:420px">
        <h2 style="margin-top:0">Promjena lozinke</h2>
        <form method="POST" action="{{ route('profil.update') }}">
            @csrf
            @method('PUT')
            <div style="margin-bottom:12px">
                <label for="trenutna_lozinka">Trenutna lozinka</label>
                <input type="password" id="trenutna_lozinka" name="trenutna_lozinka" required autocomplete="current-password">
            </div>
            <div style="margin-bottom:12px">
                <label for="password">Nova lozinka</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <div style="margin-bottom:16px">
                <label for="password_confirmation">Ponovi novu lozinku</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button class="btn primary" type="submit">Promijeni lozinku</button>
        </form>
    </div>
@endsection
