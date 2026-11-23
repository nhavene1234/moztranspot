<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assento extends Model
{
    public $timestamps = false;

    protected $table = 'assentos';

    protected $fillable = ['numero', 'tipo', 'autocarro_id'];

    public function autocarro()
    {
        return $this->belongsTo(Autocarro::class);
    }

    public function getFilaAttribute(): int
    {
        return (int) ceil($this->numero / 4);
    }

    public function getLetraAttribute(): string
    {
        return 'ABCD'[($this->numero - 1) % 4];
    }

    /** Rótulo mostrado ao utilizador, ex.: 7B */
    public function getRotuloAttribute(): string
    {
        return $this->fila . $this->letra;
    }
}
