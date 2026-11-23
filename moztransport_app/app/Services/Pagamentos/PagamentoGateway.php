<?php

namespace App\Services\Pagamentos;

interface PagamentoGateway
{
    /**
     * Pede o pagamento ao operador.
     *
     * @return array{ok: bool, referencia: string, codigo: ?string, mensagem: ?string}
     */
    public function cobrar(int $valor, string $metodo, ?string $telefone, string $referencia): array;
}
