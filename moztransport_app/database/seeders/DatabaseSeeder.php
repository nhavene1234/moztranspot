<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ConfiguracaoSeeder::class, // definições + matriz de permissões (necessário em produção)
            DemoSeeder::class,         // transportadoras, rotas, viagens e vendas de demonstração
        ]);
    }
}
