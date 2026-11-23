@extends('layouts.painel')
@section('titulo', 'Motoristas')
@section('seccao', 'Gestão')
@section('painel')
<div class="page-head"><div><h1>Motoristas</h1><p>Registe os motoristas e controle a validade da carta de condução.</p></div>
    <div class="row wrap">@include('gestao._empresa')<button class="btn" data-modal-open="m-mot">+ Novo motorista</button></div></div>

<div class="card">
    <form method="get" class="filters">
        @if (request('transportadora'))<input type="hidden" name="transportadora" value="{{ request('transportadora') }}">@endif
        <div class="field grow"><label>Pesquisar</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Nome, carta ou telefone"></div>
        <button class="btn btn-outline">Pesquisar</button>
    </form>
    <div class="tbl-scroll"><table class="tbl">
        <thead><tr><th>Motorista</th><th>Carta de condução</th>@if ($admin)<th>Transportadora</th>@endif<th>Viagens</th><th>Estado</th><th class="right">Acções</th></tr></thead><tbody>
        @forelse ($motoristas as $m)
            @php
                $fill = ['_put' => 1, '_titulo' => 'Editar motorista', 'nome' => $m->nome, 'telefone' => $m->telefone, 'documento' => $m->documento, 'carta_numero' => $m->carta_numero, 'carta_categoria' => $m->carta_categoria, 'carta_validade' => $m->carta_validade?->format('Y-m-d')];
                $carta = $m->estadoCarta();
            @endphp
            <tr>
                <td><b>{{ $m->nome }}</b><div class="small muted">{{ $m->telefone ?: 'Sem telefone' }}{{ $m->documento ? ' · BI ' . $m->documento : '' }}</div></td>
                <td><span class="mono">{{ $m->carta_numero }}</span>{{ $m->carta_categoria ? ' · cat. ' . $m->carta_categoria : '' }}
                    <div>@if ($carta === 'caducada')<span class="pill err">Caducada em {{ $m->carta_validade->format('d/m/Y') }}</span>
                        @elseif ($carta === 'a_caducar')<span class="pill warn">Caduca em {{ $m->carta_validade->format('d/m/Y') }}</span>
                        @elseif ($carta === 'valida')<span class="small muted">Válida até {{ $m->carta_validade->format('d/m/Y') }}</span>
                        @else<span class="small muted">Sem data de validade</span>@endif</div></td>
                @if ($admin)<td>{{ $m->transportadora->nome }}</td>@endif
                <td>{{ $m->viagens_count }}</td>
                <td><span class="pill {{ $m->estado === 'activo' ? 'ok' : 'err' }}">{{ $m->estado === 'activo' ? 'Activo' : 'Inactivo' }}</span></td>
                <td class="right"><div class="row" style="justify-content:flex-end">
                    <button class="btn btn-sm btn-outline" data-modal-open="m-mot" data-action="{{ route('gestao.motoristas.actualizar', $m) }}" data-fill="{{ json_encode($fill) }}">Editar</button>
                    <form method="post" action="{{ route('gestao.motoristas.estado', $m) }}">@csrf<button class="btn btn-sm {{ $m->estado === 'activo' ? 'btn-danger-outline' : 'btn-soft' }}">{{ $m->estado === 'activo' ? 'Desactivar' : 'Activar' }}</button></form>
                    <form method="post" action="{{ route('gestao.motoristas.eliminar', $m) }}" data-confirm="Eliminar o motorista {{ $m->nome }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-danger-outline" @disabled($m->viagens_count)>Eliminar</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="center muted" style="padding:26px">Ainda não há motoristas. Clique em “+ Novo motorista”.</td></tr>
        @endforelse
        </tbody></table></div>
    <p class="small muted mt">Só é possível eliminar motoristas sem viagens; os restantes podem ser desactivados.</p>
</div>

<div class="modal-bg" id="m-mot" data-reset="1"><div class="modal">
    <h2 data-title="Novo motorista">Novo motorista</h2>
    <form method="post" action="{{ route('gestao.motoristas.guardar') }}" data-create-action="{{ route('gestao.motoristas.guardar') }}">@csrf
        <input type="hidden" name="_method" value="PUT" disabled>
        @if ($admin)<div class="field"><label>Transportadora *</label><select class="input" name="transportadora_id"><option value="">—</option>@foreach ($empresas as $e)<option value="{{ $e->id }}" {{ (int) old('transportadora_id', $tid) === $e->id ? 'selected' : '' }}>{{ $e->nome }}</option>@endforeach</select></div>@endif
        <div class="field"><label>Nome completo *</label><input class="input" name="nome" required maxlength="120"></div>
        <div class="grid2"><div class="field"><label>Telefone</label><input class="input" name="telefone" maxlength="30" placeholder="+258 84 000 0000"></div>
            <div class="field"><label>BI / Passaporte</label><input class="input" name="documento" maxlength="30"></div></div>
        <div class="grid3"><div class="field"><label>Nº da carta *</label><input class="input mono" name="carta_numero" required maxlength="40"></div>
            <div class="field"><label>Categoria</label><input class="input" name="carta_categoria" maxlength="10" placeholder="D"></div>
            <div class="field"><label>Validade</label><input class="input" type="date" name="carta_validade"></div></div>
        <div class="actions"><button type="button" class="btn btn-outline" data-modal-close>Cancelar</button><button class="btn">Guardar</button></div>
    </form></div></div>
@endsection
