@extends('layouts.app')
@section('titulo', 'Pagamento confirmado')
@section('semtab', '1')
@section('app')
@php $v = $reserva->viagem; @endphp
<div class="app-pad center" style="justify-content:center">
    <div class="big-icon ok">@include('partials.icone', ['n' => 'check'])</div>
    <h1>Pagamento confirmado!</h1>
    <p class="muted">{{ $compra->count() > 1 ? 'Os seus '.$compra->count().' bilhetes foram emitidos. Cada passageiro apresenta o seu QR code ao embarcar.' : 'O seu bilhete foi emitido. Apresente o QR code ao embarcar.' }}</p>
    <div class="app-card" style="text-align:left;background:#f8fafc">
        <b>{{ $v->rota->nome }}</b><div class="small muted">{{ $v->data->translatedFormat('D, d M Y') }} às {{ $v->hora }} · matrícula {{ $v->autocarro->matricula }}</div>
        @foreach ($compra as $c)
            <a class="row between mt small" href="{{ route('app.bilhete', $c) }}" style="color:inherit;text-decoration:none;border-top:1px solid var(--line);padding-top:8px">
                <span><b>Lugar {{ $c->assento->rotulo }}</b> · {{ $c->nome_passageiro }}<br><span class="mono muted">{{ $c->bilhete->codigo_qr }}</span></span><span class="pill ok">Ver bilhete</span></a>
        @endforeach
        <div class="row between mt small"><span class="muted">Total pago</span><b>@mt($total)</b></div>
    </div>
    <a class="btn btn-lg btn-block" style="background:var(--blue)" href="{{ route('app.bilhete', $compra->first()) }}">{{ $compra->count() > 1 ? 'Ver o primeiro bilhete' : 'Ver bilhete' }}</a>
    <a class="btn btn-outline btn-block mt" href="{{ route('app.inicio') }}">Voltar ao início</a>
</div>
@endsection
