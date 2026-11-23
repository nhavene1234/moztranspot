<?php

namespace App\Http\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Models\Assento;
use App\Models\Auditoria;
use App\Models\Autocarro;
use App\Models\Reserva;
use App\Models\Rota;
use App\Models\Transportadora;
use App\Models\Utilizador;
use App\Models\Viagem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Painel do gestor (RF19–RF22, RN13–RN16). Um gestor só vê dados da sua transportadora;
 * o administrador (permissão "ver dados de todas as transportadoras") pode filtrar por qualquer uma.
 */
class GestaoController extends Controller
{
    /** Transportadora em análise: null = todas (só para quem tem permissão). */
    private function tid(Request $r): ?int
    {
        $u = $r->user();
        $pedida = $r->query('transportadora');

        if ($u->tem('ver_todas_transportadoras')) {
            return $pedida ? (int) $pedida : null;
        }
        abort_unless($u->transportadora_id, 403);
        abort_if($pedida && (int) $pedida !== (int) $u->transportadora_id, 403); // RN13

        return (int) $u->transportadora_id;
    }

    private function vendas(?int $tid)
    {
        return DB::table('pagamentos as p')
            ->join('reservas as r', 'r.id', '=', 'p.reserva_id')
            ->join('viagens as v', 'v.id', '=', 'r.viagem_id')
            ->join('rotas as ro', 'ro.id', '=', 'v.rota_id')
            ->where('p.estado', 'pago')
            ->when($tid, fn ($q) => $q->where('ro.transportadora_id', $tid));
    }

    private function contexto(Request $r, ?int $tid): array
    {
        return [
            'tid' => $tid,
            'empresa' => $tid ? Transportadora::find($tid) : null,
            'empresas' => $r->user()->tem('ver_todas_transportadoras') ? Transportadora::orderBy('nome')->get() : collect(),
        ];
    }

    private function intervalo(Request $r, int $diasPadrao): array
    {
        $r->validate(['de' => ['nullable', 'date'], 'ate' => ['nullable', 'date']]);
        $ate = $r->filled('ate') ? Carbon::parse($r->query('ate')) : Carbon::today();
        $de = $r->filled('de') ? Carbon::parse($r->query('de')) : $ate->copy()->subDays($diasPadrao - 1);
        if ($de->gt($ate)) {
            [$de, $ate] = [$ate, $de];
        }

        return [$de->startOfDay(), $ate->endOfDay()];
    }

    /** @return array{0: array<string>, 1: array<int>} */
    private function serieDiaria(Collection $porDia, Carbon $de, Carbon $ate, string $campo = 'total'): array
    {
        $labels = [];
        $valores = [];
        for ($d = $de->copy()->startOfDay(); $d->lte($ate); $d->addDay()) {
            $labels[] = $d->format('d/m');
            $valores[] = (int) ($porDia[$d->toDateString()]->$campo ?? 0);
        }

        return [$labels, $valores];
    }

    private function porDia(?int $tid, Carbon $de, Carbon $ate): Collection
    {
        return $this->vendas($tid)
            ->whereBetween('p.data_pagamento', [$de, $ate])
            ->selectRaw('date(p.data_pagamento) as dia, sum(p.valor) as total, count(*) as n')
            ->groupBy('dia')->get()->keyBy('dia');
    }

    // ------------------------------------------------------------ dashboard (RF19)

    public function dashboard(Request $r)
    {
        $tid = $this->tid($r);
        $hoje = Carbon::today();

        $ult7 = $this->porDia($tid, $hoje->copy()->subDays(6), $hoje->copy()->endOfDay());
        [, $serieReceita] = $this->serieDiaria($ult7, $hoje->copy()->subDays(6), $hoje->copy()->endOfDay());
        [, $serieBilhetes] = $this->serieDiaria($ult7, $hoje->copy()->subDays(6), $hoje->copy()->endOfDay(), 'n');
        $receitaHoje = end($serieReceita) ?: 0;
        $bilhetesHoje = end($serieBilhetes) ?: 0;
        $mediaAnterior = array_sum(array_slice($serieReceita, 0, 6)) / 6;
        $variacaoReceita = $mediaAnterior > 0 ? round(($receitaHoje - $mediaAnterior) / $mediaAnterior * 100) : null;
        $mediaBilhetes = array_sum(array_slice($serieBilhetes, 0, 6)) / 6;
        $variacaoBilhetes = $mediaBilhetes > 0 ? round(($bilhetesHoje - $mediaBilhetes) / $mediaBilhetes * 100) : null;

        $viagensHoje = Viagem::whereDate('data', $hoje)
            ->whereHas('rota', fn ($w) => $tid ? $w->where('transportadora_id', $tid) : $w)
            ->with('autocarro')
            ->withCount(['reservas as ocupados_count' => fn ($q) => $q->where('ativa', 1)])->get();
        $capacidade = $viagensHoje->sum(fn ($v) => $v->autocarro->capacidade_total);
        $ocupacao = $capacidade ? round($viagensHoje->sum('ocupados_count') / $capacidade * 100) : 0;
        $activas = $viagensHoje->where('estado', 'agendada')->count();

        $de30 = $hoje->copy()->subDays(29);
        $mes = $this->porDia($tid, $de30, $hoje->copy()->endOfDay());
        [$labels30, $valores30] = $this->serieDiaria($mes, $de30, $hoje->copy()->endOfDay());

        $porRota = $this->vendas($tid)->whereBetween('p.data_pagamento', [$de30, $hoje->copy()->endOfDay()])
            ->selectRaw('ro.origem, ro.destino, sum(p.valor) as total')->groupBy('ro.origem', 'ro.destino')
            ->orderByDesc('total')->limit(4)->get();

        $ultimas = $this->vendas($tid)->select('p.valor', 'p.data_pagamento', 'r.nome_passageiro', 'r.estado', 'ro.origem', 'ro.destino')
            ->orderByDesc('p.data_pagamento')->limit(6)->get();

        return view('gestao.dashboard', $this->contexto($r, $tid) + compact(
            'receitaHoje', 'bilhetesHoje', 'variacaoReceita', 'variacaoBilhetes', 'serieReceita', 'serieBilhetes',
            'ocupacao', 'activas', 'labels30', 'valores30', 'porRota', 'ultimas'
        ));
    }

    // ------------------------------------------------------------ vendas

    public function vendasLista(Request $r)
    {
        $tid = $this->tid($r);
        [$de, $ate] = $this->intervalo($r, 7);
        $r->validate(['estado' => ['nullable', 'in:confirmada,cancelada,expirada,pendente'], 'rota' => ['nullable', 'string'], 'q' => ['nullable', 'string', 'max:80']]);

        $consulta = Reserva::query()
            ->with('viagem.rota', 'assento', 'bilhete')
            ->whereHas('viagem.rota', function ($w) use ($tid, $r) {
                if ($tid) {
                    $w->where('transportadora_id', $tid);
                }
                if ($r->filled('rota')) {
                    [$o, $d] = array_pad(explode('|', $r->query('rota')), 2, '');
                    $w->where('origem', $o)->where('destino', $d);
                }
            })
            ->whereBetween('data_reserva', [$de, $ate])
            ->when($r->filled('estado'), fn ($q) => $q->where('estado', $r->query('estado')))
            ->when($r->filled('q'), function ($q) use ($r) {
                $t = '%' . $r->query('q') . '%';
                $q->where(fn ($w) => $w->where('nome_passageiro', 'like', $t)->orWhereHas('bilhete', fn ($b) => $b->where('codigo_qr', 'like', $t)));
            })
            ->orderByDesc('data_reserva');

        $rotas = Rota::when($tid, fn ($q) => $q->where('transportadora_id', $tid))->get(['origem', 'destino'])
            ->map(fn ($x) => $x->origem . '|' . $x->destino)->unique()->sort()->values();

        return view('gestao.vendas', $this->contexto($r, $tid) + [
            'reservas' => $consulta->paginate(15)->withQueryString(),
            'de' => $de, 'ate' => $ate, 'rotas' => $rotas,
        ]);
    }

    // ------------------------------------------------------------ relatórios (RF20, RN14)

    private function dadosRelatorio(Request $r, ?int $tid): array
    {
        [$de, $ate] = $this->intervalo($r, 30);
        $porDia = $this->porDia($tid, $de, $ate);
        [$labels, $valores] = $this->serieDiaria($porDia, $de, $ate);
        $dias = max(1, count($valores));
        $receita = array_sum($valores);
        $bilhetes = (int) $porDia->sum('n');

        $porRota = $this->vendas($tid)->whereBetween('p.data_pagamento', [$de, $ate])
            ->selectRaw('ro.origem, ro.destino, sum(p.valor) as total, count(*) as bilhetes, count(distinct v.id) as viagens')
            ->groupBy('ro.origem', 'ro.destino')->orderByDesc('total')->get();

        return compact('de', 'ate', 'labels', 'valores', 'receita', 'bilhetes', 'dias', 'porRota') + ['mediaDiaria' => (int) round($receita / $dias)];
    }

    public function relatorios(Request $r)
    {
        $tid = $this->tid($r);

        return view('gestao.relatorios', $this->contexto($r, $tid) + $this->dadosRelatorio($r, $tid) + ['imprimir' => $r->boolean('imprimir')]);
    }

    public function exportarRelatorio(Request $r)
    {
        $tid = $this->tid($r);
        $d = $this->dadosRelatorio($r, $tid);

        return response()->streamDownload(function () use ($d) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Relatório financeiro', $d['de']->format('d/m/Y') . ' a ' . $d['ate']->format('d/m/Y')], ';');
            fputcsv($f, ['Receita total (MT)', $d['receita']], ';');
            fputcsv($f, ['Bilhetes vendidos', $d['bilhetes']], ';');
            fputcsv($f, [], ';');
            fputcsv($f, ['Rota', 'Viagens', 'Bilhetes', 'Receita (MT)'], ';');
            foreach ($d['porRota'] as $x) {
                fputcsv($f, [$x->origem . ' → ' . $x->destino, $x->viagens, $x->bilhetes, $x->total], ';');
            }
            fputcsv($f, [], ';');
            fputcsv($f, ['Dia', 'Receita (MT)'], ';');
            foreach ($d['labels'] as $i => $l) {
                fputcsv($f, [$l, $d['valores'][$i]], ';');
            }
            fclose($f);
        }, 'relatorio-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------ estatísticas (RF21, RN15)

    public function estatisticas(Request $r)
    {
        $tid = $this->tid($r);
        $hoje = Carbon::today();

        // Só conta bilhetes efectivamente vendidos (RN15), nunca pesquisas
        $base = fn () => DB::table('reservas as r')
            ->join('viagens as v', 'v.id', '=', 'r.viagem_id')
            ->join('rotas as ro', 'ro.id', '=', 'v.rota_id')
            ->where('r.estado', 'confirmada')
            ->when($tid, fn ($q) => $q->where('ro.transportadora_id', $tid));

        $rotas = $base()->whereDate('v.data', '>=', $hoje->copy()->subDays(90))->whereDate('v.data', '<=', $hoje)
            ->selectRaw('ro.origem, ro.destino, count(*) as n')->groupBy('ro.origem', 'ro.destino')->orderByDesc('n')->limit(6)->get();
        $totalRotas = max(1, $rotas->sum('n'));

        // Densidade por dia da semana e horário
        $pares = $base()->whereDate('v.data', '>=', $hoje->copy()->subDays(90))->whereDate('v.data', '<=', $hoje)
            ->selectRaw('v.data, v.hora_partida, count(*) as n')->groupBy('v.data', 'v.hora_partida')->get();
        $faixas = [4 => '04h', 6 => '06h', 9 => '09h', 13 => '13h', 16 => '16h', 19 => '19h'];
        $mapa = [];
        foreach ($faixas as $h => $_) {
            $mapa[$h] = array_fill(1, 7, 0);
        }
        foreach ($pares as $p) {
            $hora = (int) substr($p->hora_partida, 0, 2);
            $faixa = 4;
            foreach (array_keys($faixas) as $h) {
                if ($hora >= $h) {
                    $faixa = $h;
                }
            }
            $mapa[$faixa][Carbon::parse($p->data)->dayOfWeekIso] += $p->n;
        }
        $maxCalor = max(1, max(array_map('max', $mapa)));

        // Sazonalidade: bilhetes por mês nos últimos 12 meses
        $mensal = array_fill_keys(
            collect(range(11, 0))->map(fn ($i) => $hoje->copy()->startOfMonth()->subMonthsNoOverflow($i)->format('Y-m'))->all(),
            0
        );
        $porData = $base()->whereDate('v.data', '>=', $hoje->copy()->startOfMonth()->subMonthsNoOverflow(11))
            ->whereDate('v.data', '<=', $hoje)
            ->selectRaw('v.data, count(*) as n')->groupBy('v.data')->get();
        foreach ($porData as $x) {
            $k = substr($x->data, 0, 7);
            if (isset($mensal[$k])) {
                $mensal[$k] += $x->n;
            }
        }
        $picoMes = array_search(max($mensal), $mensal, true);

        return view('gestao.estatisticas', $this->contexto($r, $tid) + compact('rotas', 'totalRotas', 'mapa', 'faixas', 'maxCalor', 'mensal', 'picoMes'));
    }

    // ------------------------------------------------------------ rotas e horários (RF22, RN16)

    private function rotaPermitida(Request $r, Rota $rota): void
    {
        $u = $r->user();
        if (! $u->tem('ver_todas_transportadoras')) {
            abort_unless((int) $rota->transportadora_id === (int) $u->transportadora_id, 403);
        }
    }

    public function rotas(Request $r)
    {
        $tid = $this->tid($r);
        $rotas = Rota::with('transportadora')->when($tid, fn ($q) => $q->where('transportadora_id', $tid))
            ->orderBy('transportadora_id')->orderBy('origem')->orderBy('destino')->get();

        // reservas confirmadas futuras por rota (aviso ao desactivar)
        $futuras = DB::table('reservas as r')->join('viagens as v', 'v.id', '=', 'r.viagem_id')
            ->where('r.estado', 'confirmada')->whereDate('v.data', '>=', now())
            ->selectRaw('v.rota_id, count(*) as n')->groupBy('v.rota_id')->pluck('n', 'rota_id');

        $autocarros = Autocarro::where('estado', 'activo')->when($tid, fn ($q) => $q->where('transportadora_id', $tid))->orderBy('matricula')->get();
        $motoristas = \App\Models\Motorista::where('estado', 'activo')->when($tid, fn ($q) => $q->where('transportadora_id', $tid))->orderBy('nome')->get();
        $cobradores = Utilizador::where('perfil', 'cobrador')->where('estado', 'activo')->when($tid, fn ($q) => $q->where('transportadora_id', $tid))->orderBy('nome')->get();

        return view('gestao.rotas', $this->contexto($r, $tid) + compact('rotas', 'futuras', 'autocarros', 'cobradores', 'motoristas'));
    }

    private function validarRota(Request $r): array
    {
        return $r->validate([
            'transportadora_id' => [$r->user()->tem('ver_todas_transportadoras') ? 'required' : 'nullable', 'exists:transportadoras,id'],
            'origem' => ['required', 'string', 'max:60'],
            'destino' => ['required', 'string', 'max:60', 'different:origem'],
            'distancia_km' => ['required', 'integer', 'min:1', 'max:9999'],
            'horas' => ['required', 'integer', 'min:0', 'max:99'],
            'minutos' => ['required', 'integer', 'min:0', 'max:59'],
            'preco_base' => ['required', 'integer', 'min:1', 'max:999999'],
        ], ['destino.different' => 'A origem e o destino têm de ser diferentes.']);
    }

    public function rotaGuardar(Request $r)
    {
        $d = $this->validarRota($r);
        $empresa = $r->user()->tem('ver_todas_transportadoras') ? $d['transportadora_id'] : $r->user()->transportadora_id;
        abort_unless($empresa, 403);

        $rota = Rota::create([
            'transportadora_id' => $empresa, 'origem' => trim($d['origem']), 'destino' => trim($d['destino']),
            'distancia_km' => $d['distancia_km'], 'duracao_minutos' => $d['horas'] * 60 + $d['minutos'], 'preco_base' => $d['preco_base'],
        ]);
        Auditoria::registar('Rota criada', $rota->nome, 'laranja');

        return back()->with('estado', "Rota {$rota->nome} criada. Programe agora as viagens (horários) abaixo.");
    }

    public function rotaActualizar(Request $r, Rota $rota)
    {
        $this->rotaPermitida($r, $rota);
        $d = $this->validarRota($r);
        $rota->update([
            'origem' => trim($d['origem']), 'destino' => trim($d['destino']), 'distancia_km' => $d['distancia_km'],
            'duracao_minutos' => $d['horas'] * 60 + $d['minutos'], 'preco_base' => $d['preco_base'],
        ]);
        Auditoria::registar('Rota alterada', $rota->nome, 'laranja');

        return back()->with('estado', 'Rota actualizada. As viagens e reservas já existentes mantêm o preço com que foram criadas.');
    }

    /** Desactivar só bloqueia novas vendas; reservas confirmadas continuam válidas (RN16). */
    public function rotaEstado(Request $r, Rota $rota)
    {
        $this->rotaPermitida($r, $rota);
        $rota->update(['estado' => $rota->estado === 'activa' ? 'desactivada' : 'activa']);
        Auditoria::registar($rota->estado === 'activa' ? 'Rota activada' : 'Rota desactivada', $rota->nome, 'laranja');

        return back()->with('estado', "Rota {$rota->nome}: " . ($rota->estado === 'activa' ? 'activada.' : 'desactivada para novas vendas. As reservas confirmadas mantêm-se válidas.'));
    }

    public function viagemGuardar(Request $r)
    {
        $d = $r->validate([
            'rota_id' => ['required', 'exists:rotas,id'],
            'autocarro_id' => ['required', 'exists:autocarros,id'],
            'cobrador_id' => ['nullable', 'exists:utilizadores,id'],
            'motorista_id' => ['nullable', 'exists:motoristas,id'],
            'hora' => ['required', 'date_format:H:i'],
            'classe' => ['required', 'string', 'max:40'],
            'preco' => ['required', 'integer', 'min:1', 'max:999999'],
            'data_inicio' => ['required', 'date', 'after_or_equal:today'],
            'dias' => ['required', 'integer', 'min:1', 'max:60'],
        ]);
        $rota = Rota::findOrFail($d['rota_id']);
        $this->rotaPermitida($r, $rota);
        $bus = Autocarro::findOrFail($d['autocarro_id']);
        if ((int) $bus->transportadora_id !== (int) $rota->transportadora_id) {
            return back()->withErrors(['autocarro_id' => 'O autocarro tem de pertencer à mesma transportadora da rota.'])->withInput();
        }
        if ($bus->estado !== 'activo') {
            return back()->withErrors(['autocarro_id' => 'Este autocarro está desactivado.'])->withInput();
        }
        if (! empty($d['motorista_id'])) {
            $motorista = \App\Models\Motorista::find($d['motorista_id']);
            if (! $motorista || (int) $motorista->transportadora_id !== (int) $rota->transportadora_id || $motorista->estado !== 'activo') {
                return back()->withErrors(['motorista_id' => 'O motorista tem de estar activo e ser da mesma transportadora da rota.'])->withInput();
            }
            if ($motorista->carta_validade && $motorista->carta_validade->endOfDay()->lt(Carbon::parse($d['data_inicio']))) {
                return back()->withErrors(['motorista_id' => 'A carta de condução deste motorista caduca antes da data da viagem.'])->withInput();
            }
        }
        if (! empty($d['cobrador_id'])) {
            $cobrador = Utilizador::find($d['cobrador_id']);
            if (! $cobrador || $cobrador->perfil !== 'cobrador' || (int) $cobrador->transportadora_id !== (int) $rota->transportadora_id) {
                return back()->withErrors(['cobrador_id' => 'O cobrador tem de ser da mesma transportadora da rota.'])->withInput();
            }
        }

        $criadas = 0;
        for ($i = 0; $i < $d['dias']; $i++) {
            $dia = Carbon::parse($d['data_inicio'])->addDays($i)->toDateString();
            $existe = Viagem::where('rota_id', $rota->id)->where('autocarro_id', $bus->id)->whereDate('data', $dia)->where('hora_partida', $d['hora'] . ':00')->exists();
            if (! $existe) {
                Viagem::create([
                    'data' => $dia, 'hora_partida' => $d['hora'] . ':00', 'preco' => $d['preco'], 'classe' => $d['classe'],
                    'estado' => 'agendada', 'rota_id' => $rota->id, 'autocarro_id' => $bus->id, 'cobrador_id' => $d['cobrador_id'] ?? null, 'motorista_id' => $d['motorista_id'] ?? null,
                ]);
                $criadas++;
            }
        }
        Auditoria::registar('Viagens programadas', "{$rota->nome} · {$d['hora']} · {$criadas} dia(s)", 'laranja');

        return back()->with('estado', "{$criadas} viagem(ns) programada(s) para {$rota->nome} às {$d['hora']}.");
    }
}
