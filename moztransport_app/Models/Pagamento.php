<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    protected $table = 'pagamentos';

    protected $fillable = ['reserva_id', 'valor', 'metodo', 'estado', 'data_pagamento', 'referencia_transacao', 'codigo_erro'];

    protected $casts = ['data_pagamento' => 'datetime'];

    public const METODOS = [
        'mpesa' => 'M-Pesa',
        'emola' => 'e-Mola',
        'cartao' => 'Cartão',
        'numerario' => 'Numerário',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function rotuloMetodo(): string
    {
        return self::METODOS[$this->metodo] ?? $this->metodo;
    }
}
