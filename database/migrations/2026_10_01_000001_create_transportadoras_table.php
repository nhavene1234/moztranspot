<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportadoras', function (Blueprint $t) {
            $t->id();
            $t->string('nome');
            $t->string('nuit', 20)->nullable();
            $t->string('contacto', 30)->nullable();
            $t->string('endereco')->nullable();
            $t->string('descricao')->nullable();
            $t->string('estado', 20)->default('activa');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportadoras');
    }
};
