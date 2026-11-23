<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'grupo', 'passageiro_id', 'bilheteiro_id', 'viagem_id', 'assento_id', 'nome_passageiro',
        'documento', 'telefone', 'estado', 'ativa', 'expira_em', 'data_reserva',
    ];

    protected $casts = ['expira_em' => 'datetime', 'data_reserva' => 'datetime'];

    public function passageiro()
    {
        return $this->belongsTo(Utilizador::class, 'passageiro_id');
    }

    public function bilheteiro()
    {
        return $this->belongsTo(Utilizador::class, 'bilheteiro_id');
    }

    public function viagem()
    {
        return $this->belongsTo(Viagem::class);
    }

    public function assento()
    {
        return $this->belongsTo(Assento::class);
    }

    public function pagamento()
    {
        return $this->hasOne(Pagamento::class)->latestOfMany();
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }

    public function bilhete()
    {
        return $this->hasOne(Bilhete::class);
    }

    public function rotuloEstado(): string
    {
        return match ($this->estado) {
            'confirmada' => 'Confirmado',
            'pendente' => 'Pendente',
            'cancelada' => 'Cancelada',
            'expirada' => 'Expirada',
            default => ucfirst($this->estado),
        };
    }
}
