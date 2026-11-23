<?php

namespace App\Services\Pagamentos;

use Illuminate\Support\Str;

/**
 * Gateway de demonstração. Não fala com a Vodacom nem com a Movitel.
 *
 * Para ligar às APIs reais M-Pesa / e-Mola, crie uma classe que implemente PagamentoGateway
 * (com as credenciais de comerciante fornecidas pelos operadores) e troque a ligação em
 * AppServiceProvider. Nenhuma outra parte do sistema precisa de mudar.
 *
 * Regras da simulação:
 *  - M-Pesa só aceita números 84/85; e-Mola só aceita 86/87;
 *  - números terminados em 00 são recusados (para testar o ecrã de falha);
 *  - todo o resto é aprovado.
 */
class SimuladoGateway implements PagamentoGateway
{
    public function cobrar(int $valor, string $metodo, ?string $telefone, string $referencia): array
    {
        $ref = 'SIM-' . strtoupper(Str::random(10));

        if (in_array($metodo, ['mpesa', 'emola'], true)) {
            $digitos = preg_replace('/\D/', '', (string) $telefone);
            $nacional = substr($digitos, -9);
            $prefixo = substr($nacional, 0, 2);
            $validos = $metodo === 'mpesa' ? ['84', '85'] : ['86', '87'];

            if (strlen($nacional) < 9 || ! in_array($prefixo, $validos, true)) {
                $nome = $metodo === 'mpesa' ? 'M-Pesa (84/85)' : 'e-Mola (86/87)';

                return ['ok' => false, 'referencia' => $ref, 'codigo' => strtoupper($metodo) . '-INVALID-NUMBER',
                    'mensagem' => "O número indicado não é um número {$nome} válido."];
            }
            if (str_ends_with($digitos, '00')) {
                return ['ok' => false, 'referencia' => $ref, 'codigo' => strtoupper($metodo) . '-DECLINED-04',
                    'mensagem' => 'A transacção foi recusada. Verifique o saldo ou escolha outro meio de pagamento.'];
            }
        }

        return ['ok' => true, 'referencia' => $ref, 'codigo' => null, 'mensagem' => null];
    }
}
