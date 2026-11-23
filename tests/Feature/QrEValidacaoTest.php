<?php

namespace Tests\Feature;

use App\Models\Bilhete;
use App\Models\Permissao;
use App\Models\Reserva;
use App\Models\Utilizador;
use App\Models\Viagem;
use Illuminate\Support\Str;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** QR assinado no bilhete, validação pelo cobrador e matrícula visível. */
class QrEValidacaoTest extends TestCase
{
    use RefreshDatabase;

    private array $falhas = [];

    private function ok(bool $cond, string $msg): void
    {
        if (! $cond) {
            $this->falhas[] = $msg;
        }
    }

    private function bilheteNovo(Viagem $v, Utilizador $passageiro): Bilhete
    {
        // directo na base de dados: a viagem de hoje pode já ter partido (e aí o sistema já não vende),
        // mas o cobrador continua a validar bilhetes comprados antes.
        $livres = array_values(array_diff($v->autocarro->assentos()->pluck('id')->all(), $v->assentosOcupados()));
        $reserva = Reserva::create([
            'grupo' => (string) Str::uuid(), 'passageiro_id' => $passageiro->id, 'viagem_id' => $v->id, 'assento_id' => $livres[0],
            'nome_passageiro' => $passageiro->nome, 'estado' => 'confirmada', 'ativa' => 1,
        ]);

        return Bilhete::emitir($reserva);
    }

    public function test_qr_assinado_e_validacao(): void
    {
        Permissao::limparCache();
        $this->seed(DatabaseSeeder::class);
        $cob = Utilizador::where('email', 'mikael.chongo@moztransport.co.mz')->first();
        $pass = Utilizador::where('email', 'ioramo.satar@email.co.mz')->first();
        $hoje = now()->toDateString();

        // viagem de hoje, da transportadora do cobrador, SEM cobrador atribuído, ainda por partir ou não (a validação não depende da hora)
        $viagem = Viagem::whereDate('data', $hoje)->where('cobrador_id', $cob->id)->get()->first(fn ($v) => count($v->assentosOcupados()) < 40);
        $viagem->update(['cobrador_id' => null]);
        $bilhete = $this->bilheteNovo($viagem->fresh(), $pass);
        $assinado = $bilhete->codigo_assinado;

        // --- o bilhete do passageiro mostra o QR assinado e a matrícula
        $reserva = $bilhete->reserva;
        $pagina = $this->actingAs($pass)->get("/app/bilhetes/{$reserva->id}");
        $this->ok($pagina->getStatusCode() === 200, 'página do bilhete');
        $html = $pagina->getContent();
        $this->ok(str_contains($html, 'data-qr="' . $assinado . '"'), 'o QR do bilhete leva o código assinado');
        $this->ok(str_contains($html, $viagem->autocarro->matricula), 'o bilhete mostra a matrícula');
        $this->ok(file_exists(public_path('js/vendor/qrcode-generator.js')) && file_exists(public_path('js/vendor/jsQR.js')), 'bibliotecas de QR locais presentes');
        $this->ok(! str_contains($html, 'cdnjs.cloudflare.com'), 'sem dependência de CDN para o QR');
        $this->ok(str_contains($html, config('moz.nome')), 'nome do sistema no bilhete');

        // --- o cobrador da transportadora vê a viagem (sem cobrador atribuído) e o passageiro na lista
        $lista = $this->actingAs($cob)->get('/cobrador');
        $this->ok($lista->getStatusCode() === 200 && str_contains($lista->getContent(), $viagem->rota->nome) && str_contains($lista->getContent(), $viagem->autocarro->matricula), 'cobrador vê viagens sem cobrador atribuído e a matrícula');
        $pagViagem = $this->actingAs($cob)->get("/cobrador/viagens/{$viagem->id}");
        $this->ok($pagViagem->getStatusCode() === 200 && str_contains($pagViagem->getContent(), $pass->nome), 'o passageiro aparece na lista do cobrador');
        $this->ok($this->actingAs($cob)->get('/cobrador?data=' . now()->addDay()->toDateString())->getStatusCode() === 200, 'cobrador escolhe outro dia');
        $this->ok($this->actingAs($cob)->get('/cobrador?data=lixo')->getStatusCode() === 200, 'data inválida não parte a página');

        // --- QR adulterado, sem assinatura (câmara) e assinatura de outro bilhete
        $url = "/cobrador/viagens/{$viagem->id}/validar";
        $r = $this->actingAs($cob)->postJson($url, ['codigo' => $bilhete->codigo_qr . '.00000000', 'origem' => 'qr']);
        $this->ok($r->json('ok') === false && str_contains($r->json('mensagem'), 'assinatura'), 'QR com assinatura errada recusado: ' . $r->getContent());
        $r = $this->actingAs($cob)->postJson($url, ['codigo' => $bilhete->codigo_qr, 'origem' => 'qr']);
        $this->ok($r->json('ok') === false && str_contains($r->json('mensagem'), 'assinatura'), 'QR sem assinatura recusado pela câmara: ' . $r->getContent());
        $this->ok($bilhete->fresh()->estado === 'valido', 'bilhete continua válido depois de tentativas falhadas');

        // --- QR correcto
        $r = $this->actingAs($cob)->postJson($url, ['codigo' => $assinado, 'origem' => 'qr']);
        $this->ok($r->json('ok') === true && $r->json('passageiro') === $pass->nome, 'QR correcto validado: ' . $r->getContent());
        $this->ok($bilhete->fresh()->estado === 'usado' && (int) $bilhete->fresh()->validado_por === (int) $cob->id, 'bilhete marcado como usado pelo cobrador');
        $this->ok((int) $viagem->fresh()->cobrador_id === (int) $cob->id, 'viagem sem cobrador passa para o cobrador que validou');
        $r = $this->actingAs($cob)->postJson($url, ['codigo' => $assinado, 'origem' => 'qr']);
        $this->ok($r->json('ok') === false && str_contains($r->json('mensagem'), 'já utilizado'), 'segunda leitura recusada');
        $this->ok(str_contains($this->actingAs($cob)->get("/cobrador/viagens/{$viagem->id}")->getContent(), 'Embarcou'), 'lista mostra o passageiro como embarcado');

        // --- escrita manual: código simples e código assinado (leitor USB)
        $b2 = $this->bilheteNovo($viagem->fresh(), $pass);
        $r = $this->actingAs($cob)->postJson($url, ['codigo' => strtolower($b2->codigo_qr)]);
        $this->ok($r->json('ok') === true, 'código escrito à mão (minúsculas) aceite: ' . $r->getContent());
        $b3 = $this->bilheteNovo($viagem->fresh(), $pass);
        $r = $this->actingAs($cob)->postJson($url, ['codigo' => $b3->codigo_assinado]);
        $this->ok($r->json('ok') === true, 'código assinado escrito/lido por leitor USB aceite');
        $r = $this->actingAs($cob)->postJson($url, ['codigo' => 'MT-2026-999999.AAAAAAAA']);
        $this->ok($r->json('ok') === false, 'código assinado falso recusado');

        // --- viagem de outra transportadora => 403
        $alheia = Viagem::whereHas('rota', fn ($q) => $q->where('transportadora_id', '!=', $cob->transportadora_id))->first();
        $this->ok($this->actingAs($cob)->get("/cobrador/viagens/{$alheia->id}")->getStatusCode() === 403, 'viagem de outra transportadora => 403');
        $this->ok($this->actingAs($cob)->postJson("/cobrador/viagens/{$alheia->id}/validar", ['codigo' => $assinado])->getStatusCode() === 403, 'validar em viagem alheia => 403');

        // --- bilhete de outra viagem
        $outra = Viagem::whereDate('data', $hoje)->where('id', '!=', $viagem->id)->whereHas('rota', fn ($q) => $q->where('transportadora_id', $cob->transportadora_id))->first();
        $b4 = $this->bilheteNovo($outra, $pass);
        $r = $this->actingAs($cob)->postJson($url, ['codigo' => $b4->codigo_assinado, 'origem' => 'qr']);
        $this->ok($r->json('ok') === false && str_contains($r->json('mensagem'), 'outra viagem'), 'bilhete de outra viagem recusado');

        // --- nome do sistema no login
        auth()->logout();
        $this->ok(str_contains($this->get('/entrar')->getContent(), config('moz.nome')), 'nome do sistema no login');
        $this->ok(! str_contains($this->get('/painel/entrar')->getContent(), 'MozTransport'), 'sem o nome antigo no login do painel');

        $this->assertSame([], $this->falhas, "\n" . implode("\n", $this->falhas));
    }

    public function test_assinatura_depende_da_chave(): void
    {
        $a = Bilhete::assinatura('MT-2026-000001');
        config(['app.key' => 'base64:' . base64_encode(str_repeat('x', 32))]);
        $this->assertNotSame($a, Bilhete::assinatura('MT-2026-000001'));
        $this->assertSame(['codigo' => 'MT-1', 'assinado' => false, 'valido' => false], Bilhete::interpretar(' mt-1 '));
    }
}
