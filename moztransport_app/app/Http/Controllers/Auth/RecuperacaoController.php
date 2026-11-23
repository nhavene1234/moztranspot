<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\CodigoRecuperacao;
use App\Models\Utilizador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

/**
 * RF03 / RN18: a recuperação é feita apenas pelo email associado à conta,
 * com um código de 6 dígitos válido durante 5 minutos.
 */
class RecuperacaoController extends Controller
{
    private const VALIDADE_MINUTOS = 5;

    private function ctx(Request $r): string
    {
        return $r->routeIs('painel.*') ? 'painel' : 'app';
    }

    private function rota(Request $r, string $nome): string
    {
        return route(($this->ctx($r) === 'painel' ? 'painel.' : '') . $nome);
    }

    public function formulario(Request $request)
    {
        return view('auth.' . $this->ctx($request) . '.recuperar');
    }

    public function enviar(Request $request)
    {
        $dados = $request->validate(['email' => ['required', 'email']]);
        $this->gerarCodigo($dados['email']);
        $request->session()->put('recuperar_email', $dados['email']);

        return redirect($this->rota($request, 'recuperar.codigo'));
    }

    public function reenviar(Request $request)
    {
        $email = $request->session()->get('recuperar_email');
        if (! $email) {
            return redirect($this->rota($request, 'recuperar.form'));
        }
        $this->gerarCodigo($email);

        return back()->with('estado', 'Enviámos um novo código.');
    }

    public function codigo(Request $request)
    {
        $email = $request->session()->get('recuperar_email');
        if (! $email) {
            return redirect($this->rota($request, 'recuperar.form'));
        }
        $rec = CodigoRecuperacao::where('email', $email)->whereNull('usado_em')->latest()->first();
        $segundos = $rec ? max(0, now()->diffInSeconds($rec->expira_em, false)) : 0;

        return view('auth.' . $this->ctx($request) . '.codigo', [
            'email' => $email,
            'segundos' => $segundos,
            'demo' => config('moz.demo') ? $request->session()->get('codigo_demo') : null,
        ]);
    }

    /** Passo 2 do painel da equipa: confirma só o código. */
    public function verificar(Request $request)
    {
        $request->validate(['codigo' => ['required', 'digits:6']]);
        if (! $this->codigoValido($request->session()->get('recuperar_email'), $request->input('codigo'))) {
            return back()->withErrors(['codigo' => 'Código inválido ou expirado.']);
        }
        $request->session()->put('recuperar_codigo', $request->input('codigo'));

        return redirect($this->rota($request, 'recuperar.nova'));
    }

    public function nova(Request $request)
    {
        if (! $request->session()->get('recuperar_email') || ! $request->session()->get('recuperar_codigo')) {
            return redirect($this->rota($request, 'recuperar.form'));
        }

        return view('auth.painel.redefinir');
    }

    public function redefinir(Request $request)
    {
        $email = $request->session()->get('recuperar_email');
        if (! $email) {
            return redirect($this->rota($request, 'recuperar.form'));
        }
        $request->validate([
            'codigo' => [$this->ctx($request) === 'app' ? 'required' : 'nullable', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $codigo = $request->input('codigo') ?: $request->session()->get('recuperar_codigo');

        $rec = $this->codigoValido($email, (string) $codigo);
        if (! $rec) {
            return back()->withErrors(['codigo' => 'Código inválido ou expirado.']);
        }

        $user = Utilizador::where('email', $email)->first();
        if ($user) {
            $user->forceFill(['senha' => $request->input('password'), 'tentativas_falhadas' => 0, 'bloqueado_ate' => null])->save();
            Auditoria::registar('Palavra-passe redefinida', null, 'laranja', $user);
        }
        $rec->update(['usado_em' => now()]);
        $request->session()->forget(['recuperar_email', 'recuperar_codigo', 'codigo_demo']);

        return redirect()->route($this->ctx($request) === 'painel' ? 'painel.entrar' : 'entrar')
            ->with('estado', 'Palavra-passe actualizada. Já pode entrar.');
    }

    private function codigoValido(?string $email, string $codigo): ?CodigoRecuperacao
    {
        if (! $email) {
            return null;
        }
        $rec = CodigoRecuperacao::where('email', $email)->whereNull('usado_em')->where('expira_em', '>', now())->latest()->first();

        return $rec && Hash::check($codigo, $rec->codigo_hash) ? $rec : null;
    }

    /** Resposta igual exista ou não a conta, para não revelar quem está registado. */
    private function gerarCodigo(string $email): void
    {
        session()->forget('codigo_demo');
        $user = Utilizador::where('email', $email)->first();
        if (! $user) {
            return;
        }
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        CodigoRecuperacao::where('email', $email)->whereNull('usado_em')->update(['usado_em' => now()]);
        CodigoRecuperacao::create([
            'email' => $email,
            'codigo_hash' => Hash::make($codigo),
            'expira_em' => now()->addMinutes(self::VALIDADE_MINUTOS),
        ]);

        try {
            Mail::raw("O seu código de recuperação MozTransport é {$codigo}. Expira em " . self::VALIDADE_MINUTOS . ' minutos.', function ($m) use ($email) {
                $m->to($email)->subject('Código de recuperação - MozTransport');
            });
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar email de recuperação: ' . $e->getMessage());
        }
        Log::info("Código de recuperação para {$email}: {$codigo}");

        if (config('moz.demo')) {
            session()->put('codigo_demo', $codigo);
        }
    }
}
