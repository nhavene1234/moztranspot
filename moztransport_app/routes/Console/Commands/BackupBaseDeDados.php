<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\Definicao;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupBaseDeDados extends Command
{
    protected $signature = 'moz:backup';

    protected $description = 'Cria uma cópia de segurança da base de dados (RNF11)';

    public function handle(BackupService $backup): int
    {
        try {
            $ficheiro = $backup->executar();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        Definicao::gravar('ultimo_backup', now()->format('d/m/Y H:i'));
        Auditoria::registar('Backup diário executado', basename($ficheiro));
        $this->info("Backup criado: {$ficheiro}");

        return self::SUCCESS;
    }
}
