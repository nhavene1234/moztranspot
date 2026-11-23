<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Utilizador extends Authenticatable
{
    use Notifiable;

    protected $table = 'utilizadores';

    protected $fillable = [
        'nome', 'email', 'telefone', 'documento', 'senha', 'perfil', 'transportadora_id',
        'agencia', 'estado', 'tentativas_falhadas', 'bloqueado_ate', 'notificacoes', 'idioma',
    ];

    protected $hidden = ['senha', 'remember_token'];

    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'bloqueado_ate' => 'datetime',
            'notificacoes' => 'boolean',
        ];
    }

    public const PERFIS = [
        'passageiro' => 'Passageiro',
        'bilheteiro' => 'Bilheteiro',
        'cobrador' => 'Cobrador',
        'gestor' => 'Gestor',
        'administrador' => 'Administrador',
    ];

    /** O Laravel vai buscar a palavra-passe a esta coluna. */
    public function getAuthPassword()
    {
        return $this->senha;
    }

    public function getAuthPasswordName()
    {
        return 'senha';
    }

    public function transportadora()
    {
        return $this->belongsTo(Transportadora::class);
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'passageiro_id');
    }

    public function eStaff(): bool
    {
        return $this->perfil !== 'passageiro';
    }

    public function rotuloPerfil(): string
    {
        return self::PERFIS[$this->perfil] ?? ucfirst($this->perfil);
    }

    public function iniciais(): string
    {
        $partes = preg_split('/\s+/', trim($this->nome));
        $a = mb_substr($partes[0] ?? '', 0, 1);
        $b = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';

        return mb_strtoupper($a . $b);
    }

    public function primeiroNome(): string
    {
        return explode(' ', trim($this->nome))[0] ?? $this->nome;
    }

    public function tem(string $permissao): bool
    {
        return Permissao::tem($this->perfil, $permissao);
    }

    public function estaBloqueado(): bool
    {
        return $this->estado === 'bloqueado'
            || ($this->bloqueado_ate && $this->bloqueado_ate->isFuture());
    }

    /** Página inicial de cada perfil. */
    public function rotaInicial(): string
    {
        return match ($this->perfil) {
            'passageiro' => route('app.inicio'),
            'bilheteiro' => route('bilheteira.vender'),
            'cobrador' => route('cobrador.viagens'),
            'gestor' => route('gestao.dashboard'),
            'administrador' => route('admin.visao'),
            default => route('entrar'),
        };
    }
}
