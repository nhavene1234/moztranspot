<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Bilhete extends Model
{
    protected $table = 'bilhetes';

    protected $fillable = ['reserva_id', 'codigo_qr', 'data_emissao', 'estado', 'validado_em', 'validado_por'];

    protected $casts = ['data_emissao' => 'datetime', 'validado_em' => 'datetime'];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    /** RN05: cada código é único e corresponde a uma única reserva. */
    public static function emitir(Reserva $reserva): self
    {
        $bilhete = static::create([
            'reserva_id' => $reserva->id,
            'codigo_qr' => (string) Str::uuid(),
            'data_emissao' => now(),
            'estado' => 'valido',
        ]);
        $bilhete->update(['codigo_qr' => sprintf('MT-%s-%06d', now()->year, $bilhete->id)]);

        return $bilhete;
    }
}
