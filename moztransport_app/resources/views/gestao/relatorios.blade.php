@extends('layouts.painel')
@section('titulo', 'Relatórios')
@section('seccao', 'Gestão')
@section('painel')
<div class="page-head"><div><h1>Relatório financeiro{{ $empresa ? ' · ' . $empresa->nome : '' }}</h1><p>{{ $de->format('d/m/Y') }} a {{ $ate->format('d/m/Y') }}</p></div>@include('gestao._empresa')</div>
<form class="card mb no-print" method="get">
    @if ($tid)<input type="hidden" name="transportadora" value="{{ $tid }}">@endif
    <div class="filters">
        <div class="field"><label>De</label><input class="input" type="date" name="de" value="{{ $de->toDateString() }}"></div>
        <div class="field"><label>Até</label><input class="input" type="date" name="ate" value="{{ $ate->toDateString() }}"></div>
        <button class="btn">Gerar relatório</button>
        <a class="btn btn-outline" href="{{ route('gestao.relatorios.exportar', request()->query()) }}">⬇ Exportar CSV</a>
        <button type="button" class="btn btn-outline" data-print>Imprimir / PDF</button>
    </div>
</form>
<div class="grid3 mb">
    <div class="kpi"><div class="t">Receita total</div><div class="v">@mt($receita)</div></div>
    <div class="kpi"><div class="t">Bilhetes vendidos</div><div class="v">{{ number_format($bilhetes, 0, ',', '.') }}</div></div>
    <div class="kpi"><div class="t">Média diária</div><div class="v">@mt($mediaDiaria)</div><div class="d neutral">em {{ $dias }} dia(s)</div></div>
</div>
<div class="card"><h2 class="card-title">Receita por dia</h2><div class="card-sub">MT</div><div class="chart" data-linechart data-labels='@json($labels)' data-values='@json($valores)'></div></div>
<div class="card"><h2 class="mb">Desempenho por rota</h2>
    <div class="tbl-scroll"><table class="tbl"><thead><tr><th>Rota</th><th class="right">Viagens</th><th class="right">Bilhetes</th><th class="right">Receita</th></tr></thead><tbody>
    @forelse ($porRota as $r)<tr><td><b>{{ $r->origem }} → {{ $r->destino }}</b></td><td class="right">{{ $r->viagens }}</td><td class="right">{{ $r->bilhetes }}</td><td class="right"><b>@mt($r->total)</b></td></tr>
    @empty<tr><td colspan="4" class="center muted" style="padding:22px">Sem vendas no período.</td></tr>@endforelse
    </tbody></table></div></div>
@if ($imprimir)<script>window.addEventListener('load', function () { window.print(); });</script>@endif
@endsection
