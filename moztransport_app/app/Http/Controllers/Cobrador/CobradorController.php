<?php

namespace App\Http\Controllers\Cobrador;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Bilhete;
use App\Models\Viagem;
use Illuminate\Http\Request;

/** Módulo do cobrador/motorista: validação por QR code e lista de passageiros (RF17, RF18). */
class CobradorController extends Controller
{
    private function minha(Viagem $viagem): Viagem
    {
        abort_unless($viagem->cobrador_id === auth()->id(), 403);

        return $viagem->load('rota.transportadora', 'autocarro');
    }

    public function viagens()
    {
        $viagens = Viagem::where('cobrador_id', auth()->id())
            ->whereDate('data', now()->toDateString())
            ->with('rota.transportadora', 'autocarro')
            ->withCount([
                'reservas as confirmados_count' => fn ($q) => $q->where('estado', 'confirmada'),
                'reservas as validados_count' => fn ($q) => $q->where('estado', 'confirmada')->whereHas('bilhete', fn ($b) => $b->where('estado', 'usado')),
            ])
            ->orderBy('hora_partida')->get();

        return view('cobrador.viagens', ['viagens' => $viagens]);
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
        $codigo = strtoupper(trim($request->input('codigo')));

        $bilhete = Bilhete::where('codigo_qr', $codigo)->with('reserva.assento', 'reserva.viagem.rota')->first();
        $res = ['ok' => false, 'codigo' => $codigo, 'mensagem' => 'Bilhete não encontrado.'];

        if ($bilhete) {
            $reserva = $bilhete->reserva;
            $res['passageiro'] = $reserva->nome_passageiro;
            $res['lugar'] = $reserva->assento->rotulo;

            if ($reserva->viagem_id !== $viagem->id) {
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
