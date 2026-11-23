<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Definicao extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'definicoes';

    protected $primaryKey = 'chave';

    protected $keyType = 'string';

    protected $fillable = ['chave', 'valor'];

    public const PADRAO = [
        'max_tentativas_login' => '5',
        'minutos_bloqueio_login' => '5',
        'minutos_bloqueio_lugar' => '10',
        'horas_limite_cancelamento' => '24',
        'ultimo_backup' => '',
    ];

    public static function obter(string $chave, ?string $padrao = null): ?string
    {
        $valor = static::query()->whereKey($chave)->value('valor');

        return $valor ?? $padrao ?? (self::PADRAO[$chave] ?? null);
    }

    public static function inteiro(string $chave): int
    {
        return (int) static::obter($chave);
    }

    public static function gravar(string $chave, string $valor): void
    {
        static::updateOrCreate(['chave' => $chave], ['valor' => $valor]);
    }
}
