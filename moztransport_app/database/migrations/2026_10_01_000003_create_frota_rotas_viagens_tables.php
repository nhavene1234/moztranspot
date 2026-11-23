<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autocarros', function (Blueprint $t) {
            $t->id();
            $t->string('matricula', 20)->unique();
            $t->string('modelo', 60)->nullable();
            $t->unsignedSmallInteger('capacidade_total')->default(44);
            $t->foreignId('transportadora_id')->constrained('transportadoras')->cascadeOnDelete();
            $t->string('estado', 20)->default('activo');
            $t->timestamps();
        });

        Schema::create('assentos', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('numero');
            $t->string('tipo', 20)->default('janela'); // janela | corredor
            $t->foreignId('autocarro_id')->constrained('autocarros')->cascadeOnDelete();
            $t->unique(['autocarro_id', 'numero']);
        });

        Schema::create('rotas', function (Blueprint $t) {
            $t->id();
            $t->string('origem', 60);
            $t->string('destino', 60);
            $t->unsignedInteger('distancia_km')->default(0);
            $t->unsignedInteger('duracao_minutos')->default(0);
            $t->unsignedInteger('preco_base')->default(0); // em MT
            $t->foreignId('transportadora_id')->constrained('transportadoras')->cascadeOnDelete();
            $t->string('estado', 20)->default('activa'); // activa | desactivada
            $t->timestamps();
            $t->index(['origem', 'destino']);
        });

        Schema::create('viagens', function (Blueprint $t) {
            $t->id();
            $t->date('data')->index();
            $t->time('hora_partida');
            $t->unsignedInteger('preco');
            $t->string('classe', 40)->default('Executivo');
            // agendada | concluida | cancelada
            $t->string('estado', 20)->default('agendada');
            $t->foreignId('rota_id')->constrained('rotas')->cascadeOnDelete();
            $t->foreignId('autocarro_id')->constrained('autocarros')->cascadeOnDelete();
            $t->foreignId('cobrador_id')->nullable()->constrained('utilizadores')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viagens');
        Schema::dropIfExists('rotas');
        Schema::dropIfExists('assentos');
        Schema::dropIfExists('autocarros');
    }
};
