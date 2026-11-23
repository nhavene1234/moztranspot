<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Definicao;
use App\Models\Utilizador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function mostrar(Request $request)
    {
        return view($request->routeIs('painel.*') ? 'auth.painel.login' : 'auth.app.login');
    }

    public function entrar(Request $request)
    {
        $painel = $request->routeIs('painel.*');
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], ['password.required' => 'Introduza a palavra-passe.']);

        $voltar = fn () => back()->withInput($request->only('email'));
        $user = Utilizador::where('email', $dados['email'])->first();
        $max = max(1, Definicao::inteiro('max_tentativas_login'));
        $minutos = max(1, Definicao::inteiro('minutos_bloqueio_login'));

        // Conta bloqueada temporariamente por tentativas falhadas (RNF06)
        if ($user && $user->bloqueado_ate && $user->bloqueado_ate->isFuture()) {
            return $voltar()->with('bloqueio', now()->diffInSeconds($user->bloqueado_ate, false) + 1)
                ->with('bloqueio_max', $max);
        }

        $correcta = $user && Hash::check($dados['password'], $user->senha);

        if (! $correcta) {
            if ($user) {
                $user->tentativas_falhadas++;
                if ($user->tentativas_falhadas >= $max) {
                    $user->tentativas_falhadas = 0;
                    $user->bloqueado_ate = now()->addMinutes($minutos);
                    $user->save();
                    Auditoria::registar('Conta bloqueada temporariamente', "{$max} tentativas de login falhadas", 'vermelho', $user);

                    return $voltar()->with('bloqueio', $minutos * 60)->with('bloqueio_max', $max);
                }
                $user->save();
                $restam = $max - $user->tentativas_falhadas;

                return $voltar()->withErrors(['email' => "Email ou palavra-passe incorrectos. Restam {$restam} tentativa(s)."]);
            }

            return $voltar()->withErrors(['email' => 'Email ou palavra-passe incorrectos.']);
        }

        if ($user->estado === 'bloqueado') {
            return $voltar()->withErrors(['email' => 'A sua conta está bloqueada. Contacte o administrador.']);
        }
        if ($painel && ! $user->eStaff()) {
            return $voltar()->withErrors(['email' => 'Esta área é para a equipa das transportadoras. Use a aplicação do passageiro.']);
        }
        if (! $painel && $user->eStaff()) {
            return $voltar()->withErrors(['email' => 'Esta conta pertence à equipa. Entre pelo painel de acesso da equipa.']);
        }

        $user->forceFill(['tentativas_falhadas' => 0, 'bloqueado_ate' => null])->save();
        Auth::login($user, $request->boolean('lembrar'));
        $request->session()->regenerate();
        Auditoria::registar('Login realizado', null, 'verde', $user);

        return redirect()->intended($user->rotaInicial());
    }

    public function sair(Request $request)
    {
        $painel = (bool) $request->user()?->eStaff();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($painel ? 'painel.entrar' : 'entrar');
    }
}
