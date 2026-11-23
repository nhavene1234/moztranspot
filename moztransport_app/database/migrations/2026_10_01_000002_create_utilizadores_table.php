<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilizadores', function (Blueprint $t) {
            $t->id();
            $t->string('nome');
            $t->string('email')->unique();
            $t->string('telefone', 30)->nullable();
            $t->string('documento', 40)->nullable();
            $t->string('senha');
            // passageiro | bilheteiro | cobrador | gestor | administrador
            $t->string('perfil', 20)->default('passageiro')->index();
            $t->foreignId('transportadora_id')->nullable()->constrained('transportadoras')->nullOnDelete();
            $t->string('agencia')->nullable();
            // activo | bloqueado
            $t->string('estado', 20)->default('activo');
            $t->unsignedSmallInteger('tentativas_falhadas')->default(0);
            $t->timestamp('bloqueado_ate')->nullable();
            $t->boolean('notificacoes')->default(true);
            $t->string('idioma', 5)->default('pt');
            $t->rememberToken();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilizadores');
    }
};
