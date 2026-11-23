<?php

namespace Database\Seeders;

use App\Models\Assento;
use App\Models\Autocarro;
use App\Models\Motorista;
use App\Models\Bilhete;
use App\Models\Definicao;
use App\Models\Pagamento;
use App\Models\Reserva;
use App\Models\Rota;
use App\Models\Transportadora;
use App\Models\Utilizador;
use App\Models\Viagem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dados de demonstração. Todas as contas usam a palavra-passe  Moz@2026
 * Os nomes e números são fictícios.
 */
class DemoSeeder extends Seeder
{
    private const SENHA = 'Moz@2026';

    private array $rows = ['r' => [], 'p' => [], 'b' => []];

    private int $rid;

    private int $pid;

    private int $bid;

    /** viagem_id => [assento_id, ...] já ocupados */
    private array $ocupados = [];

    private array $bilheteiros = [];

    private array $nomes = ['Sulemane', 'Custódia', 'Armando', 'Albertina', 'Geraldo', 'Filomena', 'Amílcar', 'Zacarias', 'Maria', 'Sérgio', 'Cláudio', 'Amélia', 'Joana', 'Pedro', 'Celeste', 'Ismael', 'Lurdes', 'Tomás', 'Berta', 'Eduardo', 'Fátima', 'Hélio', 'Glória', 'Rui'];

    private array $apelidos = ['Issufo', 'Banze', 'Chivite', 'Alface', 'Tembe', 'Cabral', 'Mondlane', 'Gune', 'Mutola', 'Langa', 'Chirindza', 'Macie', 'Cossa', 'Sitoe', 'Mabunda', 'Nhaca', 'Muianga', 'Machava', 'Bila', 'Novela', 'Chilaule', 'Zandamela'];

    public function run(): void
    {
        DB::transaction(fn () => $this->gerar());
    }

    private function gerar(): void
    {
        mt_srand(2026); // dados repetíveis
        $hoje = Carbon::today();

        // ---------- Transportadoras ----------
        $nagi = Transportadora::create(['nome' => 'Nagi Investimentos', 'nuit' => '400123456', 'contacto' => '+258 21 400 100', 'endereco' => 'Terminal da Junta, Maputo', 'descricao' => 'Operadora Nacional']);
        $lalgy = Transportadora::create(['nome' => 'Lalgy', 'nuit' => '400234567', 'contacto' => '+258 21 400 200', 'endereco' => 'Terminal da Junta, Maputo', 'descricao' => 'Operadora Nacional']);
        $city = Transportadora::create(['nome' => 'CityLink', 'nuit' => '400345678', 'contacto' => '+258 21 400 300', 'endereco' => 'Terminal da Junta, Maputo', 'descricao' => 'Operadora Nacional']);

        // ---------- Utilizadores ----------
        $u = fn (array $d) => Utilizador::create($d + ['senha' => self::SENHA]);
        $ioramo = $u(['nome' => 'Ioramo Satar', 'email' => 'ioramo.satar@email.co.mz', 'telefone' => '+258 84 123 4567', 'documento' => '110100123456B', 'perfil' => 'passageiro']);
        $manuel = $u(['nome' => 'Manuel de Jesus Zoca', 'email' => 'manuel.zoca@moztransport.co.mz', 'telefone' => '+258 84 123 4567', 'perfil' => 'bilheteiro', 'transportadora_id' => $nagi->id, 'agencia' => 'Maputo – Terminal Junta']);
        $jose = $u(['nome' => 'Jose Junior', 'email' => 'jose.junior@moztransport.co.mz', 'perfil' => 'bilheteiro', 'transportadora_id' => $city->id, 'agencia' => 'Maputo – Terminal Junta', 'estado' => 'bloqueado']);
        $mikael = $u(['nome' => 'Mikael Chongo', 'email' => 'mikael.chongo@moztransport.co.mz', 'perfil' => 'cobrador', 'transportadora_id' => $lalgy->id]);
        $helder = $u(['nome' => 'Hélder Sitoe', 'email' => 'helder.sitoe@moztransport.co.mz', 'perfil' => 'cobrador', 'transportadora_id' => $nagi->id]);
        $dulce = $u(['nome' => 'Dulce Mabunda', 'email' => 'dulce.mabunda@moztransport.co.mz', 'perfil' => 'cobrador', 'transportadora_id' => $city->id]);
        $u(['nome' => 'Peresterello Nhalicolo', 'email' => 'p.nhalicolo@moztransport.co.mz', 'perfil' => 'gestor', 'transportadora_id' => $lalgy->id]);
        $u(['nome' => 'Arlindo Cossa', 'email' => 'arlindo.cossa@moztransport.co.mz', 'perfil' => 'gestor', 'transportadora_id' => $nagi->id]);
        $u(['nome' => 'Telma Bila', 'email' => 'telma.bila@moztransport.co.mz', 'perfil' => 'gestor', 'transportadora_id' => $city->id]);
        $admin = $u(['nome' => 'Nelson Nhavene', 'email' => 'nelson.nhavene@moztransport.co.mz', 'perfil' => 'administrador']);
        $this->bilheteiros = [$manuel->id, $manuel->id, $manuel->id, $manuel->id, $jose->id];

        // ---------- Rotas, autocarros e viagens ----------
        // destino => [origem, km, minutos, preço Nagi, preço Lalgy, preço CityLink]
        $catalogo = [
            ['Maputo', 'Beira', 1190, 960, 2500, 2350, 2700],
            ['Maputo', 'Xai-Xai', 225, 210, 350, 350, 380],
            ['Maputo', 'Inhambane', 480, 360, 650, 650, 700],
            ['Maputo', 'Chimoio', 1040, 840, 2000, null, null],
            ['Maputo', 'Tete', 1630, 1320, null, 3100, null],
            ['Maputo', 'Quelimane', 1900, 1560, null, null, 2850],
            ['Maputo', 'Nampula', 2300, 2040, 3500, 3400, 3600],
            ['Beira', 'Nampula', 1000, 840, 1900, 1850, 2000],
        ];
        $empresas = [
            [$nagi, 'NAG', 4, ['05:30', '14:00'], 'Executivo · AC', $helder],
            [$lalgy, 'LAL', 5, ['07:00', '16:30'], 'Semi-luxo', $mikael],
            [$city, 'CTL', 6, ['04:30', '20:00'], 'Executivo', $dulce],
        ];
        $modelos = ['Yutong ZK6122', 'Higer KLQ6129', 'Scania K360', 'Marcopolo Paradiso'];

        $viagens = []; // lista de [Viagem, empresaNome, rotaDestino, slotIdx]
        $buses = [];   // chave rota_id|slot => autocarro
        $seq = 1000;

        // Motoristas de demonstração (2 por transportadora; um deles com a carta prestes a caducar)
        $nomesMotoristas = [['Carlos Matola', 'Alberto Cossa'], ['Joaquim Tembe', 'Bernardo Nhaca'], ['Armando Zandamela', 'Fernando Muianga']];
        $motoristasEmp = [];
        foreach ($empresas as $i => [$emp]) {
            foreach ($nomesMotoristas[$i % 3] as $k => $nomeM) {
                $motoristasEmp[$emp->id][] = Motorista::create([
                    'nome' => $nomeM, 'telefone' => '+258 84 ' . random_int(100, 999) . ' ' . random_int(1000, 9999),
                    'documento' => random_int(100000000, 999999999) . 'A', 'carta_numero' => 'MZ-' . random_int(100000, 999999),
                    'carta_categoria' => 'D', 'carta_validade' => $hoje->copy()->addDays($i === 0 && $k === 0 ? 20 : 400 + $k * 120),
                    'transportadora_id' => $emp->id,
                ]);
            }
        }

        foreach ($empresas as [$emp, $prefixo, $idx, $horas, $classe, $cobrador]) {
            $col = $idx; // índice da coluna de preço no catálogo
            foreach ($catalogo as $c) {
                $preco = $c[$col];
                if ($preco === null) {
                    continue;
                }
                $rota = Rota::create(['origem' => $c[0], 'destino' => $c[1], 'distancia_km' => $c[2], 'duracao_minutos' => $c[3], 'preco_base' => $preco, 'transportadora_id' => $emp->id]);
                foreach ($horas as $slot => $hora) {
                    $bus = Autocarro::create(['matricula' => sprintf('%s-%04d', $prefixo, ++$seq), 'modelo' => $modelos[array_rand($modelos)], 'capacidade_total' => 44, 'transportadora_id' => $emp->id]);
                    $assentos = [];
                    for ($n = 1; $n <= 44; $n++) {
                        $assentos[] = ['numero' => $n, 'tipo' => in_array(($n - 1) % 4, [0, 3]) ? 'janela' : 'corredor', 'autocarro_id' => $bus->id];
                    }
                    DB::table('assentos')->insert($assentos);
                    $buses[$rota->id . '|' . $slot] = $bus;

                    $classeViagem = ($emp->id === $city->id && $slot === 1) ? 'Executivo nocturno' : $classe;
                    for ($d = -14; $d <= 10; $d++) {
                        $dia = $hoje->copy()->addDays($d);
                        $v = Viagem::create([
                            'data' => $dia->toDateString(), 'hora_partida' => $hora . ':00', 'preco' => $preco, 'classe' => $classeViagem,
                            'estado' => $d < 0 ? 'concluida' : 'agendada', 'rota_id' => $rota->id,
                            'autocarro_id' => $bus->id, 'cobrador_id' => $cobrador->id,
                            'motorista_id' => $motoristasEmp[$emp->id][$slot % 2]->id,
                        ]);
                        $viagens[] = [$v, $emp->nome, $rota, $slot, $d];
                    }
                }
            }
        }

        // ---------- Início dos contadores para inserção em massa ----------
        $this->rid = (int) Reserva::max('id') + 1;
        $this->pid = (int) Pagamento::max('id') + 1;
        $this->bid = (int) Bilhete::max('id') + 1;

        $seatIds = [];
        foreach ($buses as $bus) {
            $seatIds[$bus->id] = Assento::where('autocarro_id', $bus->id)->orderBy('numero')->pluck('id')->all();
        }

        // ---------- Viagens da passageira de demonstração ----------
        $acha = fn (string $emp, string $origem, string $destino, int $slot, int $dia) => collect($viagens)->first(
            fn ($x) => $x[1] === $emp && $x[2]->origem === $origem && $x[2]->destino === $destino && $x[3] === $slot && $x[4] === $dia
        )[0];

        $this->venda($acha('Nagi Investimentos', 'Maputo', 'Beira', 0, 4), $seatIds, 26, $ioramo, 'mpesa'); // lugar 7B
        $this->venda($acha('Lalgy', 'Maputo', 'Nampula', 1, 6), $seatIds, 14, $ioramo, 'emola');
        $this->venda($acha('Lalgy', 'Maputo', 'Xai-Xai', 0, -10), $seatIds, 9, $ioramo, 'mpesa');
        $this->venda($acha('Nagi Investimentos', 'Maputo', 'Inhambane', 1, -7), $seatIds, 21, $ioramo, 'mpesa');
        $this->venda($acha('Lalgy', 'Maputo', 'Tete', 0, -12), $seatIds, 30, $ioramo, 'mpesa', 'cancelada');
        $this->venda($acha('CityLink', 'Maputo', 'Quelimane', 0, -9), $seatIds, 12, $ioramo, 'mpesa', 'expirada');

        // ---------- Vendas aleatórias (ocupação realista) ----------
        foreach ($viagens as [$v, $empNome, $rota, $slot, $d]) {
            $cap = 44;
            if ($d < 0) {
                $frac = mt_rand(45, 92) / 100;
            } elseif ($d <= 2) {
                $frac = mt_rand(55, 98) / 100;
            } else {
                $frac = mt_rand(8, 60) / 100;
            }
            $dow = (int) $v->data->dayOfWeekIso;
            if ($dow === 5 || $dow === 7) {
                $frac = min(1, $frac * 1.15);
            }
            if ($empNome === 'CityLink' && $slot === 1 && $rota->destino === 'Beira' && $d >= 0 && $d % 3 === 0) {
                $frac = 1.0; // viagens esgotadas para demonstração
            }
            $this->preencher($v, $seatIds[$v->autocarro_id], (int) round($cap * $frac));
        }

        // ---------- Histórico mensal (sazonalidade: pico em Dezembro) ----------
        $sazonal = [1 => 0.75, 2 => 0.55, 3 => 0.5, 4 => 0.7, 5 => 0.5, 6 => 0.55, 7 => 0.65, 8 => 0.55, 9 => 0.6, 10 => 0.55, 11 => 0.7, 12 => 0.97];
        $rotasTodas = Rota::all();
        foreach ($rotasTodas as $rota) {
            $bus = $buses[$rota->id . '|0'];
            for ($m = 2; $m <= 10; $m++) {
                $dia = $hoje->copy()->subMonthsNoOverflow($m)->day(15);
                $v = Viagem::create(['data' => $dia->toDateString(), 'hora_partida' => '06:00:00', 'preco' => $rota->preco_base, 'classe' => 'Executivo', 'estado' => 'concluida', 'rota_id' => $rota->id, 'autocarro_id' => $bus->id, 'cobrador_id' => null]);
                $n = (int) round(44 * min(1, $sazonal[(int) $dia->month] * mt_rand(90, 105) / 100));
                $this->preencher($v, $seatIds[$bus->id], $n);
            }
        }
        $this->despejar(true);

        // ---------- Auditoria e definições ----------
        $agora = now();
        DB::table('auditoria')->insert([
            ['utilizador_id' => $admin->id, 'utilizador_nome' => $admin->nome, 'acao' => 'Login realizado', 'detalhe' => null, 'cor' => 'verde', 'ip' => '197.249.12.33', 'created_at' => $agora->copy()->subMinutes(50)],
            ['utilizador_id' => $manuel->id, 'utilizador_nome' => $manuel->nome, 'acao' => 'Venda registada', 'detalhe' => 'Maputo → Beira · lugar 6D', 'cor' => 'verde', 'ip' => '197.249.15.112', 'created_at' => $agora->copy()->subMinutes(35)],
            ['utilizador_id' => null, 'utilizador_nome' => 'Peresterello Nhalicolo', 'acao' => 'Rota desactivada', 'detalhe' => 'Beira → Tete', 'cor' => 'laranja', 'ip' => '197.249.20.45', 'created_at' => $agora->copy()->subHours(2)],
            ['utilizador_id' => $admin->id, 'utilizador_nome' => $admin->nome, 'acao' => 'Utilizador bloqueado', 'detalhe' => 'Jose Junior', 'cor' => 'vermelho', 'ip' => '197.249.12.33', 'created_at' => $agora->copy()->subHours(3)],
            ['utilizador_id' => null, 'utilizador_nome' => 'Sistema Automático', 'acao' => 'Backup diário executado', 'detalhe' => null, 'cor' => 'verde', 'ip' => null, 'created_at' => $agora->copy()->subHours(9)],
        ]);
        Definicao::gravar('ultimo_backup', $agora->copy()->subHours(9)->format('d/m/Y H:i'));
    }

    /** Venda individual com dados exactos (usada nas contas de demonstração). */
    private function venda(Viagem $v, array $seatIds, int $numeroAssento, Utilizador $p, string $metodo, string $estado = 'confirmada'): void
    {
        $assentoId = $seatIds[$v->autocarro_id][$numeroAssento - 1];
        $this->ocupados[$v->id][] = $assentoId;
        $dataVenda = Carbon::parse($v->data)->min(Carbon::today())->subDays(3)->setTime(10, 15);
        $this->adicionar($v, $assentoId, $dataVenda, $metodo, null, $p->nome, $p->documento, $p->telefone, $p->id, $estado);
    }

    private function preencher(Viagem $v, array $assentoIds, int $quantos): void
    {
        $ja = $this->ocupados[$v->id] ?? [];
        $livres = array_values(array_diff($assentoIds, $ja));
        shuffle($livres);
        $quantos = max(0, min($quantos - count($ja), count($livres)));

        for ($i = 0; $i < $quantos; $i++) {
            $assentoId = $livres[$i];
            $this->ocupados[$v->id][] = $assentoId;

            $dataVenda = Carbon::parse($v->data)->min(Carbon::today())->subDays(mt_rand(0, 7))->setTime(mt_rand(6, 18), mt_rand(0, 59));
            $presencial = mt_rand(1, 100) <= 55;
            $metodo = $presencial
                ? $this->sortear(['numerario' => 40, 'mpesa' => 35, 'emola' => 15, 'cartao' => 10])
                : $this->sortear(['mpesa' => 60, 'emola' => 30, 'cartao' => 10]);
            $bilheteiro = $presencial ? $this->bilheteiros[array_rand($this->bilheteiros)] : null;
            $estado = mt_rand(1, 100) <= 3 ? 'cancelada' : 'confirmada';
            $nome = $this->nomes[array_rand($this->nomes)] . ' ' . $this->apelidos[array_rand($this->apelidos)];
            $doc = '1101' . mt_rand(1000000, 9999999) . chr(mt_rand(65, 90));
            $tel = '+258 8' . mt_rand(2, 7) . ' ' . mt_rand(100, 999) . ' ' . mt_rand(1000, 9999);

            $this->adicionar($v, $assentoId, $dataVenda, $metodo, $bilheteiro, $nome, $doc, $tel, null, $estado);
        }
        $this->despejar();
    }

    private function adicionar(Viagem $v, int $assentoId, Carbon $dataVenda, string $metodo, ?int $bilheteiro, string $nome, ?string $doc, ?string $tel, ?int $passageiroId, string $estado): void
    {
        $f = fn (Carbon $c) => $c->format('Y-m-d H:i:s');
        $ativa = $estado === 'confirmada' ? 1 : null;
        $agora = $f(now());

        $this->rows['r'][] = [
            'id' => $this->rid, 'grupo' => (string) Str::uuid(), 'passageiro_id' => $passageiroId, 'bilheteiro_id' => $bilheteiro,
            'viagem_id' => $v->id, 'assento_id' => $assentoId, 'nome_passageiro' => $nome, 'documento' => $doc, 'telefone' => $tel,
            'estado' => $estado, 'ativa' => $ativa, 'expira_em' => null, 'data_reserva' => $f($dataVenda),
            'created_at' => $f($dataVenda), 'updated_at' => $agora,
        ];

        if ($estado !== 'expirada') {
            $this->rows['p'][] = [
                'id' => $this->pid++, 'reserva_id' => $this->rid, 'valor' => $v->preco, 'metodo' => $metodo,
                'estado' => $estado === 'cancelada' ? 'reembolsado' : 'pago', 'data_pagamento' => $f($dataVenda),
                'referencia_transacao' => 'SIM-' . strtoupper(Str::random(10)), 'codigo_erro' => null,
                'created_at' => $f($dataVenda), 'updated_at' => $agora,
            ];
            $passada = Carbon::parse($v->data)->isBefore(Carbon::today());
            $estadoBilhete = $estado === 'cancelada' ? 'cancelado' : ($passada && mt_rand(1, 100) <= 95 ? 'usado' : 'valido');
            $this->rows['b'][] = [
                'id' => $this->bid, 'reserva_id' => $this->rid, 'codigo_qr' => sprintf('MT-%d-%06d', $dataVenda->year, $this->bid),
                'data_emissao' => $f($dataVenda), 'estado' => $estadoBilhete,
                'validado_em' => $estadoBilhete === 'usado' ? $f(Carbon::parse($v->data->format('Y-m-d') . ' ' . $v->hora_partida)->subMinutes(20)) : null,
                'validado_por' => null, 'created_at' => $f($dataVenda), 'updated_at' => $agora,
            ];
            $this->bid++;
        }
        $this->rid++;
    }

    private function despejar(bool $forcar = false): void
    {
        if (! $forcar && count($this->rows['r']) < 600) {
            return;
        }
        foreach (['r' => 'reservas', 'p' => 'pagamentos', 'b' => 'bilhetes'] as $k => $tabela) {
            foreach (array_chunk($this->rows[$k], 50) as $bloco) {
                DB::table($tabela)->insert($bloco);
            }
            $this->rows[$k] = [];
        }
    }

    private function sortear(array $pesos): string
    {
        $r = mt_rand(1, array_sum($pesos));
        foreach ($pesos as $k => $p) {
            if (($r -= $p) <= 0) {
                return $k;
            }
        }

        return array_key_first($pesos);
    }
}
