<?php

namespace App\Http\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Bilhete;
use App\Models\Motorista;
use App\Models\Utilizador;
use App\Models\Viagem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Motoristas e cobradores da transportadora (CRUD). */
class PessoalController extends Controller
{
    use ContextoTransportadora;

    /* ------------------------------------------------------------ motoristas */

    public function motoristas(Request $r)
    {
        $tid = $this->tid($r);
        $motoristas = Motorista::with('transportadora')->withCount('viagens')
            ->when($tid, fn ($q) => $q->where('transportadora_id', $tid))
            ->when($r->filled('q'), function ($q) use ($r) {
                $t = '%' . trim($r->query('q')) . '%';
                $q->where(fn ($w) => $w->where('nome', 'like', $t)->orWhere('carta_numero', 'like', $t)->orWhere('telefone', 'like', $t));
            })
            ->orderBy('nome')->get();

        return view('gestao.motoristas', $this->contexto($r, $tid) + compact('motoristas'));
    }

    private function validarMotorista(Request $r, bool $novo): array
    {
        return $r->validate([
            'transportadora_id' => [$novo && $r->user()->tem('ver_todas_transportadoras') ? 'required' : 'nullable', 'exists:transportadoras,id'],
            'nome' => ['required', 'string', 'max:120'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'documento' => ['nullable', 'string', 'max:30'],
            'carta_numero' => ['required', 'string', 'max:40'],
            'carta_categoria' => ['nullable', 'string', 'max:10'],
            'carta_validade' => ['nullable', 'date'],
        ], ['carta_numero.required' => 'Indique o número da carta de condução.']);
    }

    public function motoristaGuardar(Request $r)
    {
        $d = $this->validarMotorista($r, true);
        $m = Motorista::create(collect($d)->except('transportadora_id')->all() + ['transportadora_id' => $this->empresaDoNovo($r)]);
        Auditoria::registar('Motorista registado', $m->nome, 'laranja');

        return redirect()->route('gestao.motoristas')->with('estado', "Motorista {$m->nome} registado.");
    }

    public function motoristaActualizar(Request $r, Motorista $motorista)
    {
        $this->permitido($r, $motorista);
        $d = $this->validarMotorista($r, false);
        $motorista->update(collect($d)->except('transportadora_id')->all());
        Auditoria::registar('Motorista actualizado', $motorista->nome, 'laranja');

        return redirect()->route('gestao.motoristas')->with('estado', "Motorista {$motorista->nome} actualizado.");
    }

    public function motoristaEstado(Request $r, Motorista $motorista)
    {
        $this->permitido($r, $motorista);
        $motorista->update(['estado' => $motorista->estado === 'activo' ? 'inactivo' : 'activo']);
        Auditoria::registar($motorista->estado === 'activo' ? 'Motorista activado' : 'Motorista desactivado', $motorista->nome, 'laranja');

        return back()->with('estado', "Motorista {$motorista->nome}: " . ($motorista->estado === 'activo' ? 'activo.' : 'inactivo (não aparece ao programar viagens).'));
    }

    public function motoristaEliminar(Request $r, Motorista $motorista)
    {
        $this->permitido($r, $motorista);
        if ($motorista->viagens()->exists()) {
            return back()->with('erro', "{$motorista->nome} tem viagens associadas e não pode ser eliminado. Desactive-o.");
        }
        $nome = $motorista->nome;
        $motorista->delete();
        Auditoria::registar('Motorista eliminado', $nome, 'laranja');

        return back()->with('estado', "Motorista {$nome} eliminado.");
    }

    /* ------------------------------------------------------------ cobradores */

    private function cobradorDaEmpresa(Request $r, Utilizador $cobrador): Utilizador
    {
        abort_unless($cobrador->perfil === 'cobrador', 404);
        $this->permitido($r, $cobrador);

        return $cobrador;
    }

    public function cobradores(Request $r)
    {
        $tid = $this->tid($r);
        $cobradores = Utilizador::with('transportadora')->where('perfil', 'cobrador')
            ->when($tid, fn ($q) => $q->where('transportadora_id', $tid))
            ->when($r->filled('q'), function ($q) use ($r) {
                $t = '%' . trim($r->query('q')) . '%';
                $q->where(fn ($w) => $w->where('nome', 'like', $t)->orWhere('email', 'like', $t)->orWhere('telefone', 'like', $t));
            })
            ->orderBy('nome')->get();
        $viagens = Viagem::selectRaw('cobrador_id, count(*) as n')->whereIn('cobrador_id', $cobradores->pluck('id'))->groupBy('cobrador_id')->pluck('n', 'cobrador_id');

        return view('gestao.cobradores', $this->contexto($r, $tid) + compact('cobradores', 'viagens'));
    }

    public function cobradorGuardar(Request $r)
    {
        $d = $r->validate([
            'transportadora_id' => [$r->user()->tem('ver_todas_transportadoras') ? 'required' : 'nullable', 'exists:transportadoras,id'],
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:utilizadores,email'],
            'telefone' => ['nullable', 'string', 'max:30'],
        ], ['email.unique' => 'Já existe uma conta com este email.']);

        $senha = 'Moz' . random_int(1000, 9999) . Str::upper(Str::random(3)) . '!';
        $c = Utilizador::create([
            'nome' => $d['nome'], 'email' => $d['email'], 'telefone' => $d['telefone'] ?? null,
            'perfil' => 'cobrador', 'senha' => $senha, 'transportadora_id' => $this->empresaDoNovo($r),
        ]);
        Auditoria::registar('Cobrador criado', $c->nome, 'laranja');

        return redirect()->route('gestao.cobradores')->with('estado', "Cobrador {$c->nome} criado.")
            ->with('senha_temporaria', ['email' => $c->email, 'senha' => $senha]);
    }

    public function cobradorActualizar(Request $r, Utilizador $cobrador)
    {
        $this->cobradorDaEmpresa($r, $cobrador);
        $d = $r->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('utilizadores', 'email')->ignore($cobrador->id)],
            'telefone' => ['nullable', 'string', 'max:30'],
        ], ['email.unique' => 'Já existe uma conta com este email.']);
        $cobrador->update($d);
        Auditoria::registar('Cobrador actualizado', $cobrador->nome, 'laranja');

        return redirect()->route('gestao.cobradores')->with('estado', "Cobrador {$cobrador->nome} actualizado.");
    }

    public function cobradorEstado(Request $r, Utilizador $cobrador)
    {
        $this->cobradorDaEmpresa($r, $cobrador);
        $cobrador->update(['estado' => $cobrador->estado === 'bloqueado' ? 'activo' : 'bloqueado', 'tentativas_falhadas' => 0, 'bloqueado_ate' => null]);
        Auditoria::registar($cobrador->estado === 'bloqueado' ? 'Cobrador bloqueado' : 'Cobrador desbloqueado', $cobrador->nome, 'laranja');

        return back()->with('estado', "Cobrador {$cobrador->nome}: " . ($cobrador->estado === 'bloqueado' ? 'bloqueado (não consegue entrar).' : 'desbloqueado.'));
    }

    public function cobradorSenha(Request $r, Utilizador $cobrador)
    {
        $this->cobradorDaEmpresa($r, $cobrador);
        $senha = 'Moz' . random_int(1000, 9999) . Str::upper(Str::random(3)) . '!';
        $cobrador->update(['senha' => $senha, 'tentativas_falhadas' => 0, 'bloqueado_ate' => null]);
        Auditoria::registar('Palavra-passe de cobrador reposta', $cobrador->nome, 'laranja');

        return back()->with('estado', "Palavra-passe de {$cobrador->nome} reposta.")->with('senha_temporaria', ['email' => $cobrador->email, 'senha' => $senha]);
    }

    public function cobradorEliminar(Request $r, Utilizador $cobrador)
    {
        $this->cobradorDaEmpresa($r, $cobrador);
        if (Viagem::where('cobrador_id', $cobrador->id)->exists() || Bilhete::where('validado_por', $cobrador->id)->exists()) {
            return back()->with('erro', "{$cobrador->nome} tem viagens ou bilhetes validados no histórico e não pode ser eliminado. Bloqueie a conta.");
        }
        $nome = $cobrador->nome;
        $cobrador->delete();
        Auditoria::registar('Cobrador eliminado', $nome, 'laranja');

        return back()->with('estado', "Cobrador {$nome} eliminado.");
    }
}
