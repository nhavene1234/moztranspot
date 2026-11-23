<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Se o administrador bloquear a conta, a sessão em curso termina. */
class ContaActiva
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->estado === 'bloqueado') {
            $staff = $user->eStaff();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($staff ? 'painel.entrar' : 'entrar')
                ->withErrors(['email' => 'A sua conta foi bloqueada. Contacte o administrador.']);
        }

        return $next($request);
    }
}
