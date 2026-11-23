<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transportadora extends Model
{
    protected $table = 'transportadoras';

    protected $fillable = ['nome', 'nuit', 'contacto', 'endereco', 'descricao', 'estado'];

    public function rotas()
    {
        return $this->hasMany(Rota::class);
    }

    public function autocarros()
    {
        return $this->hasMany(Autocarro::class);
    }

    public function utilizadores()
    {
        return $this->hasMany(Utilizador::class);
    }

    public function gestor()
    {
        return $this->hasOne(Utilizador::class)->where('perfil', 'gestor');
    }
}
