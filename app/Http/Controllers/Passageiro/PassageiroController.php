<?php

namespace App\Http\Controllers\Passageiro;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Pagamento;
use App\Models\Reserva;
use App\Models\Rota;
use App\Models\Viagem;
use App\Services\LugarIndisponivel;
use App\Services\Pagamentos\PagamentoGateway;
use App\Services\PrazoExpirado;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PassageiroController extends Controller
{
    public function __construct(private ReservaService $reservas)
    {
    }

    private function minha(Reserva $reserva): Reserva
    {
        abort_unless((int) $reserva->passageiro_id === (int) auth()->id(), 403);

        return $reserva->load('viagem.rota.transportadora', 'viagem.autocarro', 'assento', 'bilhete', 'pagamento');
    }

    // ------------------------------------------------------------ pesquisa (RF06, RF07, RF08)

    public function inicio()
    {
        $rotas = Rota::where('estado', 'activa')->get(['origem', 'destino']);
        $mapa = $rotas->groupBy('origem')->map(fn ($g) => $g->pluck('destino')->unique()->sort()->values());

        $populares = DB::table('reservas')
            ->join('viagens', 'viagens.id', '=', 'reservas.viagem_id')
            ->join('rotas', 'rotas.id', '=', 'viagens.rota_id')
            ->where('reservas.estado', 'confirmada')
            ->selectRaw('rotas.origem, rotas.destino, count(*) as total')
            ->groupBy('rotas.origem', 'rotas.destino')
            ->orderByDesc('total')->limit(3)->get();

        return view('app.inicio', ['mapa' => $mapa, 'populares' => $populares]);
    }

    public function pesquisar(Request $request)
    {
        $dados = $request->validate([
            'origem' => ['required', 'string'],
            'destino' => ['required', 'string', 'different:origem'],
            'data' => ['required', 'date', 'after_or_equal:today'],
            'ordem' => ['nullable', 'in:hora,preco,empresa'],
        ], ['destino.different' => 'A origem e o destino têm de ser diferentes.']);

        $this->reservas->libertarExpiradas();

        $viagens = Viagem::futuras()
            ->whereDate('data', $dados['data'])
            ->where('estado', 'agendada')
            ->whereHas('rota', fn ($q) => $q->where('origem', $dados['origem'])->where('destino', $dados['destino'])->where('estado', 'activa'))
            ->with('rota.transportadora', 'autocarro')
            ->withCount(['reservas as ocupados_count' => fn ($q) => $q->where('ativa', 1)])
            ->get();

        $ordem = $dados['ordem'] ?? 'hora';
        $viagens = (match ($ordem) {
            'preco' => $viagens->sortBy('preco'),
            'empresa' => $viagens->sortBy(fn ($v) => $v->rota->transportadora->nome),
            default => $viagens->sortBy('hora_partida'),
        })->values();

        return view('app.resultados', ['viagens' => $viagens, 'q' => $dados, 'ordem' => $ordem]);
    }

    // ------------------------------------------------------------ escolher lugar (RF09)

    public function lugares(Viagem $viagem)
    {
        $this->reservas->libertarExpiradas();
        abort_if($viagem->estado !== 'agendada' || $viagem->partida_em->isPast(), 404);
        $viagem->load('rota.transportadora', 'autocarro.assentos');

        return view('app.lugares', ['viagem' => $viagem, 'ocupados' => $viagem->assentosOcupados()]);
    }

    public const MAX_LUGARES = 5;

    public function reservar(Request $request, Viagem $viagem)
    {
        $dados = $request->validate([
            'assento_ids' => ['required', 'array', 'min:1', 'max:' . self::MAX_LUGARES],
            'assento_ids.*' => ['integer', 'distinct'],
        ], [
            'assento_ids.required' => 'Escolha pelo menos um lugar no mapa.',
            'assento_ids.min' => 'Escolha pelo menos um lugar no mapa.',
            'assento_ids.max' => 'No máximo ' . self::MAX_LUGARES . ' lugares por compra.',
            'assento_ids.*.distinct' => 'Escolheu o mesmo lugar duas vezes.',
        ]);
        $user = $request->user();

        // Um passageiro só mantém uma compra por pagar de cada vez
        Reserva::where('passageiro_id', $user->id)->where('estado', 'pendente')->get()->each(fn ($r) => $this->reservas->libertar($r));

        try {
            $criadas = $this->reservas->bloquear($viagem, array_map('intval', $dados['assento_ids']), [
                'passageiro_id' => $user->id,
                'nome_passageiro' => $user->nome,
                'documento' => $user->documento,
                'telefone' => $user->telefone,
            ]);
        } catch (LugarIndisponivel $e) {
            return back()->withErrors(['assento_ids' => $e->getMessage()]);
        }

        return redirect()->route('app.pagamento', $criadas->first());
    }

    /** Todas as reservas da mesma compra (mesmo grupo) que pertencem ao passageiro, por ordem de lugar. */
    private function compra(Reserva $reserva, ?string $estado = null)
    {
        $q = Reserva::where('passageiro_id', auth()->id())
            ->when($reserva->grupo, fn ($q) => $q->where('grupo', $reserva->grupo), fn ($q) => $q->whereKey($reserva->id))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->with('viagem.rota.transportadora', 'viagem.autocarro', 'assento', 'bilhete', 'pagamento')
            ->orderBy('id');

        return $q->get();
    }

    // ------------------------------------------------------------ pagamento (RF10)

    public function pagamento(Reserva $reserva)
    {
        $reserva = $this->minha($reserva);
        if ($reserva->estado === 'confirmada') {
            return $this->compra($reserva, 'confirmada')->count() > 1
                ? redirect()->route('app.resultado', $reserva)
                : redirect()->route('app.bilhete', $reserva);
        }
        if ($reserva->estado !== 'pendente' || $reserva->expira_em->isPast()) {
            $this->reservas->libertarExpiradas();

            return redirect()->route('app.inicio')->with('erro', 'O tempo da reserva expirou. Escolha o lugar novamente.');
        }

        $compra = $this->compra($reserva, 'pendente');

        return view('app.pagamento', ['reserva' => $reserva, 'compra' => $compra, 'total' => $compra->sum(fn ($r) => $r->viagem->preco)]);
    }

    public function pagar(Request $request, Reserva $reserva, PagamentoGateway $gateway)
    {
        $reserva = $this->minha($reserva);
        $dados = $request->validate([
            'metodo' => ['required', 'in:mpesa,emola,cartao'],
            'telefone' => ['required_if:metodo,mpesa,emola', 'nullable', 'string', 'max:30'],
            'nomes' => ['nullable', 'array'],
            'nomes.*' => ['nullable', 'string', 'max:120'],
        ], ['telefone.required_if' => 'Indique o número de telefone para pagamento.']);

        if ($reserva->estado === 'confirmada') {
            return redirect()->route('app.resultado', $reserva);
        }
        if ($reserva->estado !== 'pendente' || $reserva->expira_em->isPast()) {
            $this->reservas->libertarExpiradas();

            return redirect()->route('app.inicio')->with('erro', 'O tempo da reserva expirou. Escolha o lugar novamente.');
        }

        $compra = $this->compra($reserva, 'pendente');
        $titular = $request->user();

        // Nome de cada passageiro (opcional). Se for diferente do titular da conta, o documento do titular não é copiado.
        foreach ($compra as $r) {
            $nome = trim((string) ($dados['nomes'][$r->id] ?? ''));
            if ($nome !== '' && $nome !== $r->nome_passageiro) {
                $r->update(['nome_passageiro' => $nome, 'documento' => $nome === $titular->nome ? $titular->documento : null]);
            }
        }

        $total = $compra->sum(fn ($r) => $r->viagem->preco);
        $resp = $gateway->cobrar($total, $dados['metodo'], $dados['telefone'] ?? null, 'RES-' . $compra->first()->id);

        if (! $resp['ok']) {
            $this->reservas->registarFalha($compra->first(), $dados['metodo'], $resp['codigo'], $resp['referencia']);

            return redirect()->route('app.resultado', $reserva);
        }

        try {
            // Tudo ou nada: se um dos lugares já expirou, nenhum bilhete da compra fica emitido.
            DB::transaction(function () use ($compra, $dados, $resp) {
                foreach ($compra as $r) {
                    $this->reservas->confirmar($r, $dados['metodo'], $resp['referencia']);
                }
            });
        } catch (LugarIndisponivel $e) {
            $this->reservas->libertarExpiradas();

            return redirect()->route('app.inicio')->with('erro', $e->getMessage());
        }
        $rotulos = $compra->map(fn ($r) => $r->assento->rotulo)->implode(', ');
        Auditoria::registar('Reserva paga', "{$reserva->viagem->rota->nome} · lugar(es) {$rotulos}");

        return redirect()->route('app.resultado', $reserva);
    }

    public function resultado(Reserva $reserva)
    {
        $reserva = $this->minha($reserva);
        if ($reserva->estado === 'confirmada') {
            $compra = $this->compra($reserva, 'confirmada');

            return view('app.pagamento-ok', [
                'reserva' => $reserva,
                'compra' => $compra,
                'total' => $compra->sum(fn ($r) => (float) ($r->pagamento->valor ?? $r->viagem->preco)),
            ]);
        }
        $falha = Pagamento::whereIn('reserva_id', $this->compra($reserva)->pluck('id'))->where('estado', 'falhado')->latest('id')->first();

        return view('app.pagamento-falha', ['reserva' => $reserva, 'falha' => $falha]);
    }

    // ------------------------------------------------------------ bilhetes (RF11, RF13)

    public function bilhete(Reserva $reserva)
    {
        $reserva = $this->minha($reserva);
        abort_unless(in_array($reserva->estado, ['confirmada', 'cancelada'], true) && $reserva->bilhete, 404);

        return view('app.bilhete', [
            'reserva' => $reserva,
            'podeAlterar' => $reserva->estado === 'confirmada' && $this->reservas->dentroDoPrazo($reserva),
            'horasLimite' => $this->reservas->horasLimiteCancelamento(),
        ]);
    }

    public function bilhetes(Request $request)
    {
        $todas = $request->user()->reservas()->where('estado', 'confirmada')
            ->with('viagem.rota.transportadora', 'assento', 'bilhete')->get();
        $proximas = $todas->filter(fn ($r) => $r->viagem->partida_em->isFuture())->sortBy(fn ($r) => $r->viagem->partida_em)->values();
        $passadas = $todas->filter(fn ($r) => ! $r->viagem->partida_em->isFuture())->sortByDesc(fn ($r) => $r->viagem->partida_em)->values();

        return view('app.bilhetes', ['proximas' => $proximas, 'passadas' => $passadas, 'tab' => $request->query('tab', 'proximas')]);
    }

    public function historico(Request $request)
    {
        $reservas = $request->user()->reservas()->whereIn('estado', ['confirmada', 'cancelada', 'expirada'])
            ->with('viagem.rota', 'pagamento')->get()
            ->sortByDesc(fn ($r) => $r->viagem->partida_em)->values();
        $porAno = $reservas->groupBy(fn ($r) => $r->viagem->data->year);

        return view('app.historico', ['porAno' => $porAno]);
    }

    // ------------------------------------------------------------ cancelar / remarcar (RF12)

    public function cancelar(Reserva $reserva)
    {
        $reserva = $this->minha($reserva);
        try {
            $this->reservas->cancelar($reserva);
        } catch (PrazoExpirado $e) {
            return back()->with('erro', $e->getMessage());
        }
        Auditoria::registar('Reserva cancelada', "{$reserva->viagem->rota->nome} · {$reserva->bilhete->codigo_qr}", 'laranja');

        return redirect()->route('app.cancelada')->with('valor_reembolso', $reserva->viagem->preco);
    }

    public function cancelada(Request $request)
    {
        $valor = $request->session()->get('valor_reembolso');
        if (! $valor) {
            return redirect()->route('app.bilhetes');
        }

        return view('app.cancelada', ['valor' => $valor]);
    }

    public function remarcar(Reserva $reserva)
    {
        $reserva = $this->minha($reserva);
        if (! $this->reservas->dentroDoPrazo($reserva) || $reserva->estado !== 'confirmada') {
            return redirect()->route('app.bilhete', $reserva)->with('erro', "Já não é possível remarcar esta reserva ({$this->reservas->horasLimiteCancelamento()} horas antes da partida).");
        }
        $this->reservas->libertarExpiradas();

        $alternativas = Viagem::futuras()->where('estado', 'agendada')
            ->where('rota_id', $reserva->viagem->rota_id)->where('preco', $reserva->viagem->preco)
            ->where('id', '!=', $reserva->viagem_id)
            ->whereDate('data', '<=', now()->addDays(30))
            ->with('rota', 'autocarro')
            ->withCount(['reservas as ocupados_count' => fn ($q) => $q->where('ativa', 1)])
            ->orderBy('data')->orderBy('hora_partida')->get()
            ->filter(fn ($v) => $v->autocarro->capacidade_total - $v->ocupados_count > 0)->values();

        return view('app.remarcar', ['reserva' => $reserva, 'alternativas' => $alternativas]);
    }

    public function remarcarLugares(Reserva $reserva, Viagem $viagem)
    {
        $reserva = $this->minha($reserva);
        abort_unless((int) $viagem->rota_id === (int) $reserva->viagem->rota_id && $viagem->estado === 'agendada' && $viagem->partida_em->isFuture(), 404);
        $this->reservas->libertarExpiradas();
        $viagem->load('rota.transportadora', 'autocarro.assentos');

        return view('app.remarcar-lugares', ['reserva' => $reserva, 'viagem' => $viagem, 'ocupados' => $viagem->assentosOcupados()]);
    }

    public function remarcarConfirmar(Request $request, Reserva $reserva, Viagem $viagem)
    {
        $reserva = $this->minha($reserva);
        $dados = $request->validate(['assento_id' => ['required', 'integer']], ['assento_id.required' => 'Escolha um lugar no mapa.']);

        try {
            $this->reservas->remarcar($reserva, $viagem, (int) $dados['assento_id']);
        } catch (LugarIndisponivel|PrazoExpirado $e) {
            return back()->withErrors(['assento_id' => $e->getMessage()]);
        }
        Auditoria::registar('Reserva remarcada', "{$reserva->bilhete->codigo_qr} → {$viagem->data->format('d/m/Y')} {$viagem->hora}", 'laranja');

        return redirect()->route('app.bilhete', $reserva)->with('estado', 'Reserva remarcada com sucesso.');
    }
}
