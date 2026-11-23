<?php

namespace Tests\Feature;

use App\Models\Permissao;
use App\Models\Utilizador;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Página principal (foto, sem demonstração) e matriz de permissões do administrador. */
class PermissoesELoginTest extends TestCase
{
    use RefreshDatabase;

    private Utilizador $admin;

    private Utilizador $gestor;

    protected function setUp(): void
    {
        parent::setUp();
        Permissao::limparCache();
        $this->seed(DatabaseSeeder::class);
        $this->admin = Utilizador::where('email', 'nelson.nhavene@moztransport.co.mz')->firstOrFail();
        $this->gestor = Utilizador::where('email', 'p.nhalicolo@moztransport.co.mz')->firstOrFail();
    }

    private function guardar(array $perm)
    {
        $r = $this->actingAs($this->admin)->post(route('admin.permissoes.guardar'), ['perm' => $perm]);
        Permissao::limparCache();

        return $r;
    }

    public function test_pagina_principal_tem_a_foto_e_nao_tem_demonstracao_nem_link_da_equipa(): void
    {
        $html = $this->get('/entrar')->assertOk()->getContent();
        $this->assertStringContainsString('img/autocarro.jpg', $html);
        $this->assertFileExists(public_path('img/autocarro.jpg'));
        foreach (['Sou da equipa', 'equipa (bilheteiro', 'Demonstração', 'demonstração', 'Moz@2026', 'painel/entrar'] as $proibido) {
            $this->assertStringNotContainsString($proibido, $html, "a página principal não devia conter «{$proibido}»");
        }

        $painel = $this->get('/painel/entrar')->assertOk()->getContent();
        foreach (['DEMONSTRAÇÃO', 'Demonstração', 'Moz@2026'] as $proibido) {
            $this->assertStringNotContainsString($proibido, $painel);
        }
        // o acesso da equipa continua a funcionar só com credenciais
        $this->post('/painel/entrar', ['email' => $this->gestor->email, 'password' => 'Moz@2026'])->assertRedirect();
    }

    public function test_interruptores_da_matriz_sao_visiveis(): void
    {
        $this->assertMatchesRegularExpression('/\.switch\s*\{[^}]*display:\s*inline-block/', file_get_contents(public_path('css/moz.css')));
        $html = $this->actingAs($this->admin)->get(route('admin.permissoes'))->assertOk()->getContent();
        $this->assertStringContainsString('name="perm[gestor][gerir_rotas]"', $html);
        $this->assertStringNotContainsString('name="perm[gestor][gerir_permissoes]"', $html, 'permissão que não se aplica ao gestor');
        $this->assertStringNotContainsString('name="perm[bilheteiro][gerir_rotas]"', $html);
    }

    public function test_retirar_uma_permissao_tira_o_acesso_e_o_menu_e_voltar_a_dar_repoe(): void
    {
        $this->actingAs($this->gestor)->get('/gestao/rotas')->assertOk();

        // desmarcar «Gerir rotas» do gestor (mantém as outras)
        $this->guardar(['gestor' => ['ver_dashboard' => 1, 'gerir_frota' => 1, 'gerir_pessoal' => 1, 'ver_relatorios' => 1], 'bilheteiro' => ['vender_bilhetes' => 1], 'cobrador' => ['ver_dashboard' => 1]])
            ->assertSessionHas('estado');
        $this->actingAs($this->gestor)->get('/gestao/rotas')->assertForbidden();
        $this->actingAs($this->gestor)->get('/gestao')->assertOk()->assertDontSee('Rotas e horários')->assertSee('Autocarros');

        // marcar outra vez
        $this->guardar(['gestor' => ['ver_dashboard' => 1, 'gerir_rotas' => 1, 'gerir_frota' => 1, 'gerir_pessoal' => 1, 'ver_relatorios' => 1], 'bilheteiro' => ['vender_bilhetes' => 1], 'cobrador' => ['ver_dashboard' => 1]]);
        $this->actingAs($this->gestor)->get('/gestao/rotas')->assertOk();
        // a matriz mostra o estado gravado
        $html = $this->actingAs($this->admin)->get(route('admin.permissoes'))->getContent();
        $this->assertMatchesRegularExpression('/name="perm\[gestor\]\[gerir_rotas\]" value="1" checked/', $html);
    }

    public function test_bilheteiro_e_cobrador_sem_permissao_ficam_sem_acesso_e_sem_menu(): void
    {
        $bil = Utilizador::where('perfil', 'bilheteiro')->first();
        $cob = Utilizador::where('perfil', 'cobrador')->first();
        $this->actingAs($bil)->get('/bilheteira/vender')->assertOk();
        $this->actingAs($cob)->get('/cobrador')->assertOk();

        $this->guardar(['gestor' => ['ver_dashboard' => 1]]); // todos os outros desmarcados
        $this->actingAs($bil)->get('/bilheteira/vender')->assertForbidden();
        $this->actingAs($cob)->get('/cobrador')->assertForbidden();
        // a página de entrada nunca leva a um 403 sem saída: vai para o perfil
        $this->assertSame(route('perfil'), $bil->fresh()->rotaInicial());
        $this->assertSame(route('perfil'), $cob->fresh()->rotaInicial());
        $this->actingAs($bil)->get('/perfil')->assertOk()->assertDontSee('Vender bilhete');
    }

    public function test_gestor_sem_dashboard_entra_numa_pagina_permitida(): void
    {
        $this->guardar(['gestor' => ['gerir_rotas' => 1], 'bilheteiro' => ['vender_bilhetes' => 1], 'cobrador' => ['ver_dashboard' => 1]]);
        $destino = $this->gestor->fresh()->rotaInicial();
        $this->assertSame(route('gestao.rotas'), $destino);
        $this->actingAs($this->gestor)->get($destino)->assertOk();
        $this->actingAs($this->gestor)->get('/gestao')->assertForbidden();
    }

    public function test_administrador_mantem_sempre_tudo_e_permissoes_inaplicaveis_sao_ignoradas(): void
    {
        // tentativa de dar «gerir permissões» ao gestor por pedido manual
        $this->guardar(['gestor' => ['gerir_permissoes' => 1, 'gerir_utilizadores' => 1, 'gerir_rotas' => 1], 'administrador' => []]);
        $this->assertFalse(Permissao::tem('gestor', 'gerir_permissoes'));
        $this->assertFalse(Permissao::tem('gestor', 'gerir_utilizadores'));
        foreach (array_keys(Permissao::CHAVES) as $chave) {
            $this->assertTrue(Permissao::tem('administrador', $chave), "admin sem {$chave}");
        }
        $this->actingAs($this->gestor)->get('/admin/permissoes')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/permissoes')->assertOk();
    }

    public function test_repor_valores_por_omissao(): void
    {
        $this->guardar(['gestor' => []]);
        $this->assertFalse(Permissao::tem('gestor', 'gerir_rotas'));
        $this->actingAs($this->admin)->post(route('admin.permissoes.repor'))->assertSessionHas('estado');
        Permissao::limparCache();
        $this->assertTrue(Permissao::tem('gestor', 'gerir_rotas'));
        $this->assertTrue(Permissao::tem('bilheteiro', 'vender_bilhetes'));
        $this->assertFalse(Permissao::tem('gestor', 'ver_todas_transportadoras'));
    }
}
