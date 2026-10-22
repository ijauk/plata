@extends('layouts.app')

@section('naslov', 'Prijava')

@section('sadrzaj')
    <div class="panel" style="max-width:380px; margin:40px auto">
        <h1>Prijava</h1>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div style="margin-bottom:12px">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div style="margin-bottom:12px">
                <label for="password">Lozinka</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <label style="display:flex; gap:8px; align-items:center; margin-bottom:16px">
                <input type="checkbox" name="zapamti" value="1" style="width:auto"> Zapamti me
            </label>
            <button class="btn primary" type="submit" style="width:100%">Prijavi se</button>
        </form>
    </div>
@endsection
