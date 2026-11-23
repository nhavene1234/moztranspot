<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_a_raiz_redireciona_visitantes_para_o_login(): void
    {
        $this->get('/')->assertRedirect(route('entrar'));
    }
}
