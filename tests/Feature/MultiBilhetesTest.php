<?php

namespace Tests\Feature;

use App\Models\Assento;
use App\Models\Autocarro;
use App\Models\Bilhete;
use App\Models\Pagamento;
use App\Models\Permissao;
use App\Models\Reserva;
use App\Models\Rota;
use App\Models\Transportadora;
use App\Models\Utilizador;
use App\Models\Viagem;
use Database\Seeders\ConfiguracaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** O passageiro pode comprar até 5 bilhetes de uma só vez (um pagamento, vários bilhetes). */
class MultiBilhetesTest extends TestCase
{
    use RefreshDatabase;

    private Viagem $viagem;

    private Utilizador $passageiro;

    private array $assentos = [];

    protected function setUp(): void
    {
        parent::setUp();
        Permissao::limparCache();
        $this->seed(ConfiguracaoSeeder::class);

        $t = Transportadora::create(['nome' => 'Teste Lda']);
        $rota = Rota::create(['origem' => 'Maputo', 'destino' => 'Beira', 'distancia_km' => 1190, 'duracao_minutos' => 960, 'preco_base' => 2500, 'transportadora_id' => $t->id]);
        $bus = Autocarro::create(['matricula' => 'TST-0001', 'capacidade_total' => 8, 'transportadora_id' => $t->id]);
        foreach (range(1, 8) as $n) {
            $this->assentos[] = Assento::create(['numero' => $n, 'tipo' => 'janela', 'autocarro_id' => $bus->id])->id;
        }
        $this->passageiro = Utilizador::create(['nome' => 'Passageiro Teste', 'email' => 'passageiro@teste.mz', 'senha' => 'Teste@1234', 'perfil' => 'passageiro', 'documento' => '123456789A', 'telefone' => '+258 84 111 2222']);
        $this->viagem = Viagem::create([
            'data' => now()->addDays(3)->toDateString(), 'hora_partida' => '10:00:00', 'preco' => 2500, 'classe' => 'Executivo',
            'estado' => 'agendada', 'rota_id' => $rota->id, 'autocarro_id' => $bus->id,
        ]);
    }

    private function reservar(array $ids, ?Utilizador $quem = null)
    {
        return $this->actingAs($quem ?? $this->passageiro)->post(route('app.reservar', $this->viagem), ['assento_ids' => $ids]);
    }

    public function test_compra_de_cinco_bilhetes_com_um_so_pagamento(): void
    {
        $ids = array_slice($this->assentos, 0, 5);
        $this->get(route('app.lugares', $this->viagem)); // visitante é mandado entrar
        $this->actingAs($this->passageiro)->get(route('app.lugares', $this->viagem))->assertOk()->assertSee('assento_ids[]', false)->assertSee('até 5 lugares');

        $this->reservar($ids)->assertRedirect();
        $this->assertSame(5, Reserva::where('estado', 'pendente')->count());
        $this->assertSame(1, Reserva::distinct('grupo')->count('grupo'), 'as 5 reservas pertencem à mesma compra');

        $primeira = Reserva::orderBy('id')->first();
        $pag = $this->actingAs($this->passageiro)->get(route('app.pagamento', $primeira));
        $pag->assertOk()->assertSee('12.500')->assertSee('5 ×', false);

        $nomes = Reserva::orderBy('id')->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => $i === 1 ? 'Maria Convidada' : 'Passageiro Teste'])->all();
        $this->actingAs($this->passageiro)
            ->post(route('app.pagar', $primeira), ['metodo' => 'mpesa', 'telefone' => '84 123 4567', 'nomes' => $nomes])
            ->assertRedirect(route('app.resultado', $primeira));

        $this->assertSame(5, Reserva::where('estado', 'confirmada')->count());
        $this->assertSame(5, Bilhete::count());
        $this->assertEqualsWithDelta(12500, Pagamento::where('estado', 'pago')->sum('valor'), 0.01);
        $this->assertSame(1, Pagamento::where('estado', 'pago')->distinct('referencia_transacao')->count('referencia_transacao'), 'uma só transacção');
        $segunda = Reserva::orderBy('id')->skip(1)->first();
        $this->assertSame('Maria Convidada', $segunda->nome_passageiro);
        $this->assertNull($segunda->documento, 'o documento do titular não é copiado para outro passageiro');

        $this->actingAs($this->passageiro)->get(route('app.resultado', $primeira))->assertOk()->assertSee('Maria Convidada')->assertSee('12.500');
        foreach (Reserva::all() as $r) {
            $this->actingAs($this->passageiro)->get(route('app.bilhete', $r))->assertOk()->assertSee($r->bilhete->codigo_qr);
        }
        $this->actingAs($this->passageiro)->get(route('app.bilhetes'))->assertOk();
        // abrir a página de pagamento de uma compra já paga leva ao resultado
        $this->actingAs($this->passageiro)->get(route('app.pagamento', $primeira))->assertRedirect(route('app.resultado', $primeira));
    }

    public function test_seis_lugares_sao_recusados(): void
    {
        $this->reservar(array_slice($this->assentos, 0, 6))->assertSessionHasErrors('assento_ids');
        $this->assertSame(0, Reserva::count());
    }

    public function test_sem_lugares_ou_lugar_repetido_e_recusado(): void
    {
        $this->reservar([])->assertSessionHasErrors('assento_ids');
        $this->reservar([$this->assentos[0], $this->assentos[0]])->assertSessionHasErrors();
        $this->assertSame(0, Reserva::count());
    }

    public function test_um_lugar_ocupado_cancela_a_compra_toda(): void
    {
        $outro = Utilizador::create(['nome' => 'Outro', 'email' => 'outro@teste.mz', 'senha' => 'Teste@1234', 'perfil' => 'passageiro']);
        $this->reservar([$this->assentos[2]], $outro);

        $this->reservar([$this->assentos[0], $this->assentos[1], $this->assentos[2]])->assertSessionHasErrors('assento_ids');
        $this->assertSame(1, Reserva::where('ativa', 1)->count(), 'só ficou a reserva do outro passageiro');
        $this->assertSame(0, Reserva::where('passageiro_id', $this->passageiro->id)->where('ativa', 1)->count());
    }

    public function test_pagamento_recusado_mantem_os_lugares_e_permite_tentar_de_novo(): void
    {
        $this->reservar(array_slice($this->assentos, 0, 3));
        $primeira = Reserva::orderBy('id')->first();

        $this->actingAs($this->passageiro)->post(route('app.pagar', $primeira), ['metodo' => 'mpesa', 'telefone' => '84 123 4500']); // 00 = recusado
        $this->assertSame(3, Reserva::where('estado', 'pendente')->count());
        $this->assertSame(0, Bilhete::count());
        $this->actingAs($this->passageiro)->get(route('app.resultado', $primeira))->assertOk()->assertSee('Tentar novamente');

        $this->actingAs($this->passageiro)->post(route('app.pagar', $primeira), ['metodo' => 'mpesa', 'telefone' => '84 123 4567']);
        $this->assertSame(3, Reserva::where('estado', 'confirmada')->count());
        $this->assertSame(3, Bilhete::count());
    }

    public function test_outro_passageiro_nao_ve_nem_paga_a_compra(): void
    {
        $this->reservar(array_slice($this->assentos, 0, 2));
        $primeira = Reserva::orderBy('id')->first();
        $outro = Utilizador::create(['nome' => 'Outro', 'email' => 'outro@teste.mz', 'senha' => 'Teste@1234', 'perfil' => 'passageiro']);

        $this->actingAs($outro)->get(route('app.pagamento', $primeira))->assertForbidden();
        $this->actingAs($outro)->post(route('app.pagar', $primeira), ['metodo' => 'mpesa', 'telefone' => '84 123 4567'])->assertForbidden();
        $this->assertSame(0, Bilhete::count());
    }

    public function test_compra_expirada_nao_emite_bilhetes(): void
    {
        $this->reservar(array_slice($this->assentos, 0, 2));
        Reserva::query()->update(['expira_em' => now()->subMinute()]);
        $primeira = Reserva::orderBy('id')->first();

        $this->actingAs($this->passageiro)->post(route('app.pagar', $primeira), ['metodo' => 'mpesa', 'telefone' => '84 123 4567'])->assertRedirect(route('app.inicio'));
        $this->assertSame(0, Bilhete::count());
        $this->assertSame(0, Reserva::where('ativa', 1)->count(), 'os lugares foram libertados');
    }
}
