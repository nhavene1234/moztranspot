<?php

namespace Tests\Feature;

use App\Models\Assento;
use App\Models\Autocarro;
use App\Models\Bilhete;
use App\Models\Permissao;
use App\Models\Reserva;
use App\Models\Rota;
use App\Models\Transportadora;
use App\Models\Utilizador;
use App\Models\Viagem;
use App\Services\LugarIndisponivel;
use App\Services\ReservaService;
use Database\Seeders\ConfiguracaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testa as regras de negócio principais do documento:
 * RN02 (lugar nunca vendido duas vezes), RF10/RF11 (pagar e receber bilhete) e RN11 (QR só valida uma vez).
 */
class FluxoVendaTest extends TestCase
{
    use RefreshDatabase;

    private Viagem $viagem;

    private Utilizador $passageiro;

    private Utilizador $cobrador;

    private array $assentos = [];

    protected function setUp(): void
    {
        parent::setUp();
        Permissao::limparCache();
        $this->seed(ConfiguracaoSeeder::class);

        $t = Transportadora::create(['nome' => 'Teste Lda']);
        $rota = Rota::create(['origem' => 'Maputo', 'destino' => 'Beira', 'distancia_km' => 1190, 'duracao_minutos' => 960, 'preco_base' => 2500, 'transportadora_id' => $t->id]);
        $bus = Autocarro::create(['matricula' => 'TST-0001', 'capacidade_total' => 4, 'transportadora_id' => $t->id]);
        foreach ([1, 2, 3, 4] as $n) {
            $this->assentos[] = Assento::create(['numero' => $n, 'tipo' => 'janela', 'autocarro_id' => $bus->id])->id;
        }
        $this->cobrador = Utilizador::create(['nome' => 'Cobrador Teste', 'email' => 'cobrador@teste.mz', 'senha' => 'Teste@1234', 'perfil' => 'cobrador', 'transportadora_id' => $t->id]);
        $this->passageiro = Utilizador::create(['nome' => 'Passageiro Teste', 'email' => 'passageiro@teste.mz', 'senha' => 'Teste@1234', 'perfil' => 'passageiro']);
        $this->viagem = Viagem::create([
            'data' => now()->addDay()->toDateString(), 'hora_partida' => '10:00:00', 'preco' => 2500, 'classe' => 'Executivo',
            'estado' => 'agendada', 'rota_id' => $rota->id, 'autocarro_id' => $bus->id, 'cobrador_id' => $this->cobrador->id,
        ]);
    }

    public function test_o_mesmo_lugar_nao_pode_ser_vendido_duas_vezes(): void
    {
        $servico = app(ReservaService::class);
        $servico->bloquear($this->viagem, [$this->assentos[0]], ['nome_passageiro' => 'A']);

        $this->expectException(LugarIndisponivel::class);
        $servico->bloquear($this->viagem, [$this->assentos[0]], ['nome_passageiro' => 'B']);
    }

    public function test_passageiro_reserva_paga_e_recebe_bilhete(): void
    {
        $this->actingAs($this->passageiro)
            ->post(route('app.reservar', $this->viagem), ['assento_ids' => [$this->assentos[0]]])
            ->assertRedirect();

        $reserva = Reserva::firstOrFail();
        $this->assertSame('pendente', $reserva->estado);

        $this->actingAs($this->passageiro)
            ->post(route('app.pagar', $reserva), ['metodo' => 'mpesa', 'telefone' => '84 123 4567'])
            ->assertRedirect(route('app.resultado', $reserva));

        $this->assertSame('confirmada', $reserva->fresh()->estado);
        $this->assertSame(1, Bilhete::count());
    }

    public function test_pagamento_com_numero_invalido_nao_emite_bilhete(): void
    {
        $this->actingAs($this->passageiro)->post(route('app.reservar', $this->viagem), ['assento_ids' => [$this->assentos[0]]]);
        $reserva = Reserva::firstOrFail();

        $this->actingAs($this->passageiro)
            ->post(route('app.pagar', $reserva), ['metodo' => 'mpesa', 'telefone' => '87 123 4567']); // 87 = e-Mola

        $this->assertSame('pendente', $reserva->fresh()->estado);
        $this->assertSame(0, Bilhete::count());
    }

    public function test_bilhete_so_pode_ser_validado_uma_vez(): void
    {
        $servico = app(ReservaService::class);
        $reserva = $servico->bloquear($this->viagem, [$this->assentos[1]], ['nome_passageiro' => 'X', 'passageiro_id' => $this->passageiro->id])->first();
        $codigo = $servico->confirmar($reserva, 'numerario')->bilhete->codigo_qr;

        $this->actingAs($this->cobrador)
            ->postJson(route('cobrador.validar', $this->viagem), ['codigo' => $codigo])
            ->assertJson(['ok' => true]);

        $this->actingAs($this->cobrador)
            ->postJson(route('cobrador.validar', $this->viagem), ['codigo' => $codigo])
            ->assertJson(['ok' => false]);
    }

    public function test_passageiro_nao_acede_ao_painel_da_equipa(): void
    {
        $this->actingAs($this->passageiro)->get(route('admin.visao'))->assertForbidden();
        $this->actingAs($this->passageiro)->get(route('gestao.dashboard'))->assertForbidden();
    }
}
