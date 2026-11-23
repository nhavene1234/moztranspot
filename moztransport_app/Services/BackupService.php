<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Cópias de segurança (RNF11). SQLite: copia o ficheiro. MySQL: usa mysqldump, se estiver instalado. */
class BackupService
{
    public function executar(): string
    {
        $pasta = storage_path('app/backups');
        if (! is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }
        $nome = 'moztransport-' . now()->format('Ymd-His');
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $origem = DB::connection()->getDatabaseName();
            if (! is_file($origem)) {
                throw new \RuntimeException('Ficheiro da base de dados SQLite não encontrado.');
            }
            $destino = "{$pasta}/{$nome}.sqlite";
            if (! copy($origem, $destino)) {
                throw new \RuntimeException('Não foi possível copiar a base de dados.');
            }

            return $destino;
        }

        if ($driver === 'mysql') {
            $c = config('database.connections.' . config('database.default'));
            $destino = "{$pasta}/{$nome}.sql";
            $cmd = ['mysqldump', '--host=' . $c['host'], '--port=' . $c['port'], '--user=' . $c['username'], '--single-transaction', '--result-file=' . $destino, $c['database']];
            $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['MYSQL_PWD' => (string) $c['password'], 'PATH' => getenv('PATH')]);
            if (! is_resource($proc)) {
                throw new \RuntimeException('Não foi possível executar o mysqldump.');
            }
            stream_get_contents($pipes[1]);
            $erro = stream_get_contents($pipes[2]);
            $codigo = proc_close($proc);
            if ($codigo !== 0) {
                throw new \RuntimeException('mysqldump falhou: ' . trim($erro ?: 'verifique se o mysqldump está instalado e no PATH.'));
            }

            return $destino;
        }

        throw new \RuntimeException("Backup automático não suportado para o driver {$driver}.");
    }
}
