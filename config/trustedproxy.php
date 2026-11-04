<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | IP adrese reverse proxyja (npr. Synology NAS i gateway Docker mreže),
    | odvojene zarezom. Prazno = ne vjeruje se nijednom proxyju (development).
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
