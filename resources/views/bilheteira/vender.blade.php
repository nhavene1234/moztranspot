@extends('layouts.painel')
@section('titulo', 'Vender bilhete')
@section('seccao', 'Bilheteira')
@section('painel')
@include('bilheteira._passos', ['passo' => 1])
<form class="card" method="get" action="{{ route('bilheteira.vender') }}">
    <div class="filters">
        <div class="field"><label>Origem</label><select class="input" name="origem" data-autosubmit>@foreach ($origens as $o)<option {{ $o === $origem ? 'selected' : '' }}>{{ $o }}</option>@endforeach</select></div>
        <div class="field"><label>Destino</label><select class="input" name="destino" data-autosubmit><option value="">Todos os destinos</option>@foreach ($destinos as $d)<option {{ $d === $destino ? 'selected' : '' }}>{{ $d }}</option>@endforeach</select></div>
        <div class="field"><label>Data de partida</label><input class="input" type="date" name="data" value="{{ $data }}" min="{{ now()->toDateString() }}" data-autosubmit></div>
        <button class="btn">Procurar</button>
    </div>
</form>
<div class="card mt">
    <div class="row between mb"><h2>Viagens disponíveis {{ $hoje ? 'para hoje' : 'em ' . \Carbon\Carbon::parse($data)->format('d/m/Y') }}</h2><span class="muted small">{{ $viagens->count() }} rotas encontradas</span></div>
    <div class="tbl-scroll"><table class="tbl">
        <thead><tr><th>Transportadora</th><th>Rota</th><th>Partida</th><th>Lugares livres</th><th>Preço</th><th></th></tr></thead>
        <tbody>
        @forelse ($viagens as $v)
            @php $livres = $v->autocarro->capacidade_total - $v->ocupados_count; @endphp
            <tr>
                <td><b>{{ $v->rota->transportadora->nome }}</b><div class="small muted">{{ $v->classe }}</div></td>
                <td>{{ $v->rota->nome }}</td>
                <td>{{ $v->hora }}</td>
                <td><span class="pill {{ $livres < 1 ? 'err' : ($livres <= 8 ? 'warn' : 'ok') }}">{{ $livres }} livres</span></td>
                <td><b>@mt($v->preco)</b></td>
                <td class="right">@if ($livres > 0)<a class="btn btn-sm" href="{{ route('bilheteira.lugares', $v) }}">Seleccionar</a>@else<span class="btn btn-sm btn-outline disabled">Esgotado</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6" class="center muted" style="padding:28px">Sem viagens para estes filtros.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
