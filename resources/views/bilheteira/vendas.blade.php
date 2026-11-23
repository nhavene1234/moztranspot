@extends('layouts.painel')
@section('titulo', 'Vendas do dia')
@section('seccao', 'Bilheteira')
@section('painel')
<div class="grid3 mb">
    <div class="kpi"><div class="t">Total vendido hoje</div><div class="v">@mt($total)</div><div class="d {{ $variacao === null ? 'neutral' : ($variacao < 0 ? 'neg' : '') }}">{{ $variacao === null ? 'Sem dados de ontem' : ($variacao >= 0 ? '+' : '') . $variacao . '% comparado a ontem' }}</div></div>
    <div class="kpi"><div class="t">Nº de bilhetes emitidos</div><div class="v">{{ $quantidade }} bilhetes</div><div class="d neutral">Média de {{ $quantidade ? number_format($total / $quantidade, 0, ',', '.') : 0 }} MT por bilhete</div></div>
    <div class="kpi"><div class="t">Método mais usado</div><div class="v">{{ $topo ? \App\Support\Fmt::metodoRotulo($topo->metodo) . ' (' . $topo->n . ')' : '—' }}</div><div class="d neutral">{{ $topo ? 'Corresponde a ' . round($topo->n / $totalMetodos * 100) . '% das vendas' : '' }}</div></div>
</div>
<div class="card">
    <div class="row between mb"><div><h2>Vendas de hoje</h2><div class="muted small">Transações e bilhetes emitidos hoje, {{ now()->format('d/m/Y') }}.</div></div>
        <a class="btn btn-outline btn-sm" href="{{ route('bilheteira.vendas.exportar') }}">⬇ Exportar CSV</a></div>
    <div class="tbl-scroll"><table class="tbl">
        <thead><tr><th>Hora</th><th>Passageiro</th><th>Rota</th><th>Assento</th><th>Valor (MT)</th><th>Método</th></tr></thead>
        <tbody>
        @forelse ($vendas as $p)
            <tr><td>{{ $p->data_pagamento->format('H:i') }}</td><td><b>{{ $p->reserva->nome_passageiro }}</b></td><td>{{ $p->reserva->viagem->rota->nome }}</td><td>Lugar {{ $p->reserva->assento->rotulo }}</td><td>@mt($p->valor)</td><td><span class="pill {{ $p->metodo === 'numerario' ? '' : 'ok' }}">{{ $p->rotuloMetodo() }}</span></td></tr>
        @empty
            <tr><td colspan="6" class="center muted" style="padding:26px">Ainda não há vendas hoje.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $vendas->links() }}
</div>
@endsection
