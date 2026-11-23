@extends('layouts.painel')
@section('titulo', 'Vendas')
@section('seccao', 'Gestão')
@section('painel')
<div class="page-head"><div><h1>Vendas e reservas</h1><p>Consulte, filtre e acompanhe cada reserva.</p></div>@include('gestao._empresa')</div>
<form class="card mb" method="get">
    @if ($tid)<input type="hidden" name="transportadora" value="{{ $tid }}">@endif
    <div class="filters">
        <div class="field"><label>De</label><input class="input" type="date" name="de" value="{{ $de->toDateString() }}"></div>
        <div class="field"><label>Até</label><input class="input" type="date" name="ate" value="{{ $ate->toDateString() }}"></div>
        <div class="field"><label>Estado</label><select class="input" name="estado"><option value="">Todos</option>@foreach (['confirmada' => 'Confirmada', 'cancelada' => 'Cancelada', 'expirada' => 'Expirada', 'pendente' => 'Pendente'] as $k => $t)<option value="{{ $k }}" {{ request('estado') === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></div>
        <div class="field"><label>Rota</label><select class="input" name="rota"><option value="">Todas</option>@foreach ($rotas as $r)<option value="{{ $r }}" {{ request('rota') === $r ? 'selected' : '' }}>{{ str_replace('|', ' → ', $r) }}</option>@endforeach</select></div>
        <div class="field grow"><label>Passageiro ou código</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Nome ou MT-2026-…"></div>
        <button class="btn">Filtrar</button>
    </div>
</form>
<div class="card"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th>Data</th><th>Bilhete</th><th>Passageiro</th><th>Rota</th><th>Viagem</th><th>Lugar</th><th>Valor</th><th>Estado</th></tr></thead><tbody>
    @forelse ($reservas as $r)
        <tr><td>{{ $r->data_reserva->format('d/m H:i') }}</td><td class="mono small">{{ $r->bilhete->codigo_qr ?? '—' }}</td><td><b>{{ $r->nome_passageiro }}</b></td><td>{{ $r->viagem->rota->nome }}</td>
            <td>{{ $r->viagem->data->format('d/m') }} {{ $r->viagem->hora }}</td><td>{{ $r->assento->rotulo }}</td><td>@mt($r->viagem->preco)</td>
            <td><span class="pill {{ ['confirmada' => 'ok', 'cancelada' => 'err', 'expirada' => '', 'pendente' => 'warn'][$r->estado] ?? '' }}">{{ $r->rotuloEstado() }}</span></td></tr>
    @empty
        <tr><td colspan="8" class="center muted" style="padding:26px">Nenhum resultado.</td></tr>
    @endforelse
    </tbody></table></div>{{ $reservas->links() }}</div>
@endsection
