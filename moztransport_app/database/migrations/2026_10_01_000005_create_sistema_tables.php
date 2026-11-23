<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RNF10: registo de acções para auditoria
        Schema::create('auditoria', function (Blueprint $t) {
            $t->id();
            $t->foreignId('utilizador_id')->nullable()->constrained('utilizadores')->nullOnDelete();
            $t->string('utilizador_nome')->nullable();
            $t->string('acao', 120);
            $t->string('detalhe')->nullable();
            $t->string('cor', 10)->default('verde'); // verde | laranja | vermelho
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('definicoes', function (Blueprint $t) {
            $t->string('chave', 60)->primary();
            $t->string('valor');
        });

        // Matriz de permissões por perfil (RNF06, RN17)
        Schema::create('permissoes', function (Blueprint $t) {
            $t->id();
            $t->string('perfil', 20);
            $t->string('chave', 60);
            $t->boolean('ativo')->default(false);
            $t->unique(['perfil', 'chave']);
        });

        // Códigos de 6 dígitos para recuperar a palavra-passe (RF03, RN18)
        Schema::create('codigos_recuperacao', function (Blueprint $t) {
            $t->id();
            $t->string('email')->index();
            $t->string('codigo_hash');
            $t->timestamp('expira_em');
            $t->timestamp('usado_em')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_recuperacao');
        Schema::dropIfExists('permissoes');
        Schema::dropIfExists('definicoes');
        Schema::dropIfExists('auditoria');
    }
};
