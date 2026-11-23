<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Definicao;
use App\Models\Permissao;
use App\Models\Transportadora;
use App\Models\Utilizador;
use App\Services\BackupService;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Painel do administrador: utilizadores, permissões, transportadoras, auditoria e segurança (RF23, RN17). */
class AdminController extends Controller
{
    // ------------------------------------------------------------ visão geral

    public function visao()
    {
        $porCidade = DB::table('reservas as r')
            ->join('viagens as v', 'v.id', '=', 'r.viagem_id')
            ->join('rotas as ro', 'ro.id', '=', 'v.rota_id')
            ->where('r.estado', 'confirmada')
            ->selectRaw('ro.destino as cidade, count(*) as n')->groupBy('ro.destino')->orderByDesc('n')->get();

        $transportadoras = Transportadora::where('estado', 'activa')->orderBy('nome')->get();
        $transaccoesHoje = DB::table('pagamentos')->where('estado', 'pago')->whereDate('data_pagamento', now())->count();

        try {
            DB::select('select 1');
            $baseOk = true;
        } catch (\Throwable $e) {
            $baseOk = false;
        }

        return view('admin.visao', [
            'totalUtilizadores' => Utilizador::count(),
            'novosMes' => Utilizador::where('created_at', '>=', now()->startOfMonth())->count(),
            'transportadoras' => $transportadoras,
            'transaccoesHoje' => $transaccoesHoje,
            'porCidade' => $porCidade,
            'baseOk' => $baseOk,
            'ultimoBackup' => Definicao::obter('ultimo_backup'),
        ]);
    }

    // ------------------------------------------------------------ utilizadores (RF23)

    public function utilizadores(Request $r)
    {
        $f = $r->validate([
            'q' => ['nullable', 'string', 'max:80'], 'perfil' => ['nullable', 'string'],
            'transportadora' => ['nullable', 'integer'], 'estado' => ['nullable', 'in:activo,bloqueado'],
        ]);

        $utilizadores = Utilizador::with('transportadora')
            ->when(! empty($f['q']), fn ($q) => $q->where(fn ($w) => $w->where('nome', 'like', "%{$f['q']}%")->orWhere('email', 'like', "%{$f['q']}%")))
            ->when(! empty($f['perfil']), fn ($q) => $q->where('perfil', $f['perfil']))
            ->when(! empty($f['transportadora']), fn ($q) => $q->where('transportadora_id', $f['transportadora']))
            ->when(! empty($f['estado']), fn ($q) => $q->where('estado', $f['estado']))
            ->orderBy('perfil')->orderBy('nome')
            ->paginate(10)->withQueryString();

        return view('admin.utilizadores', [
            'utilizadores' => $utilizadores,
            'transportadoras' => Transportadora::orderBy('nome')->get(),
            'total' => Utilizador::count(),
        ]);
    }

    private function senhaTemporaria(): string
    {
        return 'Moz' . random_int(1000, 9999) . Str::upper(Str::random(3)) . '!';
    }

    public function utilizadorGuardar(Request $r)
    {
        $d = $r->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:utilizadores,email'],
            'perfil' => ['required', Rule::in(array_keys(Utilizador::PERFIS))],
            'transportadora_id' => [Rule::requiredIf(in_array($r->input('perfil'), ['gestor', 'cobrador'], true)), 'nullable', 'exists:transportadoras,id'],
        ], ['transportadora_id.required' => 'Escolha a transportadora deste perfil.']);

        $senha = $this->senhaTemporaria();
        $u = Utilizador::create([
            'nome' => $d['nome'], 'email' => $d['email'], 'perfil' => $d['perfil'], 'senha' => $senha,
            'transportadora_id' => in_array($d['perfil'], ['administrador', 'passageiro'], true) ? null : ($d['transportadora_id'] ?? null),
            'agencia' => $d['perfil'] === 'bilheteiro' ? 'Maputo – Terminal Junta' : null,
        ]);
        Auditoria::registar('Utilizador criado', "{$u->nome} ({$u->rotuloPerfil()})", 'laranja');

        return back()->with('estado', "Utilizador {$u->nome} criado.")->with('senha_temporaria', ['email' => $u->email, 'senha' => $senha]);
    }

    public function utilizadorBloquear(Utilizador $utilizador)
    {
        if ($utilizador->id === auth()->id()) {
            return back()->with('erro', 'Não pode bloquear a sua própria conta.');
        }
        $bloquear = $utilizador->estado !== 'bloqueado';
        $utilizador->forceFill(['estado' => $bloquear ? 'bloqueado' : 'activo', 'tentativas_falhadas' => 0, 'bloqueado_ate' => null])->save();
        Auditoria::registar($bloquear ? 'Utilizador bloqueado' : 'Utilizador desbloqueado', $utilizador->nome, $bloquear ? 'vermelho' : 'laranja');

        return back()->with('estado', $bloquear ? "{$utilizador->nome} foi bloqueado." : "{$utilizador->nome} foi desbloqueado.");
    }

    public function utilizadorReporSenha(Utilizador $utilizador)
    {
        $senha = $this->senhaTemporaria();
        $utilizador->forceFill(['senha' => $senha, 'tentativas_falhadas' => 0, 'bloqueado_ate' => null])->save();
        Auditoria::registar('Palavra-passe reposta', $utilizador->nome, 'laranja');

        return back()->with('estado', "Palavra-passe de {$utilizador->nome} reposta.")->with('senha_temporaria', ['email' => $utilizador->email, 'senha' => $senha]);
    }

    // ------------------------------------------------------------ permissões (RNF06)

    public function permissoes()
    {
        $matriz = Permissao::all()->groupBy('chave')->map(fn ($g) => $g->keyBy('perfil'));

        return view('admin.permissoes', ['matriz' => $matriz]);
    }

    public function permissoesGuardar(Request $r)
    {
        $marcadas = (array) $r->input('perm', []);
        foreach (['bilheteiro', 'cobrador', 'gestor'] as $perfil) {
            foreach (Permissao::APLICAVEIS[$perfil] as $chave) {
                Permissao::updateOrCreate(['perfil' => $perfil, 'chave' => $chave], ['ativo' => isset($marcadas[$perfil][$chave])]);
            }
        }
        $this->garantirAdministrador();
        Auditoria::registar('Matriz de permissões alterada', null, 'laranja');

        return back()->with('estado', 'Permissões guardadas. Aplicam-se de imediato.');
    }

    public function permissoesRepor()
    {
        foreach (Permissao::PADRAO as $perfil => $activas) {
            foreach (array_keys(Permissao::CHAVES) as $chave) {
                Permissao::updateOrCreate(['perfil' => $perfil, 'chave' => $chave], ['ativo' => in_array($chave, $activas, true)]);
            }
        }
        $this->garantirAdministrador();
        Auditoria::registar('Matriz de permissões reposta para os valores por omissão', null, 'laranja');

        return back()->with('estado', 'Permissões repostas para os valores por omissão.');
    }

    /** O administrador mantém sempre todas as permissões (evita ficar sem acesso). */
    private function garantirAdministrador(): void
    {
        foreach (array_keys(Permissao::CHAVES) as $chave) {
            Permissao::updateOrCreate(['perfil' => 'administrador', 'chave' => $chave], ['ativo' => true]);
        }
        Permissao::limparCache();
    }

    // ------------------------------------------------------------ transportadoras

    public function transportadoras()
    {
        $lista = Transportadora::with(['gestor', 'autocarros'])->withCount(['rotas', 'autocarros'])->orderBy('nome')->get();

        return view('admin.transportadoras', ['transportadoras' => $lista]);
    }

    public function transportadoraGuardar(Request $r)
    {
        $d = $r->validate([
            'nome' => ['required', 'string', 'max:120', 'unique:transportadoras,nome'],
            'nuit' => ['nullable', 'string', 'max:20'],
            'contacto' => ['nullable', 'string', 'max:30'],
            'endereco' => ['nullable', 'string', 'max:200'],
        ]);
        $t = Transportadora::create($d + ['descricao' => 'Operadora Nacional']);
        Auditoria::registar('Transportadora adicionada', $t->nome, 'laranja');

        return back()->with('estado', "Transportadora {$t->nome} adicionada. Crie agora o utilizador gestor.");
    }

    // ------------------------------------------------------------ auditoria (RNF10)

    private function consultaAuditoria(Request $r)
    {
        $r->validate(['periodo' => ['nullable', 'in:hoje,7d,30d,todos'], 'utilizador' => ['nullable', 'string'], 'acao' => ['nullable', 'string']]);
        $periodo = $r->query('periodo', 'hoje');

        return Auditoria::query()
            ->when($periodo === 'hoje', fn ($q) => $q->where('created_at', '>=', now()->startOfDay()))
            ->when($periodo === '7d', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
            ->when($periodo === '30d', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->when($r->filled('utilizador'), fn ($q) => $q->where('utilizador_nome', $r->query('utilizador')))
            ->when($r->filled('acao'), fn ($q) => $q->where('acao', $r->query('acao')))
            ->orderByDesc('created_at')->orderByDesc('id');
    }

    public function auditoria(Request $r)
    {
        return view('admin.auditoria', [
            'logs' => $this->consultaAuditoria($r)->paginate(15)->withQueryString(),
            'periodo' => $r->query('periodo', 'hoje'),
            'utilizadores' => Auditoria::query()->whereNotNull('utilizador_nome')->distinct()->orderBy('utilizador_nome')->pluck('utilizador_nome'),
            'acoes' => Auditoria::query()->distinct()->orderBy('acao')->pluck('acao'),
        ]);
    }

    public function auditoriaCsv(Request $r)
    {
        $linhas = $this->consultaAuditoria($r)->limit(5000)->get();

        return response()->streamDownload(function () use ($linhas) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Data/Hora', 'Utilizador', 'Acção', 'Detalhe', 'IP'], ';');
            foreach ($linhas as $l) {
                fputcsv($f, [$l->created_at->format('d/m/Y H:i:s'), Csv::seguro($l->utilizador_nome), Csv::seguro($l->acao), Csv::seguro($l->detalhe), $l->ip], ';');
            }
            fclose($f);
        }, 'auditoria-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------ segurança e backup

    public function seguranca()
    {
        $dir = storage_path('app/backups');
        $backups = is_dir($dir) ? collect(glob($dir . '/*'))->sortDesc()->take(5)->map(fn ($p) => basename($p))->values() : collect();

        return view('admin.seguranca', [
            'def' => collect(array_keys(Definicao::PADRAO))->mapWithKeys(fn ($k) => [$k => Definicao::obter($k)]),
            'backups' => $backups,
            'driver' => DB::connection()->getDriverName(),
        ]);
    }

    public function segurancaGuardar(Request $r)
    {
        $d = $r->validate([
            'max_tentativas_login' => ['required', 'integer', 'min:3', 'max:10'],
            'minutos_bloqueio_login' => ['required', 'integer', 'min:1', 'max:120'],
            'minutos_bloqueio_lugar' => ['required', 'integer', 'min:3', 'max:60'],
            'horas_limite_cancelamento' => ['required', 'integer', 'min:0', 'max:168'],
        ]);
        foreach ($d as $k => $v) {
            Definicao::gravar($k, (string) $v);
        }
        Auditoria::registar('Definições de segurança alteradas', null, 'laranja');

        return back()->with('estado', 'Definições guardadas.');
    }

    public function backup(BackupService $backup)
    {
        try {
            $ficheiro = $backup->executar();
        } catch (\Throwable $e) {
            return back()->with('erro', 'Backup falhou: ' . $e->getMessage());
        }
        Definicao::gravar('ultimo_backup', now()->format('d/m/Y H:i'));
        Auditoria::registar('Backup executado', basename($ficheiro), 'verde');

        return back()->with('estado', 'Backup criado: ' . basename($ficheiro));
    }
}
