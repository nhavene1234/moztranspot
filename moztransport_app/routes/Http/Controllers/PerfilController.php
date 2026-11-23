<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** RF04: gerir os dados pessoais (todos os perfis). */
class PerfilController extends Controller
{
    public function mostrar(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('transportadora');

        return view($user->eStaff() ? 'perfil.painel' : 'perfil.app', ['user' => $user]);
    }

    public function actualizar(Request $request)
    {
        $user = $request->user();
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('utilizadores', 'email')->ignore($user->id)],
            'telefone' => ['nullable', 'string', 'max:30'],
            'documento' => ['nullable', 'string', 'max:40'],
        ]);
        $user->update($dados);
        Auditoria::registar('Dados pessoais actualizados', null, 'laranja');

        return back()->with('estado', 'Dados guardados.');
    }

    public function senha(Request $request)
    {
        $request->validate([
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], ['password_actual.required' => 'Introduza a palavra-passe actual.']);

        $request->user()->update(['senha' => $request->input('password')]);
        Auditoria::registar('Palavra-passe alterada', null, 'laranja');

        return back()->with('estado_senha', 'Palavra-passe actualizada.');
    }

    public function notificacoes(Request $request)
    {
        $request->user()->update(['notificacoes' => $request->boolean('notificacoes')]);

        return back();
    }
}
