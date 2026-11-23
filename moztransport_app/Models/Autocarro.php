<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Autocarro extends Model
{
    protected $table = 'autocarros';

    protected $fillable = ['matricula', 'modelo', 'capacidade_total', 'transportadora_id', 'estado'];

    public function transportadora()
    {
        return $this->belongsTo(Transportadora::class);
    }

    public function assentos()
    {
        return $this->hasMany(Assento::class)->orderBy('numero');
    }
}
