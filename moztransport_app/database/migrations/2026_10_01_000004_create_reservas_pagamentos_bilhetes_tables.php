<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $t) {
            $t->id();
            $t->uuid('grupo')->nullable()->index(); // agrupa lugares vendidos na mesma operação
            $t->foreignId('passageiro_id')->nullable()->constrained('utilizadores')->nullOnDelete();
            $t->foreignId('bilheteiro_id')->nullable()->constrained('utilizadores')->nullOnDelete();
            $t->foreignId('viagem_id')->constrained('viagens')->cascadeOnDelete();
            $t->foreignId('assento_id')->constrained('assentos')->cascadeOnDelete();
            $t->string('nome_passageiro');
            $t->string('documento', 40)->nullable();
            $t->string('telefone', 30)->nullable();
            // pendente | confirmada | cancelada | expirada
            $t->string('estado', 20)->default('pendente')->index();
            // 1 enquanto o lugar está ocupado (pendente ou confirmada), NULL quando libertado.
            // O índice único abaixo impede, ao nível da base de dados, vender o mesmo lugar duas vezes (RN02).
            $t->unsignedTinyInteger('ativa')->nullable();
            $t->timestamp('expira_em')->nullable();
            $t->timestamp('data_reserva')->useCurrent();
            $t->timestamps();
            $t->unique(['viagem_id', 'assento_id', 'ativa'], 'reservas_lugar_unico');
        });

        Schema::create('pagamentos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $t->unsignedInteger('valor');
            // mpesa | emola | cartao | numerario
            $t->string('metodo', 20);
            // pago | falhado | reembolsado
            $t->string('estado', 20)->default('pago');
            $t->timestamp('data_pagamento')->nullable()->index();
            $t->string('referencia_transacao', 60)->nullable();
            $t->string('codigo_erro', 40)->nullable();
            $t->timestamps();
        });

        Schema::create('bilhetes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $t->string('codigo_qr', 40)->unique();
            $t->timestamp('data_emissao')->useCurrent();
            // valido | usado | cancelado
            $t->string('estado', 20)->default('valido');
            $t->timestamp('validado_em')->nullable();
            $t->foreignId('validado_por')->nullable()->constrained('utilizadores')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bilhetes');
        Schema::dropIfExists('pagamentos');
        Schema::dropIfExists('reservas');
    }
};
