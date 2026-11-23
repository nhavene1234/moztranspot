<?php

return [
    // Nome oficial do sistema (aparece nos títulos, no login, no bilhete e nos emails).
    'nome' => 'Sistema de venda de bilhetes para transportes rodoviários de Moçambique',

    // Só serve para mostrar o código de recuperação de palavra-passe no ecrã (para testes sem email).
    // Por omissão está DESLIGADO: o utilizador entra apenas com as suas credenciais.
    // Para ligar (apenas em testes): MOZ_DEMO=true no ficheiro .env.
    'demo' => env('MOZ_DEMO', false),

    'demo_senha' => 'Moz@2026',
];
