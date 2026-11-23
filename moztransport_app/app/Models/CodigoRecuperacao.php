<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodigoRecuperacao extends Model
{
    protected $table = 'codigos_recuperacao';

    protected $fillable = ['email', 'codigo_hash', 'expira_em', 'usado_em'];

    protected $casts = ['expira_em' => 'datetime', 'usado_em' => 'datetime'];
}
