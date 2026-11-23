@extends('layouts.app')
@section('titulo', 'Pagamento confirmado')
@section('semtab', '1')
@section('app')
@php $v = $reserva->viagem; @endphp
<div class="app-pad center" style="justify-content:center">
    <div class="big-icon ok">@include('partials.icone', ['n' => 'check'])</div>
    <h1>Pagamento confirmado!</h1>
    <p class="muted">O seu bilhete foi emitido. Apresente o QR code ao embarcar.</p>
    <div class="app-card" style="text-align:left;background:#f8fafc">
        <b>{{ $v->rota->nome }}</b><div class="small muted">{{ $v->data->translatedFormat('D, d M Y') }} às {{ $v->hora }} · lugar {{ $reserva->assento->rotulo }}</div>
        <div class="row between mt small"><span class="muted">Código</span><b class="mono">{{ $reserva->bilhete->codigo_qr }}</b></div>
        <div class="row between small"><span class="muted">Total pago</span><b>@mt($reserva->pagamento->valor)</b></div>
    </div>
    <a class="btn btn-lg btn-block" style="background:var(--blue)" href="{{ route('app.bilhete', $reserva) }}">Ver bilhete</a>
    <a class="btn btn-outline btn-block mt" href="{{ route('app.inicio') }}">Voltar ao início</a>
</div>
@endsection
