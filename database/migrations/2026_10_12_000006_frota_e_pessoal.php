<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Frota e pessoal do gestor:
 *  - autocarros: ano e livrete (ficheiro guardado em storage/app/private/livretes)
 *  - motoristas (novo) e motorista da viagem
 *  - permissões novas "gerir_frota" e "gerir_pessoal" (para o gestor e o administrador)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autocarros', function (Blueprint $t) {
            $t->unsignedSmallInteger('ano')->nullable();
            $t->string('livrete_caminho')->nullable();
            $t->string('livrete_nome', 150)->nullable();
            $t->timestamp('livrete_em')->nullable();
        });

        Schema::create('motoristas', function (Blueprint $t) {
            $t->id();
            $t->string('nome', 120);
            $t->string('telefone', 30)->nullable();
            $t->string('documento', 30)->nullable();        // BI / passaporte
            $t->string('carta_numero', 40);
            $t->string('carta_categoria', 10)->nullable();
            $t->date('carta_validade')->nullable();
            $t->string('estado', 20)->default('activo');    // activo | inactivo
            $t->foreignId('transportadora_id')->constrained('transportadoras')->cascadeOnDelete();
            $t->timestamps();
        });

        Schema::table('viagens', function (Blueprint $t) {
            $t->foreignId('motorista_id')->nullable()->constrained('motoristas')->nullOnDelete();
        });

        // Permissões novas: ligadas por omissão ao gestor e ao administrador (o admin pode alterar na matriz)
        foreach (['bilheteiro', 'cobrador', 'gestor', 'administrador'] as $perfil) {
            foreach (['gerir_frota', 'gerir_pessoal'] as $chave) {
                $existe = DB::table('permissoes')->where('perfil', $perfil)->where('chave', $chave)->exists();
                if (! $existe) {
                    DB::table('permissoes')->insert(['perfil' => $perfil, 'chave' => $chave, 'ativo' => in_array($perfil, ['gestor', 'administrador'], true)]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $t) {
            $t->dropConstrainedForeignId('motorista_id');
        });
        Schema::dropIfExists('motoristas');
        Schema::table('autocarros', function (Blueprint $t) {
            $t->dropColumn(['ano', 'livrete_caminho', 'livrete_nome', 'livrete_em']);
        });
        DB::table('permissoes')->whereIn('chave', ['gerir_frota', 'gerir_pessoal'])->delete();
    }
};
