@extends('layouts.app')
@section('titulo', 'Histórico')
@section('app')
<div class="app-head"><h1>Histórico de viagens</h1><div class="sub">Todas as suas compras</div></div>
<div class="app-body">
    @forelse ($porAno as $ano => $reservas)
        <h3 class="mb">{{ $ano }}</h3>
        <div class="app-card">
            @foreach ($reservas as $r)
                @php $ok = $r->estado === 'confirmada'; @endphp
                <div class="list-row" style="padding:8px 0;{{ ! $loop->last ? 'border-bottom:1px solid #eef2f7' : '' }}">
                    <span class="ic {{ $ok ? '' : ($r->estado === 'cancelada' ? 'err' : 'warn') }}">@include('partials.icone', ['n' => $ok ? 'check' : 'x'])</span>
                    <div class="grow"><b>{{ $r->viagem->rota->nome }}</b><div class="small muted">{{ $r->viagem->data->translatedFormat('d M Y') }} · {{ $r->viagem->rota->transportadora->nome ?? '' }}</div></div>
                    <div class="right"><b>@mt($r->viagem->preco)</b><div class="small muted">{{ $r->rotuloEstado() }}</div></div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="app-card center muted">Ainda não tem viagens.</div>
    @endforelse
</div>
@endsection
