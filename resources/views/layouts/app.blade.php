<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('naslov', 'Plata') · Plata</title>
    <style>
        :root {
            --bg: #f6f7f9; --panel: #fff; --text: #1d2430; --muted: #667085; --line: #e3e6eb;
            --accent: #1f6feb; --accent-soft: #e8f0fe; --plus: #12805c; --minus: #c0362c; --warn-bg: #fff6e0;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #12151a; --panel: #1b1f26; --text: #e6e8eb; --muted: #98a2b3; --line: #2c323c;
                --accent: #6ea8ff; --accent-soft: #1d2a40; --plus: #4cc38a; --minus: #ff7b72; --warn-bg: #3a2f14;
            }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        header { background: var(--panel); border-bottom: 1px solid var(--line); }
        header .wrap { display: flex; gap: 24px; align-items: center; padding-top: 12px; padding-bottom: 12px; flex-wrap: wrap; }
        header strong { font-size: 17px; }
        nav a { color: var(--muted); text-decoration: none; margin-right: 16px; }
        nav a.active, nav a:hover { color: var(--accent); }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 0 16px; }
        main { padding: 24px 0 48px; }
        h1 { font-size: 22px; margin: 0 0 16px; }
        h2 { font-size: 17px; margin: 24px 0 8px; }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 10px; padding: 16px; margin-bottom: 16px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 6px 10px; border-bottom: 1px solid var(--line); text-align: left; white-space: nowrap; }
        th { color: var(--muted); font-weight: 600; font-size: 13px; }
        td.n, th.n { text-align: right; font-variant-numeric: tabular-nums; }
        tr.total td { font-weight: 700; }
        tr.big td { font-size: 17px; font-weight: 700; color: var(--accent); }
        .plus { color: var(--plus); } .minus { color: var(--minus); } .muted { color: var(--muted); }
        .btn { display: inline-block; padding: 7px 14px; border-radius: 8px; border: 1px solid var(--line); background: var(--panel); color: var(--text); text-decoration: none; cursor: pointer; font: inherit; }
        .btn.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn.danger { color: var(--minus); }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px 16px; }
        label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 3px; }
        label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pomoc { font-size: 12px; color: var(--muted); margin-top: 3px; }
        input, select, textarea { width: 100%; padding: 7px 9px; border: 1px solid var(--line); border-radius: 7px; background: var(--bg); color: var(--text); font: inherit; }
        input.changed { border-color: var(--accent); background: var(--accent-soft); }
        .status { background: var(--accent-soft); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; }
        .errors { background: var(--warn-bg); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; }
        .error { color: var(--minus); font-size: 13px; }
        .kpi { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
        .kpi .panel { flex: 1 1 200px; margin: 0; }
        .kpi .label { color: var(--muted); font-size: 13px; }
        .kpi .value { font-size: 24px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 800px) { .cols { grid-template-columns: 1fr; } }
        a { color: var(--accent); }
    </style>
    @stack('stilovi')
</head>
<body>
<header>
    <div class="wrap">
        <strong>Plata</strong>
        @auth
            <nav style="flex:1">
                <a href="{{ route('obracuni.index') }}" @class(['active' => request()->routeIs('obracuni.*')])>Obračuni</a>
                <a href="{{ route('postavke.index') }}" @class(['active' => request()->routeIs('postavke.*')])>Konstante</a>
                <a href="{{ route('simulator') }}" @class(['active' => request()->routeIs('simulator')])>Simulator</a>
                @can('admin')
                    <a href="{{ route('korisnici.index') }}" @class(['active' => request()->routeIs('korisnici.*')])>Korisnici</a>
                @endcan
            </nav>
            <nav>
                <a href="{{ route('profil.edit') }}" @class(['active' => request()->routeIs('profil.*')])>{{ auth()->user()->name }}</a>
            </nav>
            <form method="POST" action="{{ route('odjava') }}">
                @csrf
                <button class="btn" type="submit">Odjava</button>
            </form>
        @endauth
    </div>
</header>
<main class="wrap">
    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="errors">
            @foreach ($errors->all() as $greska)
                <div>{{ $greska }}</div>
            @endforeach
        </div>
    @endif
    @yield('sadrzaj')
</main>
@stack('skripte')
</body>
</html>
