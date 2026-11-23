<?php

namespace App\Http\Controllers\Cobrador;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Bilhete;
use App\Models\Viagem;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Módulo do cobrador/motorista: validação por QR code e lista de passageiros (RF17, RF18). */
class CobradorController extends Controller
{
    /**
     * O cobrador pode trabalhar numa viagem se ela lhe estiver atribuída OU se for da sua transportadora
     * (viagens sem cobrador atribuído, ou em que o colega faltou, também precisam de validação).
     */
    private function minha(Viagem $viagem): Viagem
    {
        $u = auth()->user();
        $viagem->loadMissing('rota.transportadora', 'autocarro', 'motorista');
        $atribuida = (int) $viagem->cobrador_id === (int) $u->id;
        $daEmpresa = $u->transportadora_id && (int) $viagem->rota->transportadora_id === (int) $u->transportadora_id;
        abort_unless($atribuida || $daEmpresa, 403);

        return $viagem;
    }

    public function viagens(Request $request)
    {
        $u = $request->user();
        $dia = $request->filled('data') && strtotime((string) $request->query('data')) ? Carbon::parse($request->query('data')) : now();
        $viagens = Viagem::whereDate('data', $dia->toDateString())
            ->where(function ($q) use ($u) {
                $q->where('cobrador_id', $u->id);
                if ($u->transportadora_id) {
                    $q->orWhereHas('rota', fn ($r) => $r->where('transportadora_id', $u->transportadora_id));
                }
            })
            ->with('rota.transportadora', 'autocarro', 'motorista')
            ->withCount([
                'reservas as confirmados_count' => fn ($q) => $q->where('estado', 'confirmada'),
                'reservas as validados_count' => fn ($q) => $q->where('estado', 'confirmada')->whereHas('bilhete', fn ($b) => $b->where('estado', 'usado')),
            ])
            ->orderBy('hora_partida')->get();

        return view('cobrador.viagens', ['dia' => $dia, 'minhas' => $viagens->where('cobrador_id', $u->id), 'outras' => $viagens->where('cobrador_id', '!=', $u->id)]);
    }

    public function viagem(Viagem $viagem)
    {
        $viagem = $this->minha($viagem);
        $passageiros = $viagem->reservas()->where('estado', 'confirmada')->with('assento', 'bilhete')->get()
            ->sortBy(fn ($r) => $r->assento->numero)->values();

        return view('cobrador.viagem', ['viagem' => $viagem, 'passageiros' => $passageiros]);
    }

    /** RN11: um QR só pode ser validado uma vez; usados, cancelados ou de outra viagem são rejeitados. */
    public function validar(Request $request, Viagem $viagem)
    {
        $viagem = $this->minha($viagem);
        $request->validate(['codigo' => ['required', 'string', 'max:60']], ['codigo.required' => 'Indique ou leia o código do bilhete.']);
        $lido = Bilhete::interpretar($request->input('codigo'));
        $codigo = $lido['codigo'];
        $res = ['ok' => false, 'codigo' => $codigo, 'mensagem' => 'Bilhete não encontrado.'];
        $bilhete = null;

        if ($lido['assinado'] && ! $lido['valido']) {
            // QR adulterado ou fabricado
            $res['mensagem'] = 'QR inválido: assinatura não confere. Este bilhete não foi emitido pelo sistema.';
        } elseif (! $lido['assinado'] && $request->input('origem') === 'qr') {
            // a leitura pela câmara tem de trazer sempre a assinatura (só a escrita manual aceita o código simples)
            $res['mensagem'] = 'QR sem assinatura válida. Este bilhete não foi emitido pelo sistema.';
        } else {
            $bilhete = Bilhete::where('codigo_qr', $codigo)->with('reserva.assento', 'reserva.viagem.rota')->first();
        }

        if ($bilhete) {
            $reserva = $bilhete->reserva;
            $res['passageiro'] = $reserva->nome_passageiro;
            $res['lugar'] = $reserva->assento->rotulo;

            if ((int) $reserva->viagem_id !== (int) $viagem->id) {
                $res['mensagem'] = "Este bilhete é para outra viagem ({$reserva->viagem->rota->nome}, {$reserva->viagem->data->format('d/m')} {$reserva->viagem->hora}).";
            } elseif ($bilhete->estado === 'cancelado' || $reserva->estado !== 'confirmada') {
                $res['mensagem'] = 'Bilhete cancelado ou inválido.';
            } elseif ($bilhete->estado === 'usado') {
                $res['mensagem'] = 'Bilhete já utilizado' . ($bilhete->validado_em ? ' às ' . $bilhete->validado_em->format('H:i') : '') . '.';
            } else {
                // actualização atómica: só uma validação pode "ganhar"
                $n = Bilhete::whereKey($bilhete->id)->where('estado', 'valido')
                    ->update(['estado' => 'usado', 'validado_em' => now(), 'validado_por' => $request->user()->id]);
                if ($n) {
                    if (! $viagem->cobrador_id) {
                        $viagem->update(['cobrador_id' => $request->user()->id]); // assume a viagem que ficou sem cobrador
                    }
                    $res['ok'] = true;
                    $res['mensagem'] = 'Passageiro pode embarcar.';
                    Auditoria::registar('Bilhete validado', "{$codigo} · lugar {$reserva->assento->rotulo}");
                } else {
                    $res['mensagem'] = 'Bilhete já utilizado.';
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json($res);
        }

        return back()->with('resultado', $res);
    }

    public function terminar(Viagem $viagem)
    {
        $viagem = $this->minha($viagem);
        $viagem->update(['estado' => 'concluida']);
        Auditoria::registar('Viagem terminada', "{$viagem->rota->nome} · {$viagem->hora}");

        return redirect()->route('cobrador.viagens')->with('estado', 'Viagem terminada.');
    }
}
