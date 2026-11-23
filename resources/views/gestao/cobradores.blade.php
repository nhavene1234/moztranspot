@extends('layouts.painel')
@section('titulo', 'Cobradores')
@section('seccao', 'Gestão')
@section('painel')
<div class="page-head"><div><h1>Cobradores</h1><p>Crie as contas dos cobradores que validam os bilhetes nas viagens.</p></div>
    <div class="row wrap">@include('gestao._empresa')<button class="btn" data-modal-open="m-cob">+ Novo cobrador</button></div></div>
@if (session('senha_temporaria'))
    <div class="alert alert-warn"><strong>Palavra-passe temporária (mostrada só agora)</strong>
        {{ session('senha_temporaria')['email'] }} · <b class="mono">{{ session('senha_temporaria')['senha'] }}</b> — entregue ao cobrador por um canal seguro.</div>
@endif
<div class="card">
    <form method="get" class="filters">
        @if (request('transportadora'))<input type="hidden" name="transportadora" value="{{ request('transportadora') }}">@endif
        <div class="field grow"><label>Pesquisar</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Nome, email ou telefone"></div>
        <button class="btn btn-outline">Pesquisar</button>
    </form>
    <div class="tbl-scroll"><table class="tbl">
        <thead><tr><th>Cobrador</th>@if ($admin)<th>Transportadora</th>@endif<th>Viagens</th><th>Estado</th><th class="right">Acções</th></tr></thead><tbody>
        @forelse ($cobradores as $c)
            @php $fill = ['_put' => 1, '_titulo' => 'Editar cobrador', 'nome' => $c->nome, 'email' => $c->email, 'telefone' => $c->telefone]; @endphp
            <tr>
                <td><div class="row"><span class="avatar" style="width:32px;height:32px;font-size:11px">{{ $c->iniciais() }}</span><div><b>{{ $c->nome }}</b><div class="small muted">{{ $c->email }}{{ $c->telefone ? ' · ' . $c->telefone : '' }}</div></div></div></td>
                @if ($admin)<td>{{ $c->transportadora->nome ?? '—' }}</td>@endif
                <td>{{ $viagens[$c->id] ?? 0 }}</td>
                <td><span class="pill {{ $c->estado === 'bloqueado' ? 'err' : 'ok' }}">{{ $c->estado === 'bloqueado' ? 'Bloqueado' : 'Activo' }}</span></td>
                <td class="right"><div class="row" style="justify-content:flex-end">
                    <button class="btn btn-sm btn-outline" data-modal-open="m-cob" data-action="{{ route('gestao.cobradores.actualizar', $c) }}" data-fill="{{ json_encode($fill) }}">Editar</button>
                    <form method="post" action="{{ route('gestao.cobradores.estado', $c) }}" data-confirm="{{ $c->estado === 'bloqueado' ? 'Desbloquear' : 'Bloquear' }} {{ $c->nome }}?">@csrf<button class="btn btn-sm btn-outline">{{ $c->estado === 'bloqueado' ? 'Desbloquear' : 'Bloquear' }}</button></form>
                    <form method="post" action="{{ route('gestao.cobradores.senha', $c) }}" data-confirm="Repor a palavra-passe de {{ $c->nome }}?">@csrf<button class="btn btn-sm btn-outline">Repor senha</button></form>
                    <form method="post" action="{{ route('gestao.cobradores.eliminar', $c) }}" data-confirm="Eliminar {{ $c->nome }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-danger-outline" @disabled(($viagens[$c->id] ?? 0) > 0)>Eliminar</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="5" class="center muted" style="padding:26px">Ainda não há cobradores. Clique em “+ Novo cobrador”.</td></tr>
        @endforelse
        </tbody></table></div>
    <p class="small muted mt">Cobradores com viagens no histórico não podem ser eliminados: bloqueie a conta.</p>
</div>

<div class="modal-bg" id="m-cob" data-reset="1"><div class="modal">
    <h2 data-title="Novo cobrador">Novo cobrador</h2><p class="muted small mb">Ao criar, é gerada uma palavra-passe temporária.</p>
    <form method="post" action="{{ route('gestao.cobradores.guardar') }}" data-create-action="{{ route('gestao.cobradores.guardar') }}">@csrf
        <input type="hidden" name="_method" value="PUT" disabled>
        @if ($admin)<div class="field"><label>Transportadora *</label><select class="input" name="transportadora_id"><option value="">—</option>@foreach ($empresas as $e)<option value="{{ $e->id }}" {{ (int) old('transportadora_id', $tid) === $e->id ? 'selected' : '' }}>{{ $e->nome }}</option>@endforeach</select></div>@endif
        <div class="field"><label>Nome completo *</label><input class="input" name="nome" required maxlength="120"></div>
        <div class="field"><label>Email *</label><input class="input" type="email" name="email" required maxlength="150"></div>
        <div class="field"><label>Telefone</label><input class="input" name="telefone" maxlength="30"></div>
        <div class="actions"><button type="button" class="btn btn-outline" data-modal-close>Cancelar</button><button class="btn">Guardar</button></div>
    </form></div></div>
@endsection
