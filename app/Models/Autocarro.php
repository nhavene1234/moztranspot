<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Autocarro extends Model
{
    protected $table = 'autocarros';

    protected $fillable = [
        'matricula', 'modelo', 'ano', 'capacidade_total', 'transportadora_id', 'estado',
        'livrete_caminho', 'livrete_nome', 'livrete_em',
    ];

    protected $casts = ['livrete_em' => 'datetime'];

    public function viagens()
    {
        return $this->hasMany(Viagem::class);
    }

    public function transportadora()
    {
        return $this->belongsTo(Transportadora::class);
    }

    public function assentos()
    {
        return $this->hasMany(Assento::class)->orderBy('numero');
    }
}
