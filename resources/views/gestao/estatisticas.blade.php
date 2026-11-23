@extends('layouts.painel')
@section('titulo', 'Estatísticas')
@section('seccao', 'Gestão')
@section('painel')
<div class="page-head"><div><h1>Estatísticas de procura{{ $empresa ? ' · ' . $empresa->nome : '' }}</h1><p>Baseadas apenas em bilhetes efectivamente vendidos.</p></div>@include('gestao._empresa')</div>
<div class="grid2" style="align-items:start">
    <div class="card"><h2 class="card-title">Rotas mais procuradas</h2><div class="card-sub">Últimos 90 dias</div>
        @php $maxN = max(1, (int) ($rotas->first()->n ?? 1)); @endphp
        @forelse ($rotas as $r)<div class="bar-row"><div class="lab"><span>{{ $r->origem }} → {{ $r->destino }}</span><b>{{ $r->n }} <span class="muted small">({{ round($r->n / $totalRotas * 100) }}%)</span></b></div><div class="bar"><i style="width:{{ round($r->n / $maxN * 100) }}%"></i></div></div>
        @empty<div class="muted small">Sem dados.</div>@endforelse</div>
    <div class="card"><h2 class="card-title">Horários de maior procura</h2><div class="card-sub">Bilhetes por dia da semana e hora de partida (90 dias)</div>
        <div class="heat">
            <span></span>@foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $d)<span class="center">{{ $d }}</span>@endforeach
            @foreach ($faixas as $h => $nome)
                <span>{{ $nome }}</span>
                @for ($d = 1; $d <= 7; $d++)@php $n = $mapa[$h][$d]; @endphp<span class="c" title="{{ $n }} bilhetes" style="opacity:{{ $n ? max(.12, round($n / $maxCalor, 2)) : .05 }}"></span>@endfor
            @endforeach
        </div></div>
</div>
<div class="card mt"><h2 class="card-title">Sazonalidade</h2><div class="card-sub">Bilhetes vendidos por mês (últimos 12 meses){{ $picoMes && max($mensal) > 0 ? ' · pico em ' . \Carbon\Carbon::createFromFormat('Y-m', $picoMes)->translatedFormat('F Y') : '' }}</div>
    @php $mx = max(1, max($mensal)); @endphp
    <div class="months">@foreach ($mensal as $ym => $n)<div class="m {{ $ym === $picoMes && $n > 0 ? 'pico' : '' }}"><span>{{ $n }}</span><i style="height:{{ round($n / $mx * 130) }}px"></i>{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M') }}</div>@endforeach</div></div>
@endsection
