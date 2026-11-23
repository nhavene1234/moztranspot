@extends('layouts.painel')
@section('titulo', 'As minhas viagens')
@section('seccao', 'Cobrador')
@section('painel')
<div class="page-head"><div><h1>Viagens de hoje</h1><p>{{ now()->translatedFormat('l, d \d\e F') }}</p></div></div>
<div class="grid3">
@forelse ($viagens as $v)
    <a class="card" href="{{ route('cobrador.viagem', $v) }}" style="color:inherit;text-decoration:none;display:block">
        <div class="row between"><b>{{ $v->rota->transportadora->nome }}</b><span class="pill {{ $v->estado === 'concluida' ? '' : 'accent' }}">{{ $v->estado === 'concluida' ? 'Terminada' : 'Por fazer' }}</span></div>
        <h2 style="margin:8px 0">{{ $v->rota->nome }}</h2>
        <div class="muted small">Partida às {{ $v->hora }} · {{ $v->autocarro->matricula }}</div>
        <div class="bar mt"><i style="width:{{ $v->confirmados_count ? round($v->validados_count / $v->confirmados_count * 100) : 0 }}%"></i></div>
        <div class="small muted mt">{{ $v->validados_count }} de {{ $v->confirmados_count }} passageiros validados</div>
    </a>
@empty
    <div class="card muted" style="grid-column:1/-1">Não tem viagens atribuídas para hoje.</div>
@endforelse
</div>
@endsection
