<?php

namespace App\Services;

use App\Models\Assento;
use App\Models\Bilhete;
use App\Models\Definicao;
use App\Models\Pagamento;
use App\Models\Reserva;
use App\Models\Viagem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Regras de reserva (RN02–RN06): bloqueio temporário do lugar, confirmação após
 * pagamento, cancelamento e remarcação dentro do prazo.
 */
class ReservaService
{
    /** RN03/RN04: se o pagamento não chegar a tempo, o lugar volta a ficar livre. */
    public function libertarExpiradas(): void
    {
        Reserva::where('estado', 'pendente')
            ->where('expira_em', '<', now())
            ->update(['estado' => 'expirada', 'ativa' => null]);
    }

    public function minutosBloqueio(): int
    {
        return max(1, Definicao::inteiro('minutos_bloqueio_lugar'));
    }

    public function horasLimiteCancelamento(): int
    {
        return Definicao::inteiro('horas_limite_cancelamento');
    }

    /**
     * Bloqueia temporariamente um ou mais lugares. Lança LugarIndisponivel se algum já estiver ocupado.
     *
     * @param  array<int>  $assentoIds
     * @param  array  $dados  nome_passageiro, documento, telefone, passageiro_id, bilheteiro_id
     */
    public function bloquear(Viagem $viagem, array $assentoIds, array $dados): Collection
    {
        $assentoIds = array_values(array_unique(array_map('intval', $assentoIds)));
        if (! $assentoIds) {
            throw new LugarIndisponivel('Escolha pelo menos um lugar.');
        }

        return DB::transaction(function () use ($viagem, $assentoIds, $dados) {
            // Serializa as compras da mesma viagem (RNF01: nada de venda duplicada).
            $v = Viagem::whereKey($viagem->id)->lockForUpdate()->firstOrFail();
            if ($v->estado !== 'agendada' || $v->partida_em->isPast()) {
                throw new LugarIndisponivel('Esta viagem já não está disponível para venda.');
            }

            $this->libertarExpiradas();

            $grupo = (string) Str::uuid();
            $reservas = collect();

            foreach ($assentoIds as $assentoId) {
                $assento = Assento::where('id', $assentoId)->where('autocarro_id', $v->autocarro_id)->first();
                if (! $assento) {
                    throw new LugarIndisponivel('Lugar inválido para esta viagem.');
                }

                $ocupado = Reserva::where('viagem_id', $v->id)->where('assento_id', $assento->id)->where('ativa', 1)->exists();
                if ($ocupado) {
                    throw new LugarIndisponivel("O lugar {$assento->rotulo} acabou de ser ocupado. Escolha outro.");
                }

                $reservas->push(Reserva::create([
                    'grupo' => $grupo,
                    'passageiro_id' => $dados['passageiro_id'] ?? null,
                    'bilheteiro_id' => $dados['bilheteiro_id'] ?? null,
                    'viagem_id' => $v->id,
                    'assento_id' => $assento->id,
                    'nome_passageiro' => $dados['nome_passageiro'] ?? '—',
                    'documento' => $dados['documento'] ?? null,
                    'telefone' => $dados['telefone'] ?? null,
                    'estado' => 'pendente',
                    'ativa' => 1,
                    'expira_em' => now()->addMinutes($this->minutosBloqueio()),
                    'data_reserva' => now(),
                ]));
            }

            return $reservas;
        });
    }

    /** Pagamento aprovado: a reserva torna-se definitiva e o bilhete com QR é emitido (RN04, RN05). */
    public function confirmar(Reserva $reserva, string $metodo, ?string $referencia = null): Reserva
    {
        return DB::transaction(function () use ($reserva, $metodo, $referencia) {
            $r = Reserva::whereKey($reserva->id)->lockForUpdate()->firstOrFail();

            if ($r->estado === 'confirmada') {
                return $r->load('bilhete', 'pagamento');
            }
            if ($r->estado !== 'pendente' || ($r->expira_em && $r->expira_em->isPast())) {
                $r->update(['estado' => 'expirada', 'ativa' => null]);
                throw new LugarIndisponivel('O tempo da reserva expirou e o lugar foi libertado.');
            }

            $valor = $r->viagem->preco;
            Pagamento::create([
                'reserva_id' => $r->id,
                'valor' => $valor,
                'metodo' => $metodo,
                'estado' => 'pago',
                'data_pagamento' => now(),
                'referencia_transacao' => $referencia,
            ]);
            $r->update(['estado' => 'confirmada', 'ativa' => 1, 'expira_em' => null]);
            Bilhete::emitir($r);

            return $r->load('bilhete', 'pagamento');
        });
    }

    public function registarFalha(Reserva $reserva, string $metodo, ?string $codigo, ?string $referencia = null): Pagamento
    {
        return Pagamento::create([
            'reserva_id' => $reserva->id,
            'valor' => $reserva->viagem->preco,
            'metodo' => $metodo,
            'estado' => 'falhado',
            'data_pagamento' => now(),
            'referencia_transacao' => $referencia,
            'codigo_erro' => $codigo,
        ]);
    }

    /** Desiste de uma reserva ainda não paga e liberta o lugar. */
    public function libertar(Reserva $reserva): void
    {
        if ($reserva->estado === 'pendente') {
            $reserva->update(['estado' => 'cancelada', 'ativa' => null, 'expira_em' => null]);
        }
    }

    /** RN06: só é possível alterar dentro do prazo definido antes da partida. */
    public function dentroDoPrazo(Reserva $reserva): bool
    {
        $partida = $reserva->viagem->partida_em;

        return $partida->isFuture()
            && now()->diffInMinutes($partida, false) >= $this->horasLimiteCancelamento() * 60;
    }

    public function cancelar(Reserva $reserva): void
    {
        if ($reserva->estado !== 'confirmada') {
            throw new PrazoExpirado('Só é possível cancelar reservas confirmadas.');
        }
        if (! $this->dentroDoPrazo($reserva)) {
            throw new PrazoExpirado("O prazo de cancelamento terminou ({$this->horasLimiteCancelamento()} horas antes da partida).");
        }

        DB::transaction(function () use ($reserva) {
            $reserva->update(['estado' => 'cancelada', 'ativa' => null]); // lugar libertado
            $reserva->bilhete?->update(['estado' => 'cancelado']);
            $reserva->pagamentos()->where('estado', 'pago')->update(['estado' => 'reembolsado']);
        });
    }

    /** Muda a reserva para outra viagem da mesma rota e preço, mantendo o mesmo bilhete. */
    public function remarcar(Reserva $reserva, Viagem $nova, int $assentoId): void
    {
        $original = $reserva->viagem;
        if ($reserva->estado !== 'confirmada') {
            throw new PrazoExpirado('Só é possível remarcar reservas confirmadas.');
        }
        if (! $this->dentroDoPrazo($reserva)) {
            throw new PrazoExpirado("O prazo de remarcação terminou ({$this->horasLimiteCancelamento()} horas antes da partida).");
        }
        if ($nova->rota_id !== $original->rota_id || $nova->preco !== $original->preco) {
            throw new LugarIndisponivel('A nova viagem tem de ser da mesma rota e preço.');
        }

        DB::transaction(function () use ($reserva, $nova, $assentoId) {
            $v = Viagem::whereKey($nova->id)->lockForUpdate()->firstOrFail();
            if ($v->estado !== 'agendada' || $v->partida_em->isPast()) {
                throw new LugarIndisponivel('A nova viagem já não está disponível.');
            }
            $assento = Assento::where('id', $assentoId)->where('autocarro_id', $v->autocarro_id)->first();
            if (! $assento) {
                throw new LugarIndisponivel('Lugar inválido.');
            }
            $this->libertarExpiradas();
            if (Reserva::where('viagem_id', $v->id)->where('assento_id', $assento->id)->where('ativa', 1)->exists()) {
                throw new LugarIndisponivel("O lugar {$assento->rotulo} já está ocupado.");
            }
            $reserva->update(['viagem_id' => $v->id, 'assento_id' => $assento->id]);
        });
    }
}
