<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Korisnik bez ijednog zapisa konstanti preusmjerava se na njihov unos.
 */
class ZahtijevajKonstante
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->imaKonstante()) {
            return redirect()->route('postavke.create')
                ->with('status', 'Dobro došli! Prije prvog obračuna unesite svoje konstante (osnovica, koeficijent, minuli rad…) s neke od zadnjih platnih lista.');
        }

        return $next($request);
    }
}
