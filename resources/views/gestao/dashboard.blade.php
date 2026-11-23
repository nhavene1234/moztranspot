@extends('layouts.painel')
@section('titulo', 'Dashboard')
@section('seccao', 'Gestão')
@section('painel')
<div class="page-head"><div><h1>Painel de vendas{{ $empresa ? ' · ' . $empresa->nome : '' }}</h1><p>Receita, ocupação e rotas mais rentáveis.</p></div>@include('gestao._empresa')</div>
<div class="grid4 mb">
    <div class="kpi">@include('gestao._spark', ['serie' => $serieReceita])<div class="t">Receita de hoje</div><div class="v">@mt($receitaHoje)</div>
        <div class="d {{ $variacaoReceita === null ? 'neutral' : ($variacaoReceita < 0 ? 'neg' : '') }}">{{ $variacaoReceita === null ? 'Sem referência' : ($variacaoReceita >= 0 ? '+' : '') . $variacaoReceita . '% vs. média dos 6 dias anteriores' }}</div></div>
    <div class="kpi">@include('gestao._spark', ['serie' => $serieBilhetes])<div class="t">Bilhetes vendidos hoje</div><div class="v">{{ $bilhetesHoje }}</div>
        <div class="d {{ $variacaoBilhetes === null ? 'neutral' : ($variacaoBilhetes < 0 ? 'neg' : '') }}">{{ $variacaoBilhetes === null ? 'Sem referência' : ($variacaoBilhetes >= 0 ? '+' : '') . $variacaoBilhetes . '% vs. média dos 6 dias anteriores' }}</div></div>
    <div class="kpi"><div class="t">Ocupação média (viagens de hoje)</div><div class="v">{{ $ocupacao }}%</div><div class="bar" style="margin-top:8px"><i style="width:{{ $ocupacao }}%"></i></div></div>
    <div class="kpi"><div class="t">Viagens por realizar hoje</div><div class="v">{{ $activas }}</div><div class="d neutral">viagens agendadas</div></div>
</div>
<div class="grid2" style="grid-template-columns:1.6fr 1fr;align-items:start">
    <div class="card"><h2 class="card-title">Receita dos últimos 30 dias</h2><div class="card-sub">Valor diário em MT</div>
        <div class="chart" data-linechart data-labels='@json($labels30)' data-values='@json($valores30)'></div></div>
    <div class="card"><h2 class="card-title">Rotas mais rentáveis</h2><div class="card-sub">Últimos 30 dias</div>
        @php $maxR = max(1, (int) ($porRota->first()->total ?? 1)); @endphp
        @forelse ($porRota as $r)<div class="bar-row"><div class="lab"><span>{{ $r->origem }} → {{ $r->destino }}</span><b>@mt($r->total)</b></div><div class="bar"><i style="width:{{ round($r->total / $maxR * 100) }}%"></i></div></div>
        @empty<div class="muted small">Sem vendas no período.</div>@endforelse</div>
</div>
<div class="card mt">
    <div class="row between mb"><h2>Últimas vendas</h2><a class="small" href="{{ route('gestao.vendas', array_filter(['transportadora' => $tid])) }}">Ver todas ›</a></div>
    <div class="tbl-scroll"><table class="tbl"><thead><tr><th>Quando</th><th>Passageiro</th><th>Rota</th><th class="right">Valor</th></tr></thead><tbody>
    @forelse ($ultimas as $u)<tr><td>{{ \Carbon\Carbon::parse($u->data_pagamento)->format('d/m H:i') }}</td><td>{{ $u->nome_passageiro }}</td><td>{{ $u->origem }} → {{ $u->destino }}</td><td class="right"><b>@mt($u->valor)</b></td></tr>
    @empty<tr><td colspan="4" class="center muted" style="padding:22px">Sem vendas.</td></tr>@endforelse
    </tbody></table></div>
</div>
@endsection
