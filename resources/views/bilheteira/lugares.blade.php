@extends('layouts.painel')
@section('titulo', 'Vender bilhete')
@section('seccao', 'Bilheteira')
@section('painel')
@include('bilheteira._passos', ['passo' => 2])
<form method="post" action="{{ route('bilheteira.bloquear', $viagem) }}" class="grid2" style="grid-template-columns:1.4fr 1fr;align-items:start">@csrf
    <div class="card">
        <h2 class="center">Seleccione os lugares no mapa</h2>
        <p class="center muted small mb">Pode escolher até 5 lugares (bilhete duplo/grupo).</p>
        @error('assento_ids')<div class="alert alert-err">{{ $message }}</div>@enderror
        @include('partials.assentos', ['modo' => 'varios'])
    </div>
    <div class="card">
        <div class="small" style="color:var(--accent-d);font-weight:700;text-transform:uppercase;letter-spacing:.06em">Resumo da viagem</div>
        <h2 style="margin:6px 0 12px">{{ $viagem->rota->nome }}</h2>
        <div class="muted small">{{ $viagem->rota->transportadora->nome }} · {{ $viagem->classe }}</div>
        <div class="muted small">{{ $viagem->data->translatedFormat('D, d M Y') }} às {{ $viagem->hora }}</div>
        <hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
        <div class="row between"><span class="muted">Lugares seleccionados</span><b id="sel-lista">—</b></div>
        <div class="row between mt"><span class="muted">Preço unitário</span><b>@mt($viagem->preco)</b></div>
        <div class="row between mt"><span class="muted">Total</span><b id="sel-total" style="font-size:24px;color:var(--accent-d)">0 MT</b></div>
        <button class="btn btn-lg btn-block mt2 disabled" id="sel-btn" disabled>Continuar para passo 3</button>
        <a class="btn btn-ghost btn-block mt" href="{{ route('bilheteira.vender') }}">‹ Voltar às viagens</a>
    </div>
</form>
@endsection
