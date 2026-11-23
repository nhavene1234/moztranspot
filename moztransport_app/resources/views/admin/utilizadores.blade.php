@extends('layouts.painel')
@section('titulo', 'Gerir utilizadores')
@section('seccao', 'Painel Admin')
@section('painel')
<div class="page-head"><div><h1>Utilizadores do Sistema</h1><p>Gerencie permissões, crie contas para funcionários e controle acessos.</p></div><button class="btn" data-modal-open="m-user">+ Novo utilizador</button></div>
@if (session('senha_temporaria'))
    <div class="alert alert-warn"><strong>Palavra-passe temporária (mostrada só agora)</strong>
        {{ session('senha_temporaria')['email'] }} · <b class="mono">{{ session('senha_temporaria')['senha'] }}</b> — entregue ao utilizador por canal seguro.</div>
@endif
<div class="card">
    <form method="get" class="filters">
        <div class="field grow"><label>Pesquisar</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Nome ou email"></div>
        <div class="field"><label>Perfil</label><select class="input" name="perfil" data-autosubmit><option value="">Todos</option>@foreach (\App\Models\Utilizador::PERFIS as $k => $t)<option value="{{ $k }}" {{ request('perfil') === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></div>
        <div class="field"><label>Transportadora</label><select class="input" name="transportadora" data-autosubmit><option value="">Todas</option>@foreach ($transportadoras as $t)<option value="{{ $t->id }}" {{ (int) request('transportadora') === $t->id ? 'selected' : '' }}>{{ $t->nome }}</option>@endforeach</select></div>
        <div class="field"><label>Estado</label><select class="input" name="estado" data-autosubmit><option value="">Todos</option><option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Activo</option><option value="bloqueado" {{ request('estado') === 'bloqueado' ? 'selected' : '' }}>Bloqueado</option></select></div>
        <button class="btn btn-outline">Filtrar</button>
    </form>
    <div class="tbl-scroll"><table class="tbl">
        <thead><tr><th>Nome / Email</th><th>Perfil</th><th>Transportadora</th><th>Estado</th><th class="right">Acções</th></tr></thead><tbody>
        @foreach ($utilizadores as $u)
            <tr><td><div class="row"><span class="avatar" style="width:32px;height:32px;font-size:11px">{{ $u->iniciais() }}</span><div><b>{{ $u->nome }}</b><div class="small muted">{{ $u->email }}</div></div></div></td>
                <td><span class="pill accent">{{ $u->rotuloPerfil() }}</span></td><td>{{ $u->transportadora->nome ?? '—' }}</td>
                <td><span class="pill {{ $u->estado === 'bloqueado' ? 'err' : 'ok' }}">{{ $u->estado === 'bloqueado' ? 'Bloqueado' : 'Activo' }}</span></td>
                <td class="right"><div class="row" style="justify-content:flex-end">
                    <form method="post" action="{{ route('admin.utilizadores.bloquear', $u) }}" data-confirm="{{ $u->estado === 'bloqueado' ? 'Desbloquear' : 'Bloquear' }} {{ $u->nome }}?">@csrf<button class="btn btn-sm btn-outline">{{ $u->estado === 'bloqueado' ? 'Desbloquear' : 'Bloquear' }}</button></form>
                    <form method="post" action="{{ route('admin.utilizadores.senha', $u) }}" data-confirm="Repor a palavra-passe de {{ $u->nome }}?">@csrf<button class="btn btn-sm btn-outline">Repor senha</button></form></div></td></tr>
        @endforeach
        </tbody></table></div>
    {{ $utilizadores->links() }}
</div>

<div class="modal-bg {{ $errors->any() ? 'open' : '' }}" id="m-user"><div class="modal"><h2>Novo utilizador</h2><p class="muted small mb">Preencha os dados para criar uma credencial. Será gerada uma palavra-passe temporária.</p>
    <form method="post" action="{{ route('admin.utilizadores.guardar') }}">@csrf
        <div class="field"><label>Nome completo *</label><input class="input" name="nome" value="{{ old('nome') }}" required>@error('nome')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Endereço de email *</label><input class="input" type="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Perfil *</label><select class="input" name="perfil" id="perfil-sel">@foreach (\App\Models\Utilizador::PERFIS as $k => $t)<option value="{{ $k }}" {{ old('perfil', 'gestor') === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></div>
        <div class="field"><label>Transportadora (gestor, cobrador, bilheteiro)</label><select class="input" name="transportadora_id"><option value="">—</option>@foreach ($transportadoras as $t)<option value="{{ $t->id }}" {{ (int) old('transportadora_id') === $t->id ? 'selected' : '' }}>{{ $t->nome }}</option>@endforeach</select>@error('transportadora_id')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="alert alert-info small"><strong>Permissões limitadas</strong>O gestor só verá dados desta transportadora (rotas, relatórios e vendas).</div>
        <div class="actions"><button type="button" class="btn btn-outline" data-modal-close>Cancelar</button><button class="btn">Guardar</button></div>
    </form></div></div>
@endsection
