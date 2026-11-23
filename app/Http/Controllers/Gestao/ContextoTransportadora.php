<?php

namespace App\Http\Controllers\Gestao;

use App\Models\Transportadora;
use Illuminate\Http\Request;

/**
 * Regras comuns da gestão de frota e pessoal (RN13): o gestor só mexe na sua transportadora;
 * o administrador pode escolher qualquer uma.
 */
trait ContextoTransportadora
{
    /** Transportadora em análise: null = todas (só para quem tem permissão). */
    protected function tid(Request $r): ?int
    {
        $u = $r->user();
        $pedida = $r->query('transportadora');

        if ($u->tem('ver_todas_transportadoras')) {
            return $pedida ? (int) $pedida : null;
        }
        abort_unless($u->transportadora_id, 403);
        abort_if($pedida && (int) $pedida !== (int) $u->transportadora_id, 403);

        return (int) $u->transportadora_id;
    }

    protected function contexto(Request $r, ?int $tid): array
    {
        return [
            'tid' => $tid,
            'empresa' => $tid ? Transportadora::find($tid) : null,
            'empresas' => $r->user()->tem('ver_todas_transportadoras') ? Transportadora::orderBy('nome')->get() : collect(),
            'admin' => $r->user()->tem('ver_todas_transportadoras'),
        ];
    }

    /** Transportadora onde se cria um registo novo. */
    protected function empresaDoNovo(Request $r): int
    {
        $u = $r->user();
        if ($u->tem('ver_todas_transportadoras')) {
            $id = (int) $r->input('transportadora_id');
            abort_unless($id && Transportadora::whereKey($id)->exists(), 422, 'Escolha a transportadora.');

            return $id;
        }
        abort_unless($u->transportadora_id, 403);

        return (int) $u->transportadora_id;
    }

    /** 403 se o registo for de outra transportadora. */
    protected function permitido(Request $r, $registo): void
    {
        if (! $r->user()->tem('ver_todas_transportadoras')) {
            abort_unless((int) $registo->transportadora_id === (int) $r->user()->transportadora_id, 403);
        }
    }
}
