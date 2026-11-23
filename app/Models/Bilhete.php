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

    /** Assinatura curta (HMAC com a chave da aplicação): impede fabricar um QR só por adivinhar o número. */
    public static function assinatura(string $codigo): string
    {
        return strtoupper(substr(hash_hmac('sha256', strtoupper($codigo), (string) config('app.key')), 0, 8));
    }

    /** Texto que vai dentro do QR (e que o cobrador também pode escrever): MT-2026-000123.AB12CD34 */
    public function getCodigoAssinadoAttribute(): string
    {
        return $this->codigo_qr . '.' . static::assinatura($this->codigo_qr);
    }

    /**
     * Interpreta o que foi lido/escrito.
     * @return array{codigo:string, assinado:bool, valido:bool}
     */
    public static function interpretar(string $lido): array
    {
        $lido = strtoupper(trim($lido));
        $partes = explode('.', $lido, 2);
        $codigo = $partes[0];
        if (count($partes) === 1) {
            return ['codigo' => $codigo, 'assinado' => false, 'valido' => false];
        }

        return ['codigo' => $codigo, 'assinado' => true, 'valido' => hash_equals(static::assinatura($codigo), $partes[1])];
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
