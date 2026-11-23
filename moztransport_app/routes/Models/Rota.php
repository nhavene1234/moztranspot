<?php

namespace App\Models;

use App\Support\Fmt;
use Illuminate\Database\Eloquent\Model;

class Rota extends Model
{
    protected $table = 'rotas';

    protected $fillable = ['origem', 'destino', 'distancia_km', 'duracao_minutos', 'preco_base', 'transportadora_id', 'estado'];

    public function transportadora()
    {
        return $this->belongsTo(Transportadora::class);
    }

    public function viagens()
    {
        return $this->hasMany(Viagem::class);
    }

    public function getNomeAttribute(): string
    {
        return "{$this->origem} → {$this->destino}";
    }

    public function getDuracaoTextoAttribute(): string
    {
        return Fmt::duracao($this->duracao_minutos);
    }
}
