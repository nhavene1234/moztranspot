<?php

namespace Tests\Feature;

use App\Models\Autocarro;
use App\Models\Motorista;
use App\Models\Permissao;
use App\Models\Utilizador;
use App\Models\Viagem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** CRUD do gestor: autocarros (com livrete), motoristas e cobradores. */
class FrotaPessoalTest extends TestCase
{
    use RefreshDatabase;

    private array $falhas = [];

    private function ok(bool $cond, string $msg): void
    {
        if (! $cond) {
            $this->falhas[] = $msg;
        }
    }

    public function test_autocarros_e_livrete(): void
    {
        Storage::fake('local');
        Permissao::limparCache();
        $this->seed(DatabaseSeeder::class);
        $g = Utilizador::where('email', 'p.nhalicolo@moztransport.co.mz')->first();
        $outroGestor = Utilizador::where('perfil', 'gestor')->where('transportadora_id', '!=', $g->transportadora_id)->first();
        $passageiro = Utilizador::where('perfil', 'passageiro')->first();

        // criar com livrete PDF
        $r = $this->actingAs($g)->post('/gestao/autocarros', ['matricula' => ' ab-123-mp ', 'modelo' => 'Yutong', 'ano' => 2019, 'livrete' => UploadedFile::fake()->create('livrete.pdf', 300, 'application/pdf')]);
        $bus = Autocarro::where('matricula', 'AB-123-MP')->first();
        $this->ok((bool) $bus, 'criar autocarro: ' . json_encode(session('errors')?->all()));
        if ($bus) {
            $this->ok($bus->assentos()->count() === 44 && (int) $bus->transportadora_id === (int) $g->transportadora_id && $bus->ano === 2019, 'autocarro com 44 lugares, da transportadora do gestor');
            $this->ok($bus->livrete_caminho && Storage::disk('local')->exists($bus->livrete_caminho), 'livrete guardado em disco');
            $this->ok(! str_contains((string) $bus->livrete_caminho, 'public'), 'livrete não pode ficar em pasta pública');
            $ver = $this->actingAs($g)->get("/gestao/autocarros/{$bus->id}/livrete");
            $this->ok($ver->getStatusCode() === 200 && str_contains((string) $ver->headers->get('Content-Disposition'), 'inline'), 'gestor vê o livrete: ' . $ver->getStatusCode());
            $this->ok($this->actingAs($outroGestor)->get("/gestao/autocarros/{$bus->id}/livrete")->getStatusCode() === 403, 'gestor de outra transportadora não vê o livrete');
            $this->ok($this->actingAs($passageiro)->get("/gestao/autocarros/{$bus->id}/livrete")->getStatusCode() === 403, 'passageiro não vê o livrete');
            auth()->logout();
            $this->ok($this->get("/gestao/autocarros/{$bus->id}/livrete")->isRedirect(), 'visitante não vê o livrete');

            // substituir o livrete apaga o antigo
            $antigo = $bus->livrete_caminho;
            $this->actingAs($g)->put("/gestao/autocarros/{$bus->id}", ['matricula' => 'AB-123-MP', 'modelo' => 'Yutong 2', 'ano' => 2020, 'livrete' => UploadedFile::fake()->create('novo.png', 80, 'image/png')]);
            $bus->refresh();
            $this->ok($bus->modelo === 'Yutong 2' && $bus->livrete_caminho !== $antigo && ! Storage::disk('local')->exists($antigo) && Storage::disk('local')->exists($bus->livrete_caminho), 'substituir livrete');
            // editar sem ficheiro mantém o livrete
            $mantido = $bus->livrete_caminho;
            $this->actingAs($g)->put("/gestao/autocarros/{$bus->id}", ['matricula' => 'AB-123-MP', 'modelo' => 'Yutong 3']);
            $this->ok($bus->fresh()->livrete_caminho === $mantido, 'editar sem ficheiro mantém o livrete');
            // remover
            $this->actingAs($g)->put("/gestao/autocarros/{$bus->id}", ['matricula' => 'AB-123-MP', 'remover_livrete' => 1]);
            $this->ok($bus->fresh()->livrete_caminho === null && ! Storage::disk('local')->exists($mantido), 'remover livrete');
            // gestor de outra transportadora não edita nem elimina
            $this->ok($this->actingAs($outroGestor)->put("/gestao/autocarros/{$bus->id}", ['matricula' => 'XX-1'])->getStatusCode() === 403, 'editar autocarro alheio => 403');
            $this->ok($this->actingAs($outroGestor)->delete("/gestao/autocarros/{$bus->id}")->getStatusCode() === 403, 'eliminar autocarro alheio => 403');
        }

        // validações do livrete
        $this->actingAs($g)->post('/gestao/autocarros', ['matricula' => 'TXT-1', 'livrete' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]);
        $this->ok(! Autocarro::where('matricula', 'TXT-1')->exists(), 'livrete .exe devia ser recusado');
        $this->actingAs($g)->post('/gestao/autocarros', ['matricula' => 'BIG-1', 'livrete' => UploadedFile::fake()->create('grande.pdf', 6000, 'application/pdf')]);
        $this->ok(! Autocarro::where('matricula', 'BIG-1')->exists(), 'livrete > 5 MB devia ser recusado');
        $this->actingAs($g)->post('/gestao/autocarros', ['matricula' => 'AB-123-MP']);
        $this->ok(session('errors')?->has('matricula') === true, 'matrícula duplicada devia dar erro');

        // eliminar: só sem viagens; desactivar
        $usado = Autocarro::where('transportadora_id', $g->transportadora_id)->whereHas('viagens')->first();
        $this->actingAs($g)->delete("/gestao/autocarros/{$usado->id}");
        $this->ok(Autocarro::whereKey($usado->id)->exists() && session('erro'), 'autocarro com viagens não pode ser eliminado');
        $this->actingAs($g)->post("/gestao/autocarros/{$usado->id}/estado");
        $this->ok($usado->fresh()->estado === 'inactivo', 'desactivar autocarro');
        $rota = $usado->viagens()->first()->rota;
        $this->actingAs($g)->post('/gestao/viagens', ['rota_id' => $rota->id, 'autocarro_id' => $usado->id, 'hora' => '23:30', 'classe' => 'X', 'preco' => 100, 'data_inicio' => now()->addDays(40)->toDateString(), 'dias' => 1]);
        $this->ok(Viagem::where('autocarro_id', $usado->id)->where('hora_partida', '23:30:00')->count() === 0, 'autocarro desactivado não pode ser programado');
        $this->actingAs($g)->post("/gestao/autocarros/{$usado->id}/estado");
        $this->ok($usado->fresh()->estado === 'activo', 'reactivar autocarro');

        if ($bus) {
            $this->actingAs($g)->delete("/gestao/autocarros/{$bus->id}");
            $this->ok(! Autocarro::whereKey($bus->id)->exists(), 'eliminar autocarro sem viagens');
        }
        foreach (['/gestao/autocarros', '/gestao/autocarros?q=ab', '/gestao/motoristas', '/gestao/cobradores'] as $url) {
            $this->ok($this->actingAs($g)->get($url)->getStatusCode() === 200, "GET {$url}");
        }

        $this->assertSame([], $this->falhas, "\n" . implode("\n", $this->falhas));
    }

    public function test_motoristas_e_cobradores(): void
    {
        Permissao::limparCache();
        $this->seed(DatabaseSeeder::class);
        $g = Utilizador::where('email', 'p.nhalicolo@moztransport.co.mz')->first();
        $outroGestor = Utilizador::where('perfil', 'gestor')->where('transportadora_id', '!=', $g->transportadora_id)->first();

        // ---- motoristas
        $this->actingAs($g)->post('/gestao/motoristas', ['nome' => 'João Novo', 'telefone' => '84 1', 'carta_numero' => 'MZ-1', 'carta_categoria' => 'D', 'carta_validade' => now()->addYear()->toDateString()]);
        $m = Motorista::where('nome', 'João Novo')->first();
        $this->ok($m && (int) $m->transportadora_id === (int) $g->transportadora_id, 'criar motorista: ' . json_encode(session('errors')?->all()));
        $this->actingAs($g)->post('/gestao/motoristas', ['nome' => 'Sem Carta']);
        $this->ok(! Motorista::where('nome', 'Sem Carta')->exists(), 'motorista sem carta devia ser recusado');
        if ($m) {
            $this->actingAs($g)->put("/gestao/motoristas/{$m->id}", ['nome' => 'João Editado', 'carta_numero' => 'MZ-1', 'carta_validade' => now()->addYear()->toDateString()]);
            $this->ok($m->fresh()->nome === 'João Editado', 'editar motorista');
            $this->ok($this->actingAs($outroGestor)->put("/gestao/motoristas/{$m->id}", ['nome' => 'X', 'carta_numero' => '1'])->getStatusCode() === 403, 'motorista alheio => 403');

            $rota = \App\Models\Rota::where('transportadora_id', $g->transportadora_id)->first();
            $bus = Autocarro::where('transportadora_id', $g->transportadora_id)->first();
            $dados = ['rota_id' => $rota->id, 'autocarro_id' => $bus->id, 'motorista_id' => $m->id, 'hora' => '22:15', 'classe' => 'X', 'preco' => 100, 'data_inicio' => now()->addDays(50)->toDateString(), 'dias' => 2];
            $this->actingAs($g)->post('/gestao/viagens', $dados);
            $this->ok(Viagem::where('motorista_id', $m->id)->count() === 2, 'programar viagens com motorista: ' . json_encode(session('errors')?->all()));
            $this->actingAs($g)->delete("/gestao/motoristas/{$m->id}");
            $this->ok(Motorista::whereKey($m->id)->exists(), 'motorista com viagens não pode ser eliminado');

            $this->actingAs($g)->post("/gestao/motoristas/{$m->id}/estado");
            $this->ok($m->fresh()->estado === 'inactivo', 'desactivar motorista');
            $this->actingAs($g)->post('/gestao/viagens', ['hora' => '21:00'] + $dados);
            $this->ok(Viagem::where('hora_partida', '21:00:00')->where('motorista_id', $m->id)->count() === 0, 'motorista inactivo não pode ser atribuído');
            $this->actingAs($g)->post("/gestao/motoristas/{$m->id}/estado");

            // carta caducada antes da viagem
            $m->update(['carta_validade' => now()->addDays(10)]);
            $this->actingAs($g)->post('/gestao/viagens', ['hora' => '20:00'] + $dados);
            $this->ok(Viagem::where('hora_partida', '20:00:00')->where('motorista_id', $m->id)->count() === 0, 'carta caducada antes da viagem devia ser recusada');
        }
        $livre = Motorista::create(['nome' => 'Livre', 'carta_numero' => '9', 'transportadora_id' => $g->transportadora_id]);
        $this->actingAs($g)->delete("/gestao/motoristas/{$livre->id}");
        $this->ok(! Motorista::whereKey($livre->id)->exists(), 'eliminar motorista sem viagens');
        $alheio = $this->motoristaDeOutra($g);
        $r = $this->actingAs($g)->post("/gestao/viagens", ['rota_id' => \App\Models\Rota::where('transportadora_id', $g->transportadora_id)->value('id'), 'autocarro_id' => Autocarro::where('transportadora_id', $g->transportadora_id)->value('id'), 'motorista_id' => $alheio->id, 'hora' => '19:00', 'classe' => 'X', 'preco' => 1, 'data_inicio' => now()->addDays(60)->toDateString(), 'dias' => 1]);
        $this->ok(Viagem::where('motorista_id', $alheio->id)->where('hora_partida', '19:00:00')->count() === 0, 'motorista de outra transportadora não pode ser atribuído');

        // ---- cobradores
        $this->actingAs($g)->post('/gestao/cobradores', ['nome' => 'Cobrador Novo', 'email' => 'cn@teste.mz', 'telefone' => '84 2']);
        $c = Utilizador::where('email', 'cn@teste.mz')->first();
        $this->ok($c && $c->perfil === 'cobrador' && (int) $c->transportadora_id === (int) $g->transportadora_id, 'criar cobrador: ' . json_encode(session('errors')?->all()));
        $this->ok((bool) session('senha_temporaria'), 'senha temporária mostrada');
        if ($c) {
            $tmp = session('senha_temporaria')['senha'];
            auth()->logout();
            $this->flushSession();
            $this->post('/painel/entrar', ['email' => 'cn@teste.mz', 'password' => $tmp]);
            $this->ok(auth()->check() && auth()->user()->perfil === 'cobrador', 'novo cobrador entra com a senha temporária');
            $this->post('/sair');
            $this->flushSession();

            $this->actingAs($g)->put("/gestao/cobradores/{$c->id}", ['nome' => 'Cobrador Editado', 'email' => 'cn2@teste.mz', 'telefone' => '84 3']);
            $this->ok($c->fresh()->nome === 'Cobrador Editado' && $c->fresh()->email === 'cn2@teste.mz', 'editar cobrador');
            $this->actingAs($g)->post("/gestao/cobradores/{$c->id}/estado");
            $this->ok($c->fresh()->estado === 'bloqueado', 'bloquear cobrador');
            $this->actingAs($g)->post("/gestao/cobradores/{$c->id}/estado");
            $this->actingAs($g)->post("/gestao/cobradores/{$c->id}/senha");
            $this->ok((bool) session('senha_temporaria'), 'repor senha do cobrador');
            $this->ok($this->actingAs($outroGestor)->put("/gestao/cobradores/{$c->id}", ['nome' => 'X', 'email' => 'x@x.mz'])->getStatusCode() === 403, 'cobrador alheio => 403');
            $this->ok($this->actingAs($outroGestor)->delete("/gestao/cobradores/{$c->id}")->getStatusCode() === 403, 'eliminar cobrador alheio => 403');
            $this->actingAs($g)->delete("/gestao/cobradores/{$c->id}");
            $this->ok(! Utilizador::whereKey($c->id)->exists(), 'eliminar cobrador sem histórico');
        }
        $comViagens = Utilizador::where('perfil', 'cobrador')->where('transportadora_id', $g->transportadora_id)->first();
        $this->actingAs($g)->delete("/gestao/cobradores/{$comViagens->id}");
        $this->ok(Utilizador::whereKey($comViagens->id)->exists(), 'cobrador com viagens não pode ser eliminado');
        // o gestor não consegue mexer em utilizadores que não são cobradores por este caminho
        $this->ok($this->actingAs($g)->post("/gestao/cobradores/{$g->id}/estado")->getStatusCode() === 404, 'só cobradores');
        $this->actingAs($g)->post('/gestao/cobradores', ['nome' => 'D', 'email' => 'nelson.nhavene@moztransport.co.mz']);
        $this->ok(session('errors')?->has('email') === true, 'email duplicado');

        $this->assertSame([], $this->falhas, "\n" . implode("\n", $this->falhas));
    }

    public function test_administrador_escolhe_a_transportadora(): void
    {
        Storage::fake('local');
        Permissao::limparCache();
        $this->seed(DatabaseSeeder::class);
        $admin = Utilizador::where('email', 'nelson.nhavene@moztransport.co.mz')->first();
        $t = \App\Models\Transportadora::orderByDesc('id')->first();

        $this->actingAs($admin)->post('/gestao/autocarros', ['matricula' => 'ADM-1']);
        $this->assertTrue(session('errors')?->has('transportadora_id') === true, 'admin tem de escolher a transportadora');
        $this->assertFalse(Autocarro::where('matricula', 'ADM-1')->exists());

        $this->actingAs($admin)->post('/gestao/autocarros', ['matricula' => 'ADM-1', 'transportadora_id' => $t->id, 'livrete' => UploadedFile::fake()->create('l.pdf', 50, 'application/pdf')]);
        $bus = Autocarro::where('matricula', 'ADM-1')->first();
        $this->assertNotNull($bus);
        $this->assertSame($t->id, (int) $bus->transportadora_id);
        $this->assertSame(200, $this->actingAs($admin)->get("/gestao/autocarros/{$bus->id}/livrete")->getStatusCode());

        $this->actingAs($admin)->post('/gestao/motoristas', ['nome' => 'Motorista Adm', 'carta_numero' => '1', 'transportadora_id' => $t->id]);
        $this->assertSame($t->id, (int) Motorista::where('nome', 'Motorista Adm')->value('transportadora_id'));
        $this->actingAs($admin)->post('/gestao/cobradores', ['nome' => 'Cob Adm', 'email' => 'cobadm@teste.mz', 'transportadora_id' => $t->id]);
        $this->assertSame($t->id, (int) Utilizador::where('email', 'cobadm@teste.mz')->value('transportadora_id'));
        $this->assertSame(200, $this->actingAs($admin)->get('/gestao/autocarros?transportadora=' . $t->id)->getStatusCode());
    }

    private function motoristaDeOutra(Utilizador $g): Motorista
    {
        return Motorista::where('transportadora_id', '!=', $g->transportadora_id)->firstOrFail();
    }
}
