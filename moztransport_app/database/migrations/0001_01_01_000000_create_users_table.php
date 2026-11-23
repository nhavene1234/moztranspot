<?php

use Illuminate\Database\Migrations\Migration;

/*
 * A tabela "users" do Laravel não é usada neste projecto: os utilizadores
 * ficam na tabela "utilizadores" (modelo App\Models\Utilizador), conforme o MER.
 * Este ficheiro substitui a migration original e não faz nada.
 */
return new class extends Migration
{
    public function up(): void
    {
    }

    public function down(): void
    {
    }
};
