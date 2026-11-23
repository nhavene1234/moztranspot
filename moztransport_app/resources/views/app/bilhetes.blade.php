@extends('layouts.app')
@section('titulo', 'Os meus bilhetes')
@section('app')
<div class="app-head"><h1>Os meus bilhetes</h1><div class="sub">{{ $proximas->count() }} próxima(s) viagem(ns)</div></div>
<div class="app-body">
    <div class="tabs2"><a class="{{ $tab !== 'passadas' ? 'on' : '' }}" href="{{ route('app.bilhetes') }}">Próximas</a><a class="{{ $tab === 'passadas' ? 'on' : '' }}" href="{{ route('app.bilhetes', ['tab' => 'passadas']) }}">Passadas</a></div>
    @php $lista = $tab === 'passadas' ? $passadas : $proximas; @endphp
    @forelse ($lista as $r)
        <a class="app-card trip" href="{{ route('app.bilhete', $r) }}" style="display:block;color:inherit;text-decoration:none">
            <div class="row between"><span class="co">{{ $r->viagem->rota->transportadora->nome }}</span><span class="pill {{ $r->bilhete?->estado === 'usado' ? '' : 'ok' }}">{{ $r->bilhete?->estado === 'usado' ? 'Utilizado' : 'Válido' }}</span></div>
            <b style="font-size:17px">{{ $r->viagem->rota->nome }}</b>
            <div class="small muted">{{ $r->viagem->data->translatedFormat('D, d M') }} às {{ $r->viagem->hora }} · lugar {{ $r->assento->rotulo }}</div>
        </a>
    @empty
        <div class="app-card center"><b>{{ $tab === 'passadas' ? 'Ainda sem viagens passadas.' : 'Sem viagens marcadas.' }}</b>
            <p class="muted small">Pesquise uma viagem e compre o seu bilhete.</p><a class="btn" style="background:var(--blue)" href="{{ route('app.inicio') }}">Pesquisar viagens</a></div>
    @endforelse
</div>
@endsection
