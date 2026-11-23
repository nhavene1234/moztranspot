<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperacaoController;
use App\Http\Controllers\Auth\RegistoController;
use App\Http\Controllers\Bilheteiro\BilheteiraController;
use App\Http\Controllers\Cobrador\CobradorController;
use App\Http\Controllers\Gestao\FrotaController;
use App\Http\Controllers\Gestao\GestaoController;
use App\Http\Controllers\Gestao\PessoalController;
use App\Http\Controllers\Passageiro\PassageiroController;
use App\Http\Controllers\PerfilController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect(auth()->user()->rotaInicial()) : redirect()->route('entrar'));

/* ---------------------------------------------------------------- acesso (visitantes) */
Route::middleware('guest')->group(function () {
    // Passageiros
    Route::get('/entrar', [LoginController::class, 'mostrar'])->name('entrar');
    Route::post('/entrar', [LoginController::class, 'entrar'])->name('entrar.enviar')->middleware('throttle:30,1,login-app');
    Route::get('/criar-conta', [RegistoController::class, 'mostrar'])->name('conta.criar');
    Route::post('/criar-conta', [RegistoController::class, 'criar'])->name('conta.guardar')->middleware('throttle:10,1,registo');

    // Equipa (bilheteiro, cobrador, gestor, administrador)
    Route::get('/painel/entrar', [LoginController::class, 'mostrar'])->name('painel.entrar');
    Route::post('/painel/entrar', [LoginController::class, 'entrar'])->name('painel.entrar.enviar')->middleware('throttle:30,1,login-painel');

    // Recuperação de palavra-passe: /recuperar (passageiro) e /painel/recuperar (equipa)
    foreach (['' => 'recuperar', 'painel/' => 'painel.recuperar'] as $prefixo => $nome) {
        Route::get("/{$prefixo}recuperar", [RecuperacaoController::class, 'formulario'])->name("{$nome}.form");
        Route::post("/{$prefixo}recuperar", [RecuperacaoController::class, 'enviar'])->name("{$nome}.enviar")->middleware("throttle:6,1,{$nome}-enviar");
        Route::get("/{$prefixo}recuperar/codigo", [RecuperacaoController::class, 'codigo'])->name("{$nome}.codigo");
        Route::post("/{$prefixo}recuperar/reenviar", [RecuperacaoController::class, 'reenviar'])->name("{$nome}.reenviar")->middleware("throttle:3,1,{$nome}-reenviar");
        Route::post("/{$prefixo}recuperar/verificar", [RecuperacaoController::class, 'verificar'])->name("{$nome}.verificar")->middleware("throttle:10,1,{$nome}-verificar");
        Route::get("/{$prefixo}recuperar/nova", [RecuperacaoController::class, 'nova'])->name("{$nome}.nova");
        Route::post("/{$prefixo}recuperar/redefinir", [RecuperacaoController::class, 'redefinir'])->name("{$nome}.redefinir")->middleware("throttle:10,1,{$nome}-redefinir");
    }
});
Route::get('/conta-criada', [RegistoController::class, 'criada'])->name('conta.criada');

/* ---------------------------------------------------------------- qualquer utilizador autenticado */
Route::middleware(['auth', 'activo'])->group(function () {
    Route::post('/sair', [LoginController::class, 'sair'])->name('sair');

    Route::get('/perfil', [PerfilController::class, 'mostrar'])->name('perfil');
    Route::put('/perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');
    Route::put('/perfil/senha', [PerfilController::class, 'senha'])->name('perfil.senha');
    Route::put('/perfil/notificacoes', [PerfilController::class, 'notificacoes'])->name('perfil.notificacoes');
});

/* ---------------------------------------------------------------- passageiro */
Route::middleware(['auth', 'activo', 'perfil:passageiro'])->prefix('app')->name('app.')->controller(PassageiroController::class)->group(function () {
    Route::get('/', 'inicio')->name('inicio');
    Route::get('/viagens', 'pesquisar')->name('viagens');
    Route::get('/viagens/{viagem}/lugares', 'lugares')->name('lugares');
    Route::post('/viagens/{viagem}/reservar', 'reservar')->name('reservar');

    Route::get('/reservas/{reserva}/pagamento', 'pagamento')->name('pagamento');
    Route::post('/reservas/{reserva}/pagar', 'pagar')->name('pagar');
    Route::get('/reservas/{reserva}/resultado', 'resultado')->name('resultado');

    Route::get('/bilhetes', 'bilhetes')->name('bilhetes');
    Route::get('/bilhetes/{reserva}', 'bilhete')->name('bilhete');
    Route::get('/historico', 'historico')->name('historico');

    Route::post('/reservas/{reserva}/cancelar', 'cancelar')->name('cancelar');
    Route::get('/cancelada', 'cancelada')->name('cancelada');
    Route::get('/reservas/{reserva}/remarcar', 'remarcar')->name('remarcar');
    Route::get('/reservas/{reserva}/remarcar/{viagem}', 'remarcarLugares')->name('remarcar.lugares');
    Route::post('/reservas/{reserva}/remarcar/{viagem}', 'remarcarConfirmar')->name('remarcar.confirmar');
});

/* ---------------------------------------------------------------- bilheteiro */
Route::middleware(['auth', 'activo', 'permissao:vender_bilhetes'])->prefix('bilheteira')->name('bilheteira.')->controller(BilheteiraController::class)->group(function () {
    Route::get('/vender', 'vender')->name('vender');
    Route::get('/vender/{viagem}/lugares', 'lugares')->name('lugares');
    Route::post('/vender/{viagem}/lugares', 'bloquear')->name('bloquear');
    Route::get('/vender/pagamento', 'pagamento')->name('pagamento');
    Route::post('/vender/pagamento', 'registar')->name('registar');
    Route::get('/venda/{grupo}', 'venda')->name('venda');
    Route::post('/venda/{grupo}/sms', 'sms')->name('sms');

    Route::get('/lugares', 'lugaresDisponiveis')->name('lugares.lista');
    Route::get('/lugares/{viagem}/estado', 'estado')->name('lugares.estado');

    Route::get('/vendas', 'vendasDoDia')->name('vendas');
    Route::get('/vendas/exportar', 'exportar')->name('vendas.exportar');
});

/* ---------------------------------------------------------------- cobrador / motorista */
Route::middleware(['auth', 'activo', 'perfil:cobrador', 'permissao:ver_dashboard'])->prefix('cobrador')->name('cobrador.')->controller(CobradorController::class)->group(function () {
    Route::get('/', 'viagens')->name('viagens');
    Route::get('/viagens/{viagem}', 'viagem')->name('viagem');
    Route::post('/viagens/{viagem}/validar', 'validar')->name('validar');
    Route::post('/viagens/{viagem}/terminar', 'terminar')->name('terminar');
});

/* ---------------------------------------------------------------- gestor (e administrador) */
Route::middleware(['auth', 'activo', 'perfil:gestor,administrador'])->prefix('gestao')->name('gestao.')->controller(GestaoController::class)->group(function () {
    Route::middleware('permissao:ver_dashboard')->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/vendas', 'vendasLista')->name('vendas');
    });
    Route::middleware('permissao:ver_relatorios')->group(function () {
        Route::get('/relatorios', 'relatorios')->name('relatorios');
        Route::get('/relatorios/exportar', 'exportarRelatorio')->name('relatorios.exportar');
        Route::get('/estatisticas', 'estatisticas')->name('estatisticas');
    });
    Route::middleware('permissao:gerir_rotas')->group(function () {
        Route::get('/rotas', 'rotas')->name('rotas');
        Route::post('/rotas', 'rotaGuardar')->name('rotas.guardar');
        Route::put('/rotas/{rota}', 'rotaActualizar')->name('rotas.actualizar');
        Route::post('/rotas/{rota}/estado', 'rotaEstado')->name('rotas.estado');
        Route::post('/viagens', 'viagemGuardar')->name('viagens.guardar');
    });
    // Autocarros e livrete
    Route::middleware('permissao:gerir_frota')->controller(FrotaController::class)->group(function () {
        Route::get('/autocarros', 'index')->name('autocarros');
        Route::post('/autocarros', 'guardar')->name('autocarros.guardar');
        Route::put('/autocarros/{autocarro}', 'actualizar')->name('autocarros.actualizar');
        Route::post('/autocarros/{autocarro}/estado', 'estado')->name('autocarros.estado');
        Route::delete('/autocarros/{autocarro}', 'eliminar')->name('autocarros.eliminar');
        Route::get('/autocarros/{autocarro}/livrete', 'livrete')->name('autocarros.livrete');
    });
    // Motoristas e cobradores
    Route::middleware('permissao:gerir_pessoal')->controller(PessoalController::class)->group(function () {
        Route::get('/motoristas', 'motoristas')->name('motoristas');
        Route::post('/motoristas', 'motoristaGuardar')->name('motoristas.guardar');
        Route::put('/motoristas/{motorista}', 'motoristaActualizar')->name('motoristas.actualizar');
        Route::post('/motoristas/{motorista}/estado', 'motoristaEstado')->name('motoristas.estado');
        Route::delete('/motoristas/{motorista}', 'motoristaEliminar')->name('motoristas.eliminar');

        Route::get('/cobradores', 'cobradores')->name('cobradores');
        Route::post('/cobradores', 'cobradorGuardar')->name('cobradores.guardar');
        Route::put('/cobradores/{cobrador}', 'cobradorActualizar')->name('cobradores.actualizar');
        Route::post('/cobradores/{cobrador}/estado', 'cobradorEstado')->name('cobradores.estado');
        Route::post('/cobradores/{cobrador}/senha', 'cobradorSenha')->name('cobradores.senha');
        Route::delete('/cobradores/{cobrador}', 'cobradorEliminar')->name('cobradores.eliminar');
    });
});

/* ---------------------------------------------------------------- administrador */
Route::middleware(['auth', 'activo', 'perfil:administrador'])->prefix('admin')->name('admin.')->controller(AdminController::class)->group(function () {
    Route::get('/', 'visao')->name('visao')->middleware('permissao:ver_dashboard');

    Route::middleware('permissao:gerir_utilizadores')->group(function () {
        Route::get('/utilizadores', 'utilizadores')->name('utilizadores');
        Route::post('/utilizadores', 'utilizadorGuardar')->name('utilizadores.guardar');
        Route::post('/utilizadores/{utilizador}/bloquear', 'utilizadorBloquear')->name('utilizadores.bloquear');
        Route::post('/utilizadores/{utilizador}/repor-senha', 'utilizadorReporSenha')->name('utilizadores.senha');
    });
    Route::middleware('permissao:gerir_permissoes')->group(function () {
        Route::get('/permissoes', 'permissoes')->name('permissoes');
        Route::post('/permissoes', 'permissoesGuardar')->name('permissoes.guardar');
        Route::post('/permissoes/repor', 'permissoesRepor')->name('permissoes.repor');
    });
    Route::middleware('permissao:gerir_transportadoras')->group(function () {
        Route::get('/transportadoras', 'transportadoras')->name('transportadoras');
        Route::post('/transportadoras', 'transportadoraGuardar')->name('transportadoras.guardar');
    });
    Route::middleware('permissao:ver_auditoria')->group(function () {
        Route::get('/auditoria', 'auditoria')->name('auditoria');
        Route::get('/auditoria/exportar', 'auditoriaCsv')->name('auditoria.exportar');
    });
    Route::middleware('permissao:gerir_seguranca')->group(function () {
        Route::get('/seguranca', 'seguranca')->name('seguranca');
        Route::post('/seguranca', 'segurancaGuardar')->name('seguranca.guardar');
        Route::post('/seguranca/backup', 'backup')->name('seguranca.backup');
    });
});
