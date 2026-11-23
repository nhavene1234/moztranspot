<?php

namespace Tests\Feature;

use App\Services\BackupService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** O backup de SQLite copia o ficheiro da base de dados para storage/app/backups. */
class BackupTest extends TestCase
{
    public function test_backup_sqlite_copia_o_ficheiro(): void
    {
        $origem = tempnam(sys_get_temp_dir(), 'moz') . '.sqlite';
        file_put_contents($origem, 'conteudo-de-teste');
        config([
            'database.default' => 'backup_teste',
            'database.connections.backup_teste' => ['driver' => 'sqlite', 'database' => $origem, 'prefix' => '', 'foreign_key_constraints' => false],
        ]);
        DB::purge('backup_teste');

        $destino = (new BackupService)->executar();

        $this->assertFileExists($destino);
        $this->assertSame('conteudo-de-teste', file_get_contents($destino));

        @unlink($destino);
        @unlink($origem);
    }
}
