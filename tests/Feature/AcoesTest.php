<?php

namespace Tests\Feature;

use App\Models\Autocarro;
use App\Models\Bilhete;
use App\Models\Definicao;
use App\Models\Permissao;
use App\Models\Reserva;
use App\Models\Rota;
use App\Models\Transportadora;
use App\Models\Utilizador;
use App\Models\Viagem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Acções (POST/PUT) de todos os perfis, com dados de demonstração. Lista todas as falhas no fim. */
class AcoesTest extends TestCase
{
    use RefreshDatabase;

    private array $falhas = [];

    private function ok(bool $cond, string $msg): void
    {
        if (! $cond) {
            $this->falhas[] = $msg;
        }
    }

    private function user(string $email): Utilizador
    {
        $u = Utilizador::where('email', $email)->first();
        if (! $u) {
            throw new \RuntimeException("Utilizador {$email} não existe. Falhas até aqui:\n" . implode("\n", $this->falhas));
        }

        return $u;
    }

    public function test_acoes(): void
    {
        Permissao::limparCache();
        $this->seed(DatabaseSeeder::class);
        $senha = 'Moz@2026';

        // ============ LOGIN ============
        $r = $this->post('/entrar', ['email' => 'ioramo.satar@email.co.mz', 'password' => $senha]);
        $this->ok($r->isRedirect(route('app.inicio')) && auth()->check(), 'login passageiro: ' . $r->headers->get('Location') . ' ' . json_encode(session('errors')?->all()));
        $this->post('/sair');
        $this->flushSession();

        $r = $this->post('/painel/entrar', ['email' => 'ioramo.satar@email.co.mz', 'password' => $senha]);
        $this->ok(! auth()->check(), 'passageiro não deve entrar no painel');
        $r = $this->post('/entrar', ['email' => 'nelson.nhavene@moztransport.co.mz', 'password' => $senha]);
        $this->ok(! auth()->check(), 'staff não deve entrar na app');

        foreach (['manuel.zoca@moztransport.co.mz' => 'bilheteira.vender', 'mikael.chongo@moztransport.co.mz' => 'cobrador.viagens', 'p.nhalicolo@moztransport.co.mz' => 'gestao.dashboard', 'nelson.nhavene@moztransport.co.mz' => 'admin.visao'] as $email => $rota) {
            $r = $this->post('/painel/entrar', ['email' => $email, 'password' => $senha]);
            $this->ok($r->isRedirect(route($rota)), "login painel {$email} => " . $r->headers->get('Location'));
            $this->post('/sair');
            $this->flushSession();
        }

        // conta bloqueada pelo admin (Jose Junior)
        $r = $this->post('/painel/entrar', ['email' => 'jose.junior@moztransport.co.mz', 'password' => $senha]);
        $this->ok(! auth()->check(), 'conta bloqueada não deve entrar');

        // bloqueio por tentativas
        for ($i = 0; $i < 5; $i++) {
            $r = $this->post('/entrar', ['email' => 'ioramo.satar@email.co.mz', 'password' => 'errada']);
        }
        $this->ok(session('bloqueio') > 0, 'bloqueio após 5 tentativas falhadas: ' . json_encode(session()->all()));
        $r = $this->post('/entrar', ['email' => 'ioramo.satar@email.co.mz', 'password' => $senha]);
        $this->ok(! auth()->check(), 'conta temporariamente bloqueada não deve entrar com senha certa');
        Utilizador::where('email', 'ioramo.satar@email.co.mz')->update(['bloqueado_ate' => null, 'tentativas_falhadas' => 0]);
        $this->flushSession();

        // ============ REGISTO ============
        $r = $this->post('/criar-conta', ['nome' => 'Teste Novo', 'email' => 'novo@teste.mz', 'telefone' => '84 111 2222', 'password' => 'Segura123', 'password_confirmation' => 'Segura123', 'termos' => 1]);
        $this->ok(Utilizador::where('email', 'novo@teste.mz')->exists(), 'registo: ' . $r->getStatusCode() . ' ' . $r->headers->get('Location') . ' auth=' . json_encode(auth()->check()) . ' ' . json_encode(session('errors')?->all()));
        $r = $this->get('/conta-criada');
        $this->ok($r->getStatusCode() === 200, 'conta-criada => ' . $r->getStatusCode());
        $r = $this->post('/criar-conta', ['nome' => 'X', 'email' => 'novo@teste.mz', 'password' => 'fraca', 'password_confirmation' => 'fraca']);
        $this->ok(session('errors') && session('errors')->has('email') && session('errors')->has('password'), 'registo duplicado/fraco devia dar erros');
        $this->flushSession();

        // ============ RECUPERAÇÃO (passageiro) ============
        $this->post('/recuperar', ['email' => 'novo@teste.mz']);
        $codigo = session('codigo_demo');
        $this->ok((bool) $codigo, 'recuperação: sem código demo');
        $r = $this->get('/recuperar/codigo');
        $this->ok($r->getStatusCode() === 200, 'recuperar/codigo => ' . $r->getStatusCode());
        $this->post('/recuperar/redefinir', ['codigo' => $codigo, 'password' => 'Nova12345', 'password_confirmation' => 'Nova12345']);
        $this->flushSession();
        $this->post('/entrar', ['email' => 'novo@teste.mz', 'password' => 'Nova12345']);
        $this->ok(auth()->check(), 'login com palavra-passe recuperada (app)');
        $this->post('/sair');
        $this->flushSession();

        // recuperação (painel, 2 passos)
        $this->post('/painel/recuperar', ['email' => 'telma.bila@moztransport.co.mz']);
        $codigo = session('codigo_demo');
        $r = $this->post('/painel/recuperar/verificar', ['codigo' => $codigo]);
        $this->ok($r->isRedirect(route('painel.recuperar.nova')), 'painel verificar => ' . $r->headers->get('Location'));
        $r = $this->get('/painel/recuperar/nova');
        $this->ok($r->getStatusCode() === 200, 'painel nova => ' . $r->getStatusCode());
        $this->post('/painel/recuperar/redefinir', ['password' => 'Nova12345', 'password_confirmation' => 'Nova12345']);
        $this->flushSession();
        $this->post('/painel/entrar', ['email' => 'telma.bila@moztransport.co.mz', 'password' => 'Nova12345']);
        $this->ok(auth()->check(), 'login com palavra-passe recuperada (painel)');
        $this->post('/sair');
        $this->flushSession();

        // ============ PERFIL ============
        $pass = $this->user('ioramo.satar@email.co.mz');
        $r = $this->actingAs($pass)->put('/perfil', ['nome' => 'Ioramo S.', 'email' => 'ioramo.satar@email.co.mz', 'telefone' => '84 123 4567', 'documento' => '1']);
        $this->ok($pass->fresh()->nome === 'Ioramo S.', 'perfil actualizar: ' . json_encode(session('errors')?->all()));
        $this->actingAs($pass)->put('/perfil/senha', ['password_actual' => $senha, 'password' => 'Outra1234', 'password_confirmation' => 'Outra1234']);
        $this->ok(\Hash::check('Outra1234', $pass->fresh()->senha), 'perfil senha: ' . json_encode(session('errors')?->all()));
        $this->actingAs($pass)->put('/perfil/notificacoes', []);
        $this->ok($pass->fresh()->notificacoes === false, 'perfil notificações');
        Utilizador::whereKey($pass->id)->update(['senha' => \Hash::make($senha)]);

        // ============ PASSAGEIRO: cancelar / remarcar ============
        $pass = $pass->fresh();
        $futuras = Reserva::where('passageiro_id', $pass->id)->where('estado', 'confirmada')->get()->filter(fn ($x) => $x->viagem->partida_em->isFuture() && now()->diffInHours($x->viagem->partida_em, false) > 24);
        $this->ok($futuras->count() >= 1, 'demo: passageira sem reservas futuras >24h');
        $res = $futuras->first();
        if ($res) {
            $alt = Viagem::futuras()->where('rota_id', $res->viagem->rota_id)->where('preco', $res->viagem->preco)->where('id', '!=', $res->viagem_id)->where('estado', 'agendada')->get()
                ->first(fn ($v) => count($v->assentosOcupados()) < 40 && now()->diffInHours($v->partida_em, false) > 1);
            if ($alt) {
                $livres = array_values(array_diff($alt->autocarro->assentos()->pluck('id')->all(), $alt->assentosOcupados()));
                $r = $this->actingAs($pass)->post("/app/reservas/{$res->id}/remarcar/{$alt->id}", ['assento_id' => $livres[0]]);
                $this->ok($res->fresh()->viagem_id === $alt->id, 'remarcar: ' . json_encode(session('errors')?->all()) . ' ' . $r->headers->get('Location'));
                $this->ok($this->actingAs($pass)->get("/app/bilhetes/{$res->id}")->getStatusCode() === 200, 'bilhete após remarcar');
            } else {
                $this->falhas[] = 'demo: sem viagem alternativa para remarcar';
            }
            $r = $this->actingAs($pass)->post("/app/reservas/{$res->id}/cancelar");
            $this->ok($res->fresh()->estado === 'cancelada', 'cancelar: ' . json_encode(session('erro')) . ' ' . $res->fresh()->estado);
            $this->ok($res->fresh()->ativa === null, 'cancelar devia libertar o lugar');
            $r = $this->actingAs($pass)->get('/app/cancelada');
            $this->ok($r->getStatusCode() === 200, 'página cancelada => ' . $r->getStatusCode());
            $this->ok($this->actingAs($pass)->get("/app/bilhetes/{$res->id}")->getStatusCode() === 200, 'bilhete cancelado visível');
            $this->ok($this->actingAs($pass)->get('/app/historico')->getStatusCode() === 200, 'histórico após cancelar');
        }

        // acesso à reserva de outro => 403
        $outra = Reserva::where('passageiro_id', '!=', $pass->id)->whereNotNull('passageiro_id')->first();
        if ($outra) {
            $this->ok($this->actingAs($pass)->get("/app/reservas/{$outra->id}/pagamento")->getStatusCode() === 403, 'reserva alheia devia dar 403');
            $this->ok($this->actingAs($pass)->get("/app/bilhetes/{$outra->id}")->getStatusCode() === 403, 'bilhete alheio devia dar 403');
        }

        // expiração do bloqueio
        $viagem = Viagem::futuras()->where('estado', 'agendada')->get()->first(fn ($v) => count($v->assentosOcupados()) < 40);
        $livre = array_values(array_diff($viagem->autocarro->assentos()->pluck('id')->all(), $viagem->assentosOcupados()))[0];
        $this->actingAs($pass)->post("/app/viagens/{$viagem->id}/reservar", ['assento_ids' => [$livre]]);
        $pend = Reserva::where('passageiro_id', $pass->id)->where('estado', 'pendente')->first();
        $this->ok((bool) $pend, 'reservar não criou pendente');
        if ($pend) {
            // outro passageiro tenta o mesmo lugar
            $outro = $this->user('novo@teste.mz');
            $this->actingAs($outro)->post("/app/viagens/{$viagem->id}/reservar", ['assento_ids' => [$livre]]);
            $this->ok(Reserva::where('viagem_id', $viagem->id)->where('assento_id', $livre)->where('ativa', 1)->count() === 1, 'lugar duplicado!');
            // expira
            $pend->update(['expira_em' => now()->subMinute()]);
            $r = $this->actingAs($pass)->get("/app/reservas/{$pend->id}/pagamento");
            $this->ok($r->isRedirect(route('app.inicio')), 'reserva expirada devia redireccionar: ' . $r->getStatusCode());
            $this->ok($pend->fresh()->estado === 'expirada' && $pend->fresh()->ativa === null, 'expirada devia libertar lugar: ' . $pend->fresh()->estado);
            $this->actingAs($outro)->post("/app/viagens/{$viagem->id}/reservar", ['assento_ids' => [$livre]]);
            $this->ok(Reserva::where('viagem_id', $viagem->id)->where('assento_id', $livre)->where('ativa', 1)->where('passageiro_id', $outro->id)->exists(), 'lugar libertado devia poder ser reservado');
        }

        // ============ COBRADOR ============
        $cob = $this->user('mikael.chongo@moztransport.co.mz');
        $outraViagem = Viagem::where('cobrador_id', '!=', $cob->id)->first();
        $this->ok($this->actingAs($cob)->get("/cobrador/viagens/{$outraViagem->id}")->getStatusCode() === 403, 'cobrador noutra viagem => 403');
        $vc = Viagem::where('cobrador_id', $cob->id)->whereDate('data', now()->toDateString())->first();
        if ($vc) {
            $r = $this->actingAs($cob)->postJson("/cobrador/viagens/{$vc->id}/validar", ['codigo' => 'MT-0000-000000']);
            $this->ok($r->json('ok') === false, 'QR inexistente devia ser recusado');
            $b = Bilhete::where('estado', 'valido')->whereHas('reserva', fn ($q) => $q->where('estado', 'confirmada')->where('viagem_id', '!=', $vc->id))->first();
            $r = $this->actingAs($cob)->postJson("/cobrador/viagens/{$vc->id}/validar", ['codigo' => $b->codigo_qr]);
            $this->ok($r->json('ok') === false && str_contains($r->json('mensagem'), 'outra viagem'), 'QR de outra viagem: ' . $r->getContent());
            $r = $this->actingAs($cob)->post("/cobrador/viagens/{$vc->id}/terminar");
            $this->ok($vc->fresh()->estado === 'concluida', 'terminar viagem');
        }

        // ============ GESTOR ============
        $g = $this->user('p.nhalicolo@moztransport.co.mz');
        $r = $this->actingAs($g)->post('/gestao/rotas', ['origem' => 'Maputo', 'destino' => 'Pemba', 'distancia_km' => 2600, 'horas' => 40, 'minutos' => 30, 'preco_base' => 4000]);
        $rota = Rota::where('destino', 'Pemba')->first();
        $this->ok((bool) $rota && $rota->duracao_minutos === 2430 && $rota->transportadora_id === $g->transportadora_id, 'criar rota: ' . json_encode(session('errors')?->all()));
        $r = $this->actingAs($g)->put("/gestao/rotas/{$rota->id}", ['origem' => 'Maputo', 'destino' => 'Pemba', 'distancia_km' => 2650, 'horas' => 41, 'minutos' => 0, 'preco_base' => 4100]);
        $this->ok($rota->fresh()->preco_base === 4100, 'actualizar rota: ' . json_encode(session('errors')?->all()));
        $this->actingAs($g)->post("/gestao/rotas/{$rota->id}/estado");
        $this->ok($rota->fresh()->estado === 'desactivada', 'desactivar rota');
        $this->actingAs($g)->post("/gestao/rotas/{$rota->id}/estado");
        $this->actingAs($g)->post('/gestao/autocarros', ['matricula' => 'zz-123-mp', 'modelo' => 'Teste']);
        $bus = Autocarro::where('matricula', 'ZZ-123-MP')->first();
        $this->ok((bool) $bus && $bus->assentos()->count() === 44, 'criar autocarro: ' . json_encode(session('errors')?->all()));
        $cobG = Utilizador::where('perfil', 'cobrador')->where('transportadora_id', $g->transportadora_id)->first();
        $this->actingAs($g)->post('/gestao/viagens', ['rota_id' => $rota->id, 'autocarro_id' => $bus->id, 'cobrador_id' => $cobG->id, 'hora' => '06:00', 'classe' => 'Executivo', 'preco' => 4100, 'data_inicio' => now()->addDay()->toDateString(), 'dias' => 3]);
        $this->ok(Viagem::where('rota_id', $rota->id)->count() === 3, 'programar viagens: ' . json_encode(session('errors')?->all()));
        // rota de outra transportadora => 403
        $alheia = Rota::where('transportadora_id', '!=', $g->transportadora_id)->first();
        $this->ok($this->actingAs($g)->put("/gestao/rotas/{$alheia->id}", ['origem' => 'A', 'destino' => 'B', 'distancia_km' => 1, 'horas' => 1, 'minutos' => 0, 'preco_base' => 1])->getStatusCode() === 403, 'gestor a editar rota alheia => 403');
        $this->ok($this->actingAs($g)->post("/gestao/rotas/{$alheia->id}/estado")->getStatusCode() === 403, 'gestor a desactivar rota alheia => 403');
        // autocarro alheio
        $busAlheio = Autocarro::where('transportadora_id', '!=', $g->transportadora_id)->first();
        $r = $this->actingAs($g)->post('/gestao/viagens', ['rota_id' => $rota->id, 'autocarro_id' => $busAlheio->id, 'hora' => '07:00', 'classe' => 'X', 'preco' => 1, 'data_inicio' => now()->addDay()->toDateString(), 'dias' => 1]);
        $this->ok(Viagem::where('rota_id', $rota->id)->count() === 3, 'viagem com autocarro alheio não devia ser criada');
        // cobrador de outra transportadora
        $cobAlheio = Utilizador::where('perfil', 'cobrador')->where('transportadora_id', '!=', $g->transportadora_id)->first();
        $this->actingAs($g)->post('/gestao/viagens', ['rota_id' => $rota->id, 'autocarro_id' => $bus->id, 'cobrador_id' => $cobAlheio->id, 'hora' => '09:00', 'classe' => 'X', 'preco' => 1, 'data_inicio' => now()->addDay()->toDateString(), 'dias' => 1]);
        $this->ok(Viagem::where('rota_id', $rota->id)->where('cobrador_id', $cobAlheio->id)->count() === 0, 'BUG: gestor atribuiu cobrador de outra transportadora');

        // ============ ADMIN ============
        $a = $this->user('nelson.nhavene@moztransport.co.mz');
        $t = Transportadora::first();
        $this->actingAs($a)->post('/admin/utilizadores', ['nome' => 'Novo Gestor', 'email' => 'ng@teste.mz', 'perfil' => 'gestor', 'transportadora_id' => $t->id]);
        $ng = Utilizador::where('email', 'ng@teste.mz')->first();
        $this->ok((bool) $ng && session('senha_temporaria'), 'admin criar utilizador: ' . json_encode(session('errors')?->all()));
        if ($ng) {
            $tmp = session('senha_temporaria')['senha'];
            $this->flushSession();
            $this->post('/painel/entrar', ['email' => 'ng@teste.mz', 'password' => $tmp]);
            $this->ok(auth()->check(), 'novo utilizador entra com senha temporária');
            $this->post('/sair');
            $this->flushSession();
            $this->actingAs($a)->post("/admin/utilizadores/{$ng->id}/bloquear");
            $this->ok($ng->fresh()->estado === 'bloqueado', 'bloquear utilizador');
            $this->actingAs($ng->fresh())->get('/gestao')->assertRedirect(); // sessão termina
            $this->actingAs($a)->post("/admin/utilizadores/{$ng->id}/bloquear");
            $this->actingAs($a)->post("/admin/utilizadores/{$ng->id}/repor-senha");
            $this->ok((bool) session('senha_temporaria'), 'repor senha');
        }
        $this->ok($this->actingAs($a)->post("/admin/utilizadores/{$a->id}/bloquear")->isRedirect() && $a->fresh()->estado === 'activo', 'admin não pode bloquear-se a si próprio');
        $this->actingAs($a)->post('/admin/utilizadores', ['nome' => 'C', 'email' => 'c@teste.mz', 'perfil' => 'cobrador']);
        $this->ok(! Utilizador::where('email', 'c@teste.mz')->exists(), 'cobrador sem transportadora devia ser recusado');

        $this->actingAs($a)->post('/admin/transportadoras', ['nome' => 'Nova Lda', 'nuit' => '1', 'contacto' => '2', 'endereco' => 'x']);
        $this->ok(Transportadora::where('nome', 'Nova Lda')->exists(), 'criar transportadora: ' . json_encode(session('errors')?->all()));

        $this->actingAs($a)->post('/admin/permissoes', ['perm' => ['gestor' => ['ver_dashboard' => 1], 'bilheteiro' => ['vender_bilhetes' => 1, 'ver_dashboard' => 1], 'cobrador' => ['ver_dashboard' => 1]]]);
        Permissao::limparCache();
        $this->ok(! Permissao::tem('gestor', 'gerir_rotas') && Permissao::tem('administrador', 'gerir_seguranca'), 'permissões guardadas');
        $this->ok($this->actingAs($g)->get('/gestao/rotas')->getStatusCode() === 403, 'gestor sem permissão gerir_rotas => 403');

        $this->actingAs($a)->post('/admin/seguranca', ['max_tentativas_login' => 4, 'minutos_bloqueio_login' => 2, 'minutos_bloqueio_lugar' => 8, 'horas_limite_cancelamento' => 12]);
        $this->ok(Definicao::obter('max_tentativas_login') === '4', 'guardar definições: ' . json_encode(session('errors')?->all()));
        $r = $this->actingAs($a)->post('/admin/seguranca/backup');
        // Os testes usam SQLite em memória (sem ficheiro): o backup tem de responder com uma mensagem, nunca com erro 500.
        $this->ok(session('estado') !== null || session('erro') !== null, 'backup devia responder com uma mensagem');
        $this->ok($this->actingAs($a)->get('/admin/seguranca')->getStatusCode() === 200, 'segurança após backup');

        $this->assertSame([], $this->falhas, "\n" . implode("\n", $this->falhas));
    }
}
