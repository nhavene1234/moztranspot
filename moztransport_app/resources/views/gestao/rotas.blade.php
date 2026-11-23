@extends('layouts.painel')
@section('titulo', 'Rotas e horários')
@section('seccao', 'Gestão')
@section('painel')
@php $admin = auth()->user()->tem('ver_todas_transportadoras'); @endphp
<div class="page-head"><div><h1>Rotas e horários</h1><p>Crie rotas, registe autocarros e programe as viagens.</p></div>
    <div class="row wrap">@include('gestao._empresa')
        <button class="btn btn-outline" data-modal-open="m-bus">+ Autocarro</button>
        <button class="btn btn-outline" data-modal-open="m-viagem">+ Programar viagens</button>
        <button class="btn" data-modal-open="m-rota">+ Nova rota</button></div></div>
<div class="card"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th>Rota</th>@if ($admin)<th>Transportadora</th>@endif<th>Distância</th><th>Duração</th><th>Preço base</th><th>Estado</th><th></th></tr></thead><tbody>
    @forelse ($rotas as $r)
        @php $fill = ['_put' => 1, '_titulo' => 'Editar rota', 'origem' => $r->origem, 'destino' => $r->destino, 'distancia_km' => $r->distancia_km, 'horas' => intdiv($r->duracao_minutos, 60), 'minutos' => $r->duracao_minutos % 60, 'preco_base' => $r->preco_base]; @endphp
        <tr><td><b>{{ $r->nome }}</b></td>@if ($admin)<td>{{ $r->transportadora->nome }}</td>@endif<td>{{ $r->distancia_km }} km</td><td>{{ $r->duracao_texto }}</td><td>@mt($r->preco_base)</td>
            <td><span class="pill {{ $r->estado === 'activa' ? 'ok' : 'err' }}">{{ $r->estado === 'activa' ? 'Activa' : 'Desactivada' }}</span></td>
            <td class="right"><div class="row" style="justify-content:flex-end">
                <button class="btn btn-sm btn-outline" data-modal-open="m-rota" data-action="{{ route('gestao.rotas.actualizar', $r) }}" data-fill="{{ json_encode($fill) }}">Editar</button>
                <form method="post" action="{{ route('gestao.rotas.estado', $r) }}" @if ($r->estado === 'activa' && ($futuras[$r->id] ?? 0)) data-confirm="Esta rota tem {{ $futuras[$r->id] }} reserva(s) confirmada(s) futura(s). Elas mantêm-se válidas, mas deixa de vender novos bilhetes. Continuar?" @endif>@csrf
                    <button class="btn btn-sm {{ $r->estado === 'activa' ? 'btn-danger-outline' : 'btn-soft' }}">{{ $r->estado === 'activa' ? 'Desactivar' : 'Activar' }}</button></form></div></td></tr>
    @empty<tr><td colspan="7" class="center muted" style="padding:26px">Ainda não há rotas.</td></tr>@endforelse
    </tbody></table></div></div>

<div class="card mt"><h2 class="mb">Autocarros ({{ $autocarros->count() }})</h2>
    <div class="row wrap">@forelse ($autocarros as $a)<span class="pill accent">{{ $a->matricula }} · {{ $a->capacidade_total }} lugares</span>@empty<span class="muted small">Sem autocarros.</span>@endforelse</div></div>

{{-- modais --}}
<div class="modal-bg" id="m-rota" data-reset="1"><div class="modal">
    <h2 data-title="Nova rota">Nova rota</h2><p class="muted small">As alterações aplicam-se a futuras viagens; reservas existentes mantêm o preço.</p>
    <form method="post" action="{{ route('gestao.rotas.guardar') }}" data-create-action="{{ route('gestao.rotas.guardar') }}">@csrf
        <input type="hidden" name="_method" value="PUT" disabled>
        @if ($admin)<div class="field"><label>Transportadora</label><select class="input" name="transportadora_id" required>@foreach ($empresas as $e)<option value="{{ $e->id }}">{{ $e->nome }}</option>@endforeach</select></div>@endif
        <div class="grid2"><div class="field"><label>Origem</label><input class="input" name="origem" required></div><div class="field"><label>Destino</label><input class="input" name="destino" required></div></div>
        <div class="grid3"><div class="field"><label>Distância (km)</label><input class="input" type="number" name="distancia_km" min="1" required></div>
            <div class="field"><label>Horas</label><input class="input" type="number" name="horas" min="0" max="99" value="0" required></div>
            <div class="field"><label>Minutos</label><input class="input" type="number" name="minutos" min="0" max="59" value="0" required></div></div>
        <div class="field"><label>Preço base (MT)</label><input class="input" type="number" name="preco_base" min="1" required></div>
        <div class="actions"><button type="button" class="btn btn-outline" data-modal-close>Cancelar</button><button class="btn">Guardar</button></div>
    </form></div></div>

<div class="modal-bg" id="m-bus"><div class="modal"><h2>Registar autocarro</h2><p class="muted small">Cria o autocarro com 44 lugares (mapa 2+2).</p>
    <form method="post" action="{{ route('gestao.autocarros.guardar') }}">@csrf
        @if ($admin)<div class="field"><label>Transportadora</label><select class="input" name="transportadora_id" required>@foreach ($empresas as $e)<option value="{{ $e->id }}">{{ $e->nome }}</option>@endforeach</select></div>@endif
        <div class="field"><label>Matrícula</label><input class="input" name="matricula" required placeholder="ABC-123-MP"></div>
        <div class="field"><label>Modelo</label><input class="input" name="modelo"></div>
        <div class="actions"><button type="button" class="btn btn-outline" data-modal-close>Cancelar</button><button class="btn">Registar</button></div></form></div></div>

<div class="modal-bg" id="m-viagem"><div class="modal"><h2>Programar viagens</h2><p class="muted small">Cria a mesma viagem para vários dias seguidos.</p>
    <form method="post" action="{{ route('gestao.viagens.guardar') }}">@csrf
        <div class="field"><label>Rota</label><select class="input" name="rota_id" required>@foreach ($rotas->where('estado', 'activa') as $r)<option value="{{ $r->id }}">{{ $r->nome }}{{ $admin ? ' · ' . $r->transportadora->nome : '' }}</option>@endforeach</select></div>
        <div class="field"><label>Autocarro</label><select class="input" name="autocarro_id" required>@foreach ($autocarros as $a)<option value="{{ $a->id }}">{{ $a->matricula }}{{ $admin ? ' · ' . $a->transportadora->nome : '' }}</option>@endforeach</select></div>
        <div class="field"><label>Cobrador (opcional)</label><select class="input" name="cobrador_id"><option value="">—</option>@foreach ($cobradores as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach</select></div>
        <div class="grid3"><div class="field"><label>Hora</label><input class="input" type="time" name="hora" value="06:00" required></div>
            <div class="field"><label>Classe</label><input class="input" name="classe" value="Executivo" required></div>
            <div class="field"><label>Preço (MT)</label><input class="input" type="number" name="preco" min="1" required></div></div>
        <div class="grid2"><div class="field"><label>A partir de</label><input class="input" type="date" name="data_inicio" min="{{ now()->toDateString() }}" value="{{ now()->addDay()->toDateString() }}" required></div>
            <div class="field"><label>Nº de dias seguidos</label><input class="input" type="number" name="dias" min="1" max="60" value="7" required></div></div>
        <div class="actions"><button type="button" class="btn btn-outline" data-modal-close>Cancelar</button><button class="btn">Programar</button></div></form></div></div>
@endsection
