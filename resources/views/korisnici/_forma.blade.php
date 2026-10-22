@csrf
<div class="panel" style="max-width:520px">
    <div style="margin-bottom:12px">
        <label for="name">Ime</label>
        <input type="text" id="name" name="name" value="{{ old('name', $korisnik->name) }}" required>
    </div>
    <div style="margin-bottom:12px">
        <label for="email">E-mail (za prijavu)</label>
        <input type="email" id="email" name="email" value="{{ old('email', $korisnik->email) }}" required>
    </div>
    <div style="margin-bottom:12px">
        <label for="password">{{ $korisnik->exists ? 'Nova lozinka (ostavi prazno da ostane ista)' : 'Početna lozinka' }}</label>
        <input type="text" id="password" name="password" autocomplete="new-password" @required(! $korisnik->exists)>
    </div>
    <label style="display:flex; gap:8px; align-items:center">
        <input type="hidden" name="is_admin" value="0">
        <input type="checkbox" name="is_admin" value="1" style="width:auto" @checked(old('is_admin', $korisnik->is_admin)) @disabled($korisnik->is(auth()->user()))>
        Administrator (može dodavati korisnike)
    </label>
</div>
<div class="actions">
    <button class="btn primary" type="submit">Spremi</button>
    <a class="btn" href="{{ route('korisnici.index') }}">Odustani</a>
</div>
