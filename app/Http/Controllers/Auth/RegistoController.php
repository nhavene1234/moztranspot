<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Utilizador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class RegistoController extends Controller
{
    public function mostrar()
    {
        return view('auth.app.registo');
    }

    /** RF01 + RN18: nome, email e palavra-passe são o mínimo. */
    public function criar(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', 'unique:utilizadores,email'],
            'documento' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'termos' => ['accepted'],
        ]);

        $user = Utilizador::create([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'telefone' => $dados['telefone'] ?? null,
            'documento' => $dados['documento'] ?? null,
            'senha' => $dados['password'],
            'perfil' => 'passageiro',
        ]);
        Auditoria::registar('Conta de passageiro criada', $user->email, 'verde', $user);

        return redirect()->route('conta.criada')->with('nome_novo', $user->primeiroNome());
    }

    public function criada(Request $request)
    {
        $nome = $request->session()->get('nome_novo');
        if (! $nome) {
            return redirect()->route('entrar');
        }
        $request->session()->keep('nome_novo');

        return view('auth.app.conta-criada', ['nome' => $nome]);
    }
}
