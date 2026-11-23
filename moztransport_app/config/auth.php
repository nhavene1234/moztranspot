<?php

/*
 * A autenticação usa o modelo App\Models\Utilizador (tabela "utilizadores", coluna "senha").
 */
return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
    ],

    'providers' => [
        'users' => ['driver' => 'eloquent', 'model' => App\Models\Utilizador::class],
    ],

    'passwords' => [
        'users' => ['provider' => 'users', 'table' => 'password_reset_tokens', 'expire' => 60, 'throttle' => 60],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
