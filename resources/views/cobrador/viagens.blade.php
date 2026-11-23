@extends('layouts.painel')
@section('titulo', 'As minhas viagens')
@section('seccao', 'Cobrador')
@section('painel')
@php $hoje = $dia->isToday(); @endphp
<div class="page-head"><div><h1>{{ $hoje ? 'Viagens de hoje' : 'Viagens de ' . $dia->format('d/m/Y') }}</h1><p>{{ $dia->translatedFormat('l, d \\d\\e F') }}</p></div>
    <form method="get" class="row wrap no-print">
        <a class="btn btn-outline btn-sm" href="{{ route('cobrador.viagens', ['data' => $dia->copy()->subDay()->toDateString()]) }}">‹ Dia anterior</a>
        <input class="input" type="date" name="data" value="{{ $dia->toDateString() }}" data-autosubmit style="width:auto">
        <a class="btn btn-outline btn-sm" href="{{ route('cobrador.viagens', ['data' => $dia->copy()->addDay()->toDateString()]) }}">Dia seguinte ›</a>
        @unless ($hoje)<a class="btn btn-soft btn-sm" href="{{ route('cobrador.viagens') }}">Hoje</a>@endunless
    </form></div>
@foreach ([['Atribuídas a mim', $minhas, 'Não tem viagens atribuídas neste dia.'], ['Outras viagens da transportadora', $outras, null]] as [$titulo, $lista, $vazio])
    @if ($lista->count() || $vazio)
        <h2 class="mb" style="margin-top:6px">{{ $titulo }}</h2>
        <div class="grid3" style="margin-bottom:18px">
        @forelse ($lista as $v)
            <a class="card" href="{{ route('cobrador.viagem', $v) }}" style="color:inherit;text-decoration:none;display:block">
                <div class="row between"><b>{{ $v->rota->transportadora->nome }}</b><span class="pill {{ $v->estado === 'concluida' ? '' : 'accent' }}">{{ $v->estado === 'concluida' ? 'Terminada' : 'Por fazer' }}</span></div>
                <h2 style="margin:8px 0">{{ $v->rota->nome }}</h2>
                <div class="muted small">Partida às {{ $v->hora }} · Matrícula <b class="mono">{{ $v->autocarro->matricula }}</b></div>
                @if ($v->motorista)<div class="muted small">Motorista: {{ $v->motorista->nome }}</div>@endif
                <div class="bar mt"><i style="width:{{ $v->confirmados_count ? round($v->validados_count / $v->confirmados_count * 100) : 0 }}%"></i></div>
                <div class="small muted mt">{{ $v->validados_count }} de {{ $v->confirmados_count }} passageiros validados</div>
            </a>
        @empty
            <div class="card muted" style="grid-column:1/-1">{{ $vazio }}</div>
        @endforelse
        </div>
    @endif
@endforeach
@if (! $minhas->count() && ! $outras->count())
    <div class="card muted">Não há viagens da sua transportadora neste dia. Use as setas para ver outros dias.</div>
@endif
@endsection
