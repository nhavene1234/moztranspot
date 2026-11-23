<?php

namespace App\Http\Controllers\Bilheteiro;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Support\Csv;
use App\Models\Pagamento;
use App\Models\Reserva;
use App\Models\Rota;
use App\Models\Viagem;
use App\Services\LugarIndisponivel;
use App\Services\Pagamentos\PagamentoGateway;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Módulo do bilheteiro: venda presencial em 3 passos (RF14, RF15, RF16). */
class BilheteiraController extends Controller
{
    public function __construct(private ReservaService $reservas)
    {
    }

    // ------------------------------------------------------------ passo 1: escolher viagem

    public function vender(Request $request)
    {
        $this->reservas->libertarExpiradas();
        $dados = $request->validate([
            'origem' => ['nullable', 'string'],
            'destino' => ['nullable', 'string'],
            'data' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $origem = $dados['origem'] ?? 'Maputo';
        $data = $dados['data'] ?? now()->toDateString();

        $viagens = Viagem::futuras()->where('estado', 'agendada')->whereDate('data', $data)
            ->whereHas('rota', function ($q) use ($origem, $dados) {
                $q->where('estado', 'activa')->where('origem', $origem);
                if (! empty($dados['destino'])) {
                    $q->where('destino', $dados['destino']);
                }
            })
            ->with('rota.transportadora', 'autocarro')
            ->withCount(['reservas as ocupados_count' => fn ($q) => $q->where('ativa', 1)])
            ->get()->sortBy('hora_partida')->values();

        $rotas = Rota::where('estado', 'activa')->get(['origem', 'destino']);

        return view('bilheteira.vender', [
            'viagens' => $viagens,
            'origens' => $rotas->pluck('origem')->unique()->sort()->values(),
            'destinos' => $rotas->where('origem', $origem)->pluck('destino')->unique()->sort()->values(),
            'origem' => $origem, 'destino' => $dados['destino'] ?? '', 'data' => $data,
            'hoje' => $data === now()->toDateString(),
        ]);
    }

    // ------------------------------------------------------------ passo 2: lugares

    public function lugares(Viagem $viagem)
    {
        $this->reservas->libertarExpiradas();
        abort_if($viagem->estado !== 'agendada' || $viagem->partida_em->isPast(), 404);
        $viagem->load('rota.transportadora', 'autocarro.assentos');

        return view('bilheteira.lugares', ['viagem' => $viagem, 'ocupados' => $viagem->assentosOcupados()]);
    }

    public function bloquear(Request $request, Viagem $viagem)
    {
        $dados = $request->validate([
            'assento_ids' => ['required', 'array', 'min:1', 'max:5'],
            'assento_ids.*' => ['integer'],
        ], ['assento_ids.required' => 'Seleccione pelo menos um lugar no mapa.', 'assento_ids.max' => 'No máximo 5 lugares por venda.']);

        $this->libertarVendaAnterior($request);

        try {
            $criadas = $this->reservas->bloquear($viagem, $dados['assento_ids'], ['bilheteiro_id' => $request->user()->id, 'nome_passageiro' => '—']);
        } catch (LugarIndisponivel $e) {
            return back()->withErrors(['assento_ids' => $e->getMessage()]);
        }
        $request->session()->put('venda_reservas', $criadas->pluck('id')->all());

        return redirect()->route('bilheteira.pagamento');
    }

    private function libertarVendaAnterior(Request $request): void
    {
        $ids = $request->session()->pull('venda_reservas', []);
        if ($ids) {
            Reserva::whereIn('id', $ids)->get()->each(fn ($r) => $this->reservas->libertar($r));
        }
    }

    // ------------------------------------------------------------ passo 3: dados e pagamento

    private function vendaEmCurso(Request $request)
    {
        $ids = $request->session()->get('venda_reservas', []);
        $reservas = Reserva::whereIn('id', $ids)->where('estado', 'pendente')->where('expira_em', '>', now())
            ->with('viagem.rota.transportadora', 'assento')->get();

        return $reservas->isEmpty() ? null : $reservas;
    }

    public function pagamento(Request $request)
    {
        $reservas = $this->vendaEmCurso($request);
        if (! $reservas) {
            return redirect()->route('bilheteira.vender')->with('erro', 'O bloqueio dos lugares expirou. Seleccione de novo.');
        }

        return view('bilheteira.pagamento', ['reservas' => $reservas, 'viagem' => $reservas->first()->viagem]);
    }

    public function registar(Request $request, PagamentoGateway $gateway)
    {
        $reservas = $this->vendaEmCurso($request);
        if (! $reservas) {
            return redirect()->route('bilheteira.vender')->with('erro', 'O bloqueio dos lugares expirou. Seleccione de novo.');
        }
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'tipo_documento' => ['required', 'in:BI,Passaporte,Carta de condução,DIRE'],
            'documento' => ['required', 'string', 'max:40'],
            'telefone' => ['required', 'string', 'max:30'],
            'metodo' => ['required', 'in:numerario,mpesa,emola,cartao'],
            'telefone_pagamento' => ['required_if:metodo,mpesa,emola', 'nullable', 'string', 'max:30'],
        ], ['telefone_pagamento.required_if' => 'Indique o número do cliente para pedir o pagamento.']);

        $total = $reservas->sum(fn ($r) => $r->viagem->preco);
        $ref = 'NUM-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));

        if ($dados['metodo'] !== 'numerario') {
            $resp = $gateway->cobrar($total, $dados['metodo'], $dados['telefone_pagamento'] ?? null, 'VENDA-' . $reservas->first()->grupo);
            if (! $resp['ok']) {
                $this->reservas->registarFalha($reservas->first(), $dados['metodo'], $resp['codigo'], $resp['referencia']);

                return back()->withInput()->with('erro', ($resp['mensagem'] ?? 'Pagamento recusado.') . ' [' . $resp['codigo'] . ']');
            }
            $ref = $resp['referencia'];
        }

        try {
            // Tudo ou nada: se um dos lugares já expirou, nenhum bilhete da venda fica emitido.
            DB::transaction(function () use ($reservas, $dados, $ref) {
                foreach ($reservas as $r) {
                    $r->update([
                        'nome_passageiro' => $dados['nome'],
                        'documento' => $dados['tipo_documento'] . ' · ' . $dados['documento'],
                        'telefone' => $dados['telefone'],
                    ]);
                    $this->reservas->confirmar($r, $dados['metodo'], $ref);
                }
            });
        } catch (LugarIndisponivel $e) {
            return redirect()->route('bilheteira.vender')->with('erro', $e->getMessage());
        }

        $request->session()->forget('venda_reservas');
        $rotulos = $reservas->map(fn ($r) => $r->assento->rotulo)->implode(', ');
        Auditoria::registar('Venda registada', "{$reservas->first()->viagem->rota->nome} · lugar(es) {$rotulos}");

        return redirect()->route('bilheteira.venda', $reservas->first()->grupo)->with('sucesso', true);
    }

    /** Lugares vendidos numa operação. 404 se não existir; 403 se a venda for de outro bilheteiro. */
    private function reservasDaVenda(Request $request, string $grupo)
    {
        $reservas = Reserva::where('grupo', $grupo)->where('estado', 'confirmada')
            ->with('viagem.rota.transportadora', 'assento', 'bilhete', 'pagamento')->get();
        abort_if($reservas->isEmpty(), 404);
        abort_unless($request->user()->perfil === 'administrador' || (int) $reservas->first()->bilheteiro_id === (int) $request->user()->id, 403);

        return $reservas;
    }

    public function venda(Request $request, string $grupo)
    {
        $reservas = $this->reservasDaVenda($request, $grupo);

        return view('bilheteira.venda', ['reservas' => $reservas, 'viagem' => $reservas->first()->viagem]);
    }

    /** O envio é simulado: regista no log em vez de ligar a um operador de SMS. */
    public function sms(Request $request, string $grupo)
    {
        $dados = $request->validate(['telefone' => ['required', 'string', 'max:30']]);
        $codigos = $this->reservasDaVenda($request, $grupo)->pluck('bilhete.codigo_assinado')->filter()->implode(', ');
        Log::info("SMS (simulado) para {$dados['telefone']}: bilhete(s) {$codigos}");

        return back()->with('estado', "SMS enviado para {$dados['telefone']} (simulado).");
    }

    // ------------------------------------------------------------ lugares em tempo real (RF14)

    public function lugaresDisponiveis(Request $request)
    {
        $this->reservas->libertarExpiradas();
        $viagens = Viagem::futuras()->where('estado', 'agendada')->whereDate('data', '<=', now()->addDay())
            ->with('rota.transportadora', 'autocarro')
            ->withCount(['reservas as ocupados_count' => fn ($q) => $q->where('ativa', 1)])
            ->orderBy('data')->orderBy('hora_partida')->limit(40)->get();

        $escolhida = $request->query('viagem') ? Viagem::with('rota.transportadora', 'autocarro.assentos')->find($request->query('viagem')) : $viagens->first();
        if ($escolhida) {
            $escolhida->loadMissing('rota.transportadora', 'autocarro.assentos');
        }

        return view('bilheteira.lugares-disponiveis', [
            'viagens' => $viagens, 'viagem' => $escolhida,
            'ocupados' => $escolhida ? $escolhida->assentosOcupados() : [],
        ]);
    }

    public function estado(Viagem $viagem)
    {
        $this->reservas->libertarExpiradas();
        $ocupados = $viagem->assentosOcupados();

        return response()->json(['ocupados' => $ocupados, 'total' => $viagem->autocarro->capacidade_total, 'livres' => $viagem->autocarro->capacidade_total - count($ocupados)]);
    }

    // ------------------------------------------------------------ vendas do dia

    private function consultaVendas(Request $request)
    {
        $user = $request->user();

        return Pagamento::where('estado', 'pago')->whereDate('data_pagamento', now()->toDateString())
            ->whereHas('reserva', function ($q) use ($user) {
                if ($user->perfil !== 'administrador') {
                    $q->where('bilheteiro_id', $user->id);
                }
            });
    }

    public function vendasDoDia(Request $request)
    {
        $base = $this->consultaVendas($request);
        $total = (clone $base)->sum('valor');
        $quantidade = (clone $base)->count();
        $metodos = (clone $base)->select('metodo', DB::raw('count(*) as n'))->groupBy('metodo')->orderByDesc('n')->get();
        $topo = $metodos->first();

        $ontem = Pagamento::where('estado', 'pago')->whereDate('data_pagamento', now()->subDay()->toDateString())
            ->whereHas('reserva', fn ($q) => $request->user()->perfil === 'administrador' ? $q : $q->where('bilheteiro_id', $request->user()->id))
            ->sum('valor');
        $variacao = $ontem > 0 ? round(($total - $ontem) / $ontem * 100) : null;

        $vendas = (clone $base)->with('reserva.viagem.rota', 'reserva.assento')->orderByDesc('data_pagamento')->paginate(10)->withQueryString();

        return view('bilheteira.vendas', compact('vendas', 'total', 'quantidade', 'topo', 'variacao') + ['totalMetodos' => max(1, $quantidade)]);
    }

    public function exportar(Request $request)
    {
        $linhas = $this->consultaVendas($request)->with('reserva.viagem.rota', 'reserva.assento')->orderBy('data_pagamento')->get();

        return response()->streamDownload(function () use ($linhas) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Hora', 'Passageiro', 'Rota', 'Lugar', 'Valor (MT)', 'Método'], ';');
            foreach ($linhas as $p) {
                fputcsv($f, [$p->data_pagamento->format('H:i'), Csv::seguro($p->reserva->nome_passageiro), $p->reserva->viagem->rota->nome, $p->reserva->assento->rotulo, $p->valor, $p->rotuloMetodo()], ';');
            }
            fclose($f);
        }, 'vendas-do-dia-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
