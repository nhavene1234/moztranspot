@extends('layouts.app')
@section('titulo', 'Pagamento')
@section('semtab', '1')
@section('app')
@php $v = $reserva->viagem; @endphp
<div class="app-head"><div class="top"><a class="back" href="{{ route('app.lugares', $v) }}">@include('partials.icone', ['n' => 'back'])</a><div><h1>Pagamento</h1><div class="sub">Confirme a viagem e pague</div></div></div></div>
<div class="app-body">
    <div class="timer"><span>Lugar reservado</span><span class="mono" data-countdown="{{ max(0, now()->diffInSeconds($reserva->expira_em, false)) }}" data-onend="reload"></span></div>
    <div class="app-card">
        <b>{{ $v->rota->nome }}</b>
        <div class="small muted">{{ $v->rota->transportadora->nome }} · {{ $v->data->translatedFormat('D, d M Y') }} às {{ $v->hora }}</div>
        <div class="row between mt"><span class="muted">Lugar</span><b>{{ $reserva->assento->rotulo }}</b></div>
        <div class="row between"><span class="muted">Passageiro</span><b>{{ $reserva->nome_passageiro }}</b></div>
        <div class="row between mt"><span class="muted">Total a pagar</span><b style="font-size:20px;color:var(--blue-d)">@mt($v->preco)</b></div>
    </div>
    <form method="post" action="{{ route('app.pagar', $reserva) }}" data-processing>@csrf
        @if ($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif
        <h3 class="mb">Método de pagamento</h3>
        @foreach (['mpesa' => ['M-Pesa', 'Vodacom · números 84/85'], 'emola' => ['e-Mola', 'Movitel · números 86/87'], 'cartao' => ['Cartão', 'Visa / Mastercard']] as $k => [$nome, $sub])
            <label class="pay-opt"><span class="ic">{{ strtoupper(substr($nome, 0, 2)) }}</span><div><b>{{ $nome }}</b><div class="small muted">{{ $sub }}</div></div><input type="radio" name="metodo" value="{{ $k }}" {{ old('metodo', 'mpesa') === $k ? 'checked' : '' }}></label>
        @endforeach
        <div class="field mt"><label>Telefone para pagamento</label><input class="input" name="telefone" value="{{ old('telefone', preg_replace('/^\+258\s*/', '', auth()->user()->telefone ?? '')) }}" placeholder="84 123 4567"><div class="hint">Vai receber o pedido de confirmação no telemóvel.</div></div>
        <button class="btn btn-lg btn-block" style="background:var(--blue)">Pagar @mt($v->preco)</button>
    </form>
</div>
<div class="overlay" id="processando"><div class="spinner"></div><h2>A processar o pagamento…</h2><p class="muted">Confirme o pedido no seu telemóvel.</p></div>
@endsection
