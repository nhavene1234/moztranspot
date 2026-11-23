@extends('layouts.app')
@section('titulo', 'Viagens')
@section('app')
@php $dataTxt = \Carbon\Carbon::parse($q['data'])->translatedFormat('D, d M'); @endphp
<div class="app-head"><div class="top"><a class="back" href="{{ route('app.inicio') }}">@include('partials.icone', ['n' => 'back'])</a>
    <div><h1>{{ $q['origem'] }} → {{ $q['destino'] }}</h1><div class="sub">{{ $dataTxt }} · {{ $viagens->count() }} {{ $viagens->count() === 1 ? 'viagem' : 'viagens' }}</div></div></div></div>
<div class="app-body">
    <div class="chips">
        @foreach (['hora' => 'Mais cedo', 'preco' => 'Mais barato', 'empresa' => 'Transportadora'] as $k => $t)
            <a class="{{ $ordem === $k ? 'on' : '' }}" href="{{ route('app.viagens', array_merge($q, ['ordem' => $k])) }}">{{ $t }}</a>
        @endforeach
    </div>
    @forelse ($viagens as $v)
        @php $livres = $v->autocarro->capacidade_total - $v->ocupados_count; @endphp
        <div class="app-card trip {{ $livres < 1 ? 'full' : '' }}">
            <div class="row between"><span class="co">{{ $v->rota->transportadora->nome }}</span><span class="pill {{ $livres < 1 ? 'err' : ($livres <= 8 ? 'warn' : 'ok') }}">{{ $livres < 1 ? 'Esgotado' : $livres . ' livres' }}</span></div>
            <div class="times"><span class="t">{{ $v->hora }}</span><span class="line">{{ $v->rota->duracao_texto }}</span><span class="t">{{ $v->hora_chegada }}</span></div>
            <div class="row between mt"><span class="small muted">{{ $v->classe }}</span><span class="price">@mt($v->preco)</span></div>
            @if ($livres > 0)<a class="btn btn-block mt" style="background:var(--blue)" href="{{ route('app.lugares', $v) }}">Escolher lugar</a>@endif
        </div>
    @empty
        <div class="app-card center"><b>Sem viagens para esta data.</b><p class="muted small">Experimente outro dia.</p>
            <a class="btn btn-outline" href="{{ route('app.viagens', array_merge($q, ['data' => \Carbon\Carbon::parse($q['data'])->addDay()->toDateString()])) }}">Ver o dia seguinte</a></div>
    @endforelse
</div>
@endsection
