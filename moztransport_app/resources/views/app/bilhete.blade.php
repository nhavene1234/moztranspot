@extends('layouts.app')
@section('titulo', 'Bilhete')
@section('app')
@php $v = $reserva->viagem; $b = $reserva->bilhete; $cancelado = $reserva->estado === 'cancelada'; @endphp
<div class="app-head"><div class="top"><a class="back" href="{{ route('app.bilhetes') }}">@include('partials.icone', ['n' => 'back'])</a><div><h1>O meu bilhete</h1><div class="sub">{{ $v->rota->transportadora->nome }}</div></div></div></div>
<div class="app-body">
    @include('partials.alertas')
    <div class="ticket {{ $cancelado ? 'cancelado' : '' }}">
        <div class="th"><b>{{ $v->rota->transportadora->nome }}</b>
            <span class="pill {{ $cancelado ? 'err' : ($b->estado === 'usado' ? '' : 'ok') }}">{{ $cancelado ? 'Cancelado' : ($b->estado === 'usado' ? 'Utilizado' : 'Válido') }}</span></div>
        <div class="route"><span>{{ $v->rota->origem }}</span><span class="muted">→</span><span>{{ $v->rota->destino }}</span></div>
        <div class="row between" style="padding:0 16px"><span class="small muted">{{ $v->hora }}</span><span class="small muted">{{ $v->hora_chegada }}</span></div>
        <div class="qr"><div class="box"><div data-qr="{{ $b->codigo_qr }}" data-size="170"></div></div><div class="mono small muted" style="margin-top:8px">{{ $b->codigo_qr }}</div></div>
        <div class="meta">
            <div><small>Passageiro</small><b>{{ $reserva->nome_passageiro }}</b></div>
            <div><small>Lugar</small><b>{{ $reserva->assento->rotulo }}</b></div>
            <div><small>Data</small><b>{{ $v->data->translatedFormat('D, d M Y') }}</b></div>
            <div><small>Embarque</small><b>{{ $v->hora }}</b></div>
            <div><small>Classe</small><b>{{ $v->classe }}</b></div>
            <div><small>Total pago</small><b>@mt($v->preco)</b></div>
        </div>
    </div>
    @if (! $cancelado)
        <div class="row mt no-print">
            <button class="btn btn-outline grow" data-share="{{ $b->codigo_qr }}">Partilhar</button>
            <button class="btn btn-outline grow" data-print>Imprimir</button>
        </div>
        @if ($b->estado === 'valido')
            @if ($podeAlterar)
                <div class="row mt no-print">
                    <a class="btn btn-soft grow" href="{{ route('app.remarcar', $reserva) }}">Remarcar</a>
                    <form class="grow" method="post" action="{{ route('app.cancelar', $reserva) }}" data-confirm="Cancelar esta reserva? O valor será reembolsado.">@csrf<button class="btn btn-danger-outline btn-block">Cancelar</button></form>
                </div>
            @else
                <p class="small muted center mt">Já não é possível remarcar ou cancelar (prazo: {{ $horasLimite }} horas antes da partida).</p>
            @endif
        @endif
    @endif
</div>
@endsection
