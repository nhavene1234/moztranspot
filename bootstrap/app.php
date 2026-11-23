<?php

use App\Http\Middleware\ContaActiva;
use App\Http\Middleware\ExigePerfil;
use App\Http\Middleware\ExigePermissao;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'perfil' => ExigePerfil::class,
            'permissao' => ExigePermissao::class,
            'activo' => ContaActiva::class,
        ]);

        // Visitante sem sessão: passageiros vão para /entrar, equipa para /painel/entrar
        $middleware->redirectGuestsTo(fn (Request $r) => $r->is('painel*', 'bilheteira*', 'cobrador*', 'gestao*', 'admin*')
            ? route('painel.entrar')
            : route('entrar'));

        // Utilizador com sessão que abre uma página de login: vai para a sua página inicial
        $middleware->redirectUsersTo(fn () => auth()->user()->rotaInicial());
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
