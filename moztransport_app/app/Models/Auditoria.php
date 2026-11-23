<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auditoria';

    protected $fillable = ['utilizador_id', 'utilizador_nome', 'acao', 'detalhe', 'cor', 'ip'];

    protected $casts = ['created_at' => 'datetime'];

    /** Regista uma acção. $cor: verde (normal) | laranja (alteração) | vermelho (bloqueio/risco). */
    public static function registar(string $acao, ?string $detalhe = null, string $cor = 'verde', ?Utilizador $utilizador = null): void
    {
        $utilizador ??= auth()->user();

        try {
            static::create([
                'utilizador_id' => $utilizador?->id,
                'utilizador_nome' => $utilizador?->nome ?? 'Sistema Automático',
                'acao' => $acao,
                'detalhe' => $detalhe,
                'cor' => $cor,
                'ip' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e); // a auditoria nunca deve impedir a operação principal
        }
    }
}
