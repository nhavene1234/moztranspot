<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Viagem extends Model
{
    protected $table = 'viagens';

    protected $fillable = ['data', 'hora_partida', 'preco', 'classe', 'estado', 'rota_id', 'autocarro_id', 'cobrador_id'];

    protected $casts = ['data' => 'date'];

    public function rota()
    {
        return $this->belongsTo(Rota::class);
    }

    public function autocarro()
    {
        return $this->belongsTo(Autocarro::class);
    }

    public function cobrador()
    {
        return $this->belongsTo(Utilizador::class, 'cobrador_id');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    /** RN01: só viagens futuras. */
    public function scopeFuturas($q)
    {
        $hoje = now()->toDateString();
        $agora = now()->format('H:i:s');

        return $q->where(function ($w) use ($hoje, $agora) {
            // whereDate: a coluna pode guardar "2026-10-03" ou "2026-10-03 00:00:00" conforme a base de dados
            $w->whereDate('data', '>', $hoje)
                ->orWhere(fn ($x) => $x->whereDate('data', $hoje)->where('hora_partida', '>', $agora));
        });
    }

    public function getPartidaEmAttribute(): Carbon
    {
        return Carbon::parse($this->data->format('Y-m-d') . ' ' . $this->hora_partida);
    }

    public function getChegadaEmAttribute(): Carbon
    {
        return $this->partida_em->copy()->addMinutes($this->rota->duracao_minutos ?? 0);
    }

    public function getHoraAttribute(): string
    {
        return substr($this->hora_partida, 0, 5);
    }

    public function getHoraChegadaAttribute(): string
    {
        return $this->chegada_em->format('H:i');
    }

    /** Lugares ocupados (reservas com lugar bloqueado ou confirmado). */
    public function ocupados(): int
    {
        return $this->reservas()->where('ativa', 1)->count();
    }

    public function livres(): int
    {
        return max(0, ($this->autocarro->capacidade_total ?? 0) - $this->ocupados());
    }

    /** IDs dos assentos ocupados. */
    public function assentosOcupados(): array
    {
        return $this->reservas()->where('ativa', 1)->pluck('assento_id')->all();
    }
}
