<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Uso: ->middleware('perfil:gestor,administrador') */
class ExigePerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis)
    {
        $user = $request->user();
        abort_unless($user && in_array($user->perfil, $perfis, true), 403);

        return $next($request);
    }
}
