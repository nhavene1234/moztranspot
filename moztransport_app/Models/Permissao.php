<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permissao extends Model
{
    public $timestamps = false;

    protected $table = 'permissoes';

    protected $fillable = ['perfil', 'chave', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    /** Perfis que aparecem como colunas da matriz (o passageiro não entra em painéis). */
    public const PERFIS = ['bilheteiro', 'cobrador', 'gestor', 'administrador'];

    /** chave => [descrição, é alerta de segurança?] */
    public const CHAVES = [
        'ver_dashboard' => ['Ver dashboard', false],
        'vender_bilhetes' => ['Vender bilhetes', false],
        'gerir_rotas' => ['Gerir rotas', false],
        'ver_relatorios' => ['Ver relatórios', false],
        'gerir_utilizadores' => ['Gerir utilizadores', false],
        'ver_todas_transportadoras' => ['Ver dados de todas as transportadoras', true],
        'gerir_permissoes' => ['Gerir permissões', false],
        'gerir_transportadoras' => ['Gerir transportadoras', false],
        'ver_auditoria' => ['Ver auditoria', false],
        'gerir_seguranca' => ['Gerir segurança', false],
    ];

    /** Valores por omissão (seguem a matriz CRUD do documento). */
    public const PADRAO = [
        'bilheteiro' => ['ver_dashboard', 'vender_bilhetes'],
        'cobrador' => ['ver_dashboard'],
        'gestor' => ['ver_dashboard', 'gerir_rotas', 'ver_relatorios'],
        'administrador' => [
            'ver_dashboard', 'vender_bilhetes', 'gerir_rotas', 'ver_relatorios', 'gerir_utilizadores',
            'ver_todas_transportadoras', 'gerir_permissoes', 'gerir_transportadoras', 'ver_auditoria', 'gerir_seguranca',
        ],
    ];

    protected static ?array $cache = null;

    public static function tem(string $perfil, string $chave): bool
    {
        if (static::$cache === null) {
            static::$cache = [];
            foreach (static::all() as $p) {
                static::$cache[$p->perfil][$p->chave] = (bool) $p->ativo;
            }
        }

        return static::$cache[$perfil][$chave] ?? false;
    }

    public static function limparCache(): void
    {
        static::$cache = null;
    }
}
