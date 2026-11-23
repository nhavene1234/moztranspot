<?php

namespace Tests\Feature;

use App\Models\Bilhete;
use App\Models\Permissao;
use App\Models\Reserva;
use App\Models\Utilizador;
use App\Models\Viagem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Teste de fumo: com os dados de demonstração, cada perfil consegue abrir todas as suas páginas
 * sem erros (views, SQL, rotas). Falhas são listadas todas no fim.
 */
class NavegacaoTest extends TestCase
{
    use RefreshDatabase;

    private array $falhas = [];

    private function abrir(Utilizador $u, string $url, int $esperado = 200): void
    {
        $r = $this->actingAs($u)->get($url);
        if ($r->getStatusCode() !== $esperado) {
            $msg = $r->exception ? get_class($r->exception) . ': ' . $r->exception->getMessage() . ' @ ' . basename($r->exception->getFile()) . ':' . $r->exception->getLine() : '';
            $this->falhas[] = "[{$u->perfil}] GET {$url} => {$r->getStatusCode()} (esperado {$esperado}) {$msg}";
        }
    }

    public function test_todas_as_paginas_abrem_com_dados_de_demonstracao(): void
    {
        Permissao::limparCache();
        $this->seed(DatabaseSeeder::class);

        $passageiro = Utilizador::where('email', 'ioramo.satar@email.co.mz')->first();
        $bilheteiro = Utilizador::where('email', 'manuel.zoca@moztransport.co.mz')->first();
        $cobrador = Utilizador::where('email', 'mikael.chongo@moztransport.co.mz')->first();
        $gestor = Utilizador::where('email', 'p.nhalicolo@moztransport.co.mz')->first();
        $admin = Utilizador::where('email', 'nelson.nhavene@moztransport.co.mz')->first();

        $futura = Viagem::futuras()->where('estado', 'agendada')->orderBy('data')->get()->first(fn ($v) => count($v->assentosOcupados()) <= 38);
        $hoje = now()->toDateString();

        // ---- visitante
        foreach (['/entrar', '/criar-conta', '/painel/entrar', '/recuperar', '/painel/recuperar'] as $url) {
            $r = $this->get($url);
            if ($r->getStatusCode() !== 200) {
                $this->falhas[] = "[visitante] GET {$url} => {$r->getStatusCode()} " . ($r->exception?->getMessage() ?? '');
            }
        }

        // ---- passageiro
        $minhas = Reserva::where('passageiro_id', $passageiro->id)->where('estado', 'confirmada')->get();
        $proxima = $minhas->first(fn ($r) => $r->viagem->partida_em->isFuture());
        foreach (['/app', '/app/bilhetes', '/app/bilhetes?tab=passadas', '/app/historico', "/app/viagens?origem=Maputo&destino=Beira&data={$hoje}", '/app/viagens?origem=Maputo&destino=Beira&data=' . now()->addDay()->toDateString() . '&ordem=preco', "/app/viagens/{$futura->id}/lugares", '/perfil'] as $url) {
            $this->abrir($passageiro, $url);
        }
        foreach ($minhas as $r) {
            $this->abrir($passageiro, "/app/bilhetes/{$r->id}");
        }
        if ($proxima) {
            $this->abrir($passageiro, "/app/reservas/{$proxima->id}/remarcar");
            $alt = Viagem::where('rota_id', $proxima->viagem->rota_id)->where('id', '!=', $proxima->viagem_id)->futuras()->first();
            if ($alt) {
                $this->abrir($passageiro, "/app/reservas/{$proxima->id}/remarcar/{$alt->id}");
            }
            $this->abrir($passageiro, "/app/reservas/{$proxima->id}/resultado");
        }

        // fluxo completo de reserva com a UI
        $livresP = array_values(array_diff($futura->autocarro->assentos()->pluck('id')->all(), $futura->assentosOcupados()));
        $this->actingAs($passageiro)->post("/app/viagens/{$futura->id}/reservar", ['assento_ids' => [end($livresP)]]);
        $pend = Reserva::where('passageiro_id', $passageiro->id)->where('estado', 'pendente')->first();
        if (! $pend) {
            $this->falhas[] = '[passageiro] reservar não criou reserva pendente';
        } else {
            $this->abrir($passageiro, "/app/reservas/{$pend->id}/pagamento");
            $this->actingAs($passageiro)->post("/app/reservas/{$pend->id}/pagar", ['metodo' => 'mpesa', 'telefone' => '84 000 0000']); // recusado (00)
            $this->abrir($passageiro, "/app/reservas/{$pend->id}/resultado");
            $this->actingAs($passageiro)->post("/app/reservas/{$pend->id}/pagar", ['metodo' => 'mpesa', 'telefone' => '84 123 4567']);
            $this->abrir($passageiro, "/app/reservas/{$pend->id}/resultado");
            $this->abrir($passageiro, "/app/bilhetes/{$pend->id}");
            // cancelar
            $r = $this->actingAs($passageiro)->post("/app/reservas/{$pend->id}/cancelar");
            $this->abrir($passageiro, '/app/cancelada', 302); // sem flash volta a /app/bilhetes
        }

        // ---- bilheteiro
        foreach (['/bilheteira/vender', "/bilheteira/vender?origem=Maputo&destino=Beira&data={$hoje}", "/bilheteira/vender/{$futura->id}/lugares", '/bilheteira/lugares', "/bilheteira/lugares?viagem={$futura->id}", "/bilheteira/lugares/{$futura->id}/estado", '/bilheteira/vendas', '/bilheteira/vendas/exportar', '/perfil'] as $url) {
            $this->abrir($bilheteiro, $url);
        }
        $grupo = Reserva::where('bilheteiro_id', $bilheteiro->id)->where('estado', 'confirmada')->value('grupo');
        if ($grupo) {
            $this->abrir($bilheteiro, "/bilheteira/venda/{$grupo}");
        } else {
            $this->falhas[] = '[bilheteiro] sem vendas do bilheteiro nos dados de demonstração';
        }
        // venda em 3 passos
        $assentos = $futura->autocarro->assentos()->orderBy('numero')->pluck('id')->all();
        $livres = array_values(array_diff($assentos, $futura->assentosOcupados()));
        $this->actingAs($bilheteiro)->post("/bilheteira/vender/{$futura->id}/lugares", ['assento_ids' => [$livres[0], $livres[1]]]);
        $this->abrir($bilheteiro, '/bilheteira/vender/pagamento');
        $r = $this->actingAs($bilheteiro)->post('/bilheteira/vender/pagamento', [
            'nome' => 'Cliente Teste', 'tipo_documento' => 'BI', 'documento' => '123456789A', 'telefone' => '+258 84 111 2222',
            'metodo' => 'numerario',
        ]);
        $g = Reserva::where('nome_passageiro', 'Cliente Teste')->value('grupo');
        if (! $g) {
            $this->falhas[] = '[bilheteiro] venda presencial não gerou reservas: ' . ($r->exception?->getMessage() ?? $r->getStatusCode());
        } else {
            $this->abrir($bilheteiro, "/bilheteira/venda/{$g}");
            $this->actingAs($bilheteiro)->post("/bilheteira/venda/{$g}/sms", ['telefone' => '+258 84 111 2222'])->assertSessionHasNoErrors();
        }

        // ---- cobrador
        $viagemCob = Viagem::where('cobrador_id', $cobrador->id)->whereDate('data', $hoje)->first();
        foreach (['/cobrador', '/cobrador?data=' . now()->addDay()->toDateString(), '/perfil'] as $url) {
            $this->abrir($cobrador, $url);
        }
        if ($viagemCob) {
            $this->abrir($cobrador, "/cobrador/viagens/{$viagemCob->id}");
            $b = Bilhete::whereHas('reserva', fn ($q) => $q->where('viagem_id', $viagemCob->id)->where('estado', 'confirmada'))->first();
            if ($b) {
                $this->actingAs($cobrador)->post("/cobrador/viagens/{$viagemCob->id}/validar", ['codigo' => $b->codigo_qr])->assertSessionHas('resultado');
                $this->abrir($cobrador, "/cobrador/viagens/{$viagemCob->id}");
            }
        } else {
            $this->falhas[] = '[cobrador] sem viagem hoje nos dados de demonstração';
        }

        // ---- gestor
        $tid = $gestor->transportadora_id;
        foreach (['/gestao', '/gestao/vendas', '/gestao/vendas?estado=confirmada&q=a', '/gestao/relatorios', '/gestao/relatorios?imprimir=1', '/gestao/relatorios/exportar', '/gestao/estatisticas', '/gestao/rotas', '/gestao/autocarros', '/gestao/motoristas', '/gestao/cobradores', '/perfil'] as $url) {
            $this->abrir($gestor, $url);
        }
        $this->abrir($gestor, '/gestao?transportadora=' . ($tid + 1), 403);

        // ---- administrador
        foreach (['/admin', '/admin/utilizadores', '/admin/utilizadores?q=a&perfil=gestor&estado=activo', '/admin/permissoes', '/admin/transportadoras', '/admin/auditoria', '/admin/auditoria?periodo=todos', '/admin/auditoria/exportar', '/admin/seguranca', '/gestao', '/gestao?transportadora=' . $tid, '/gestao/relatorios', '/gestao/estatisticas', '/gestao/rotas', '/gestao/autocarros', '/gestao/motoristas', '/gestao/cobradores', '/gestao/vendas', '/perfil', '/bilheteira/vender', '/bilheteira/vendas'] as $url) {
            $this->abrir($admin, $url);
        }

        $this->assertSame([], $this->falhas, "\n" . implode("\n", $this->falhas));
    }
}
