<?php

namespace Database\Seeders;

use App\Models\Definicao;
use App\Models\Permissao;
use Illuminate\Database\Seeder;

class ConfiguracaoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Definicao::PADRAO as $chave => $valor) {
            Definicao::firstOrCreate(['chave' => $chave], ['valor' => $valor]);
        }

        foreach (Permissao::PERFIS as $perfil) {
            foreach (array_keys(Permissao::CHAVES) as $chave) {
                Permissao::updateOrCreate(
                    ['perfil' => $perfil, 'chave' => $chave],
                    ['ativo' => in_array($chave, Permissao::PADRAO[$perfil], true)]
                );
            }
        }
        Permissao::limparCache();
    }
}
