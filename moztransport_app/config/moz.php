<?php

return [
    // Mostra os atalhos de contas de demonstração no login e o código de recuperação no ecrã.
    // PÕE false EM PRODUÇÃO (MOZ_DEMO=false no .env).
    'demo' => env('MOZ_DEMO', true),

    'demo_senha' => 'Moz@2026',
];
