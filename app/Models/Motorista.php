<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motorista extends Model
{
    protected $table = 'motoristas';

    protected $fillable = ['nome', 'telefone', 'documento', 'carta_numero', 'carta_categoria', 'carta_validade', 'estado', 'transportadora_id'];

    protected $casts = ['carta_validade' => 'date'];

    public function transportadora()
    {
        return $this->belongsTo(Transportadora::class);
    }

    public function viagens()
    {
        return $this->hasMany(Viagem::class);
    }

    /** 'caducada' | 'a_caducar' (próximos 30 dias) | 'valida' | null (sem data) */
    public function estadoCarta(): ?string
    {
        if (! $this->carta_validade) {
            return null;
        }
        if ($this->carta_validade->endOfDay()->isPast()) {
            return 'caducada';
        }

        return $this->carta_validade->lte(now()->addDays(30)) ? 'a_caducar' : 'valida';
    }
}
