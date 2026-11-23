<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Uso: ->middleware('permissao:gerir_rotas'). Consulta a matriz de permissões (RNF06). */
class ExigePermissao
{
    public function handle(Request $request, Closure $next, string $permissao)
    {
        $user = $request->user();
        abort_unless($user && $user->tem($permissao), 403);

        return $next($request);
    }
}
