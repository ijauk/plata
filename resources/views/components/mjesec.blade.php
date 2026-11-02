@props(['name', 'value' => null, 'required' => false])

{{--
    Birač mjeseca (Tempus Dominus, hrvatski). Forma šalje skriveno polje u obliku "YYYY-MM";
    vidljivo polje prikazuje npr. "kolovoz 2026." i samo otvara birač.
--}}
@php($mjesec = $value ? substr((string) $value, 0, 7) : '')

<input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ $mjesec }}" data-mjesec-vrijednost>
<input type="text" id="{{ $name }}_birac" readonly autocomplete="off" placeholder="odaberi mjesec"
       data-mjesec-birac="{{ $name }}" data-mjesec="{{ $mjesec }}" @required($required) {{ $attributes->merge(['class' => 'mjesec-birac']) }}>
<noscript><input type="month" name="{{ $name }}" value="{{ $mjesec }}" @required($required)></noscript>

@once
    @push('stilovi')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.10.4/dist/css/tempus-dominus.min.css">
        <style>
            .mjesec-birac { cursor: pointer; background-image: linear-gradient(transparent, transparent); }
            .td-ikona { font-style: normal; font-weight: 700; display: inline-block; width: 1em; text-align: center; }
            .td-ikona.lijevo::before { content: '‹'; }
            .td-ikona.desno::before { content: '›'; }
            .td-ikona.gore::before { content: '▲'; }
            .td-ikona.dolje::before { content: '▼'; }
            .td-ikona.danas::before { content: '●'; }
            .td-ikona.brisi::before { content: '✕'; }
            .td-ikona.zatvori::before { content: '✕'; }
            .td-ikona.kalendar::before { content: '▦'; }
            .td-ikona.sat::before { content: '◷'; }
        </style>
    @endpush
    @push('skripte')
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.10.4/dist/js/tempus-dominus.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.10.4/dist/locales/hr.js"></script>
        <script>
            (function () {
                const td = window.tempusDominus;
                if (!td) {
                    return;
                }

                td.loadLocale(td.locales.hr);
                td.locale(td.locales.hr.name);

                const dvijeZnamenke = (broj) => String(broj).padStart(2, '0');
                const prikaz = (datum) => datum.toLocaleDateString('hr-HR', { month: 'long', year: 'numeric' });

                document.querySelectorAll('[data-mjesec-birac]').forEach((polje) => {
                    const skriveno = document.getElementById(polje.dataset.mjesecBirac);
                    const [godina, mjesec] = (polje.dataset.mjesec || '').split('-').map(Number);
                    const pocetni = godina && mjesec ? new td.DateTime(godina, mjesec - 1, 1) : undefined;

                    const birac = new td.TempusDominus(polje, {
                        defaultDate: pocetni,
                        useCurrent: false,
                        allowInputToggle: true,
                        display: {
                            theme: 'auto',
                            viewMode: 'months',
                            components: { calendar: true, date: false, month: true, year: true, decades: true, clock: false },
                            buttons: { today: false, clear: false, close: false },
                            icons: {
                                type: 'icons',
                                time: 'td-ikona sat', date: 'td-ikona kalendar', up: 'td-ikona gore', down: 'td-ikona dolje',
                                previous: 'td-ikona lijevo', next: 'td-ikona desno', today: 'td-ikona danas',
                                clear: 'td-ikona brisi', close: 'td-ikona zatvori',
                            },
                        },
                        localization: {
                            nextYear: 'Sljedeća godina',
                            format: 'MMMM yyyy',
                            dayViewHeaderFormat: { month: 'long', year: 'numeric' },
                        },
                    });

                    const postavi = (datum) => {
                        skriveno.value = datum ? `${datum.getFullYear()}-${dvijeZnamenke(datum.getMonth() + 1)}` : '';
                        polje.value = datum ? prikaz(datum) : '';
                    };

                    postavi(pocetni);
                    birac.subscribe(td.Namespace.events.change, (dogadaj) => postavi(dogadaj.date));
                });
            })();
        </script>
    @endpush
@endonce
