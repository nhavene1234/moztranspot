@extends('layouts.painel')
@section('titulo', 'Visão geral')
@section('seccao', 'Painel Admin')
@section('painel')
<div class="page-head"><div><h1>Administração Geral</h1><p>Controlo consolidado de passageiros, volumes de transacção e saúde da rede nacional.</p></div></div>
<div class="grid4 mb">
    <div class="kpi"><div class="t">Total de utilizadores</div><div class="v">{{ number_format($totalUtilizadores, 0, ',', '.') }}</div><div class="d">+{{ $novosMes }} este mês</div></div>
    <div class="kpi"><div class="t">Transportadoras activas</div><div class="v">{{ $transportadoras->count() }}</div><div class="d neutral">{{ $transportadoras->pluck('nome')->implode(', ') }}</div></div>
    <div class="kpi"><div class="t">Transacções de hoje</div><div class="v">{{ $transaccoesHoje }}</div></div>
    <div class="kpi"><div class="t">Estado do sistema</div><div class="v" style="color:{{ $baseOk ? 'var(--accent)' : 'var(--err)' }}">{{ $baseOk ? 'Operacional' : 'Com falha' }}</div><div class="d neutral">Último backup: {{ $ultimoBackup ?: 'nunca' }}</div></div>
</div>
<div class="card"><h2 class="card-title">Actividade por destino</h2><div class="card-sub">Bilhetes confirmados em toda a rede</div>
    @php $mx = max(1, (int) ($porCidade->first()->n ?? 1)); @endphp
    <div class="grid2">@foreach ($porCidade as $c)<div class="bar-row"><div class="lab"><span>{{ $c->cidade }}</span><b class="mono">{{ number_format($c->n, 0, ',', '.') }}</b></div><div class="bar"><i style="width:{{ round($c->n / $mx * 100) }}%"></i></div></div>@endforeach</div></div>
@endsection
