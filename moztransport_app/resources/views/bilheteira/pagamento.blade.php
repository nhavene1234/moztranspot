@extends('layouts.painel')
@section('titulo', 'Vender bilhete')
@section('seccao', 'Bilheteira')
@section('painel')
@include('bilheteira._passos', ['passo' => 3])
@php $total = $reservas->sum(fn ($r) => $r->viagem->preco); $expira = $reservas->min('expira_em'); @endphp
<div class="timer" style="max-width:520px"><span>Lugares bloqueados para esta venda</span><span class="mono" data-countdown="{{ max(0, now()->diffInSeconds($expira, false)) }}" data-onend="reload"></span></div>
<form method="post" action="{{ route('bilheteira.registar') }}" class="grid2" style="grid-template-columns:1.4fr 1fr;align-items:start" data-processing>@csrf
    <div class="card">
        <h2 class="mb">Dados do passageiro e pagamento</h2>
        @if (session('erro'))<div class="alert alert-err">{{ session('erro') }}</div>@endif
        <div class="field"><label>Nome completo *</label><input class="input" name="nome" value="{{ old('nome') }}" required>@error('nome')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="grid2">
            <div class="field"><label>Tipo de documento *</label><select class="input" name="tipo_documento">@foreach (['BI', 'Passaporte', 'Carta de condução', 'DIRE'] as $t)<option {{ old('tipo_documento') === $t ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></div>
            <div class="field"><label>Nº do documento *</label><input class="input" name="documento" value="{{ old('documento') }}" required>@error('documento')<div class="err">{{ $message }}</div>@enderror</div>
        </div>
        <div class="field"><label>Telefone do passageiro *</label><input class="input" name="telefone" value="{{ old('telefone') }}" placeholder="+258 84 000 0000" required>@error('telefone')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Método de pagamento</label>
            <div class="segment">
                @foreach (['numerario' => 'Numerário', 'mpesa' => 'M-Pesa', 'emola' => 'e-Mola', 'cartao' => 'Cartão'] as $k => $t)
                    <label><input type="radio" name="metodo" value="{{ $k }}" {{ old('metodo', 'numerario') === $k ? 'checked' : '' }}>{{ $t }}</label>
                @endforeach
            </div></div>
        <div class="field"><label>Número do cliente (só M-Pesa / e-Mola)</label><input class="input" name="telefone_pagamento" value="{{ old('telefone_pagamento') }}" placeholder="84 123 4567">@error('telefone_pagamento')<div class="err">{{ $message }}</div>@enderror
            <div class="hint">Simulação: 84/85 para M-Pesa, 86/87 para e-Mola; números a acabar em 00 são recusados.</div></div>
    </div>
    <div class="card">
        <div class="small" style="color:var(--accent-d);font-weight:700;text-transform:uppercase;letter-spacing:.06em">Confirmação final</div>
        <h2 style="margin:6px 0 12px">{{ $viagem->rota->nome }}</h2>
        <div class="muted small">{{ $viagem->rota->transportadora->nome }} · {{ $viagem->data->translatedFormat('d M') }} às {{ $viagem->hora }}</div>
        <hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
        <div class="row between"><span class="muted">Lugares</span><b>{{ $reservas->map(fn ($r) => $r->assento->rotulo)->implode(', ') }}</b></div>
        <div class="row between mt"><span class="muted">Bilhetes</span><b>{{ $reservas->count() }} × @mt($viagem->preco)</b></div>
        <div class="row between mt"><span class="muted">Valor total</span><b style="font-size:26px">@mt($total)</b></div>
        <button class="btn btn-lg btn-block mt2">Registar venda</button>
        <a class="btn btn-ghost btn-block mt" href="{{ route('bilheteira.lugares', $viagem) }}">‹ Alterar lugares</a>
    </div>
</form>
<div class="overlay" id="processando"><div class="spinner"></div><h2>A registar a venda…</h2></div>
@endsection
