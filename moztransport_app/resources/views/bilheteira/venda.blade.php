@extends('layouts.painel')
@section('titulo', 'Emitir bilhete')
@section('seccao', 'Bilheteira')
@section('painel')
@php $total = $reservas->sum(fn ($r) => $r->viagem->preco); $r0 = $reservas->first(); @endphp
@if (session('sucesso'))<div class="alert alert-ok"><strong>Venda registada com sucesso!</strong>O bilhete foi emitido e está pronto para impressão ou envio por SMS.</div>@endif
<div style="max-width:430px;margin:0 auto">
    @foreach ($reservas as $r)
        <div class="card" style="margin-bottom:14px">
            <div class="center">
                <div class="row" style="justify-content:center"><b style="font-size:17px">Moz<span style="color:var(--blue)">Transport</span></b></div>
                <div class="small muted mb">Comprovativo de viagem oficial</div>
                <div style="display:inline-block;padding:10px;border:1px solid var(--line);border-radius:12px"><div data-qr="{{ $r->bilhete->codigo_qr }}" data-size="150"></div></div>
                <div class="mono small" style="color:var(--accent-d);margin-top:6px">{{ $r->bilhete->codigo_qr }}</div>
            </div>
            <hr style="border:0;border-top:1px dashed var(--line);margin:14px 0">
            @foreach ([['Transportadora', $r->viagem->rota->transportadora->nome], ['Rota', $r->viagem->rota->nome], ['Data de partida', $r->viagem->data->format('d/m/Y')], ['Hora de embarque', $r->viagem->hora], ['Lugar / Assento', 'Lugar ' . $r->assento->rotulo], ['Passageiro', $r->nome_passageiro], ['Método', \App\Support\Fmt::metodoRotulo($r->pagamento?->metodo)]] as [$k, $v])
                <div class="row between small" style="margin-bottom:6px"><span class="muted">{{ $k }}</span><b>{{ $v }}</b></div>
            @endforeach
            <div class="row between mt"><b>Preço</b><b style="font-size:20px">@mt($r->viagem->preco)</b></div>
        </div>
    @endforeach
    @if ($reservas->count() > 1)<div class="row between mb"><b>Total da venda</b><b style="font-size:20px">@mt($total)</b></div>@endif
    <div class="row no-print">
        <button class="btn btn-outline grow" data-print>Imprimir</button>
    </div>
    <form class="row mt no-print" method="post" action="{{ route('bilheteira.sms', $r0->grupo) }}">@csrf
        <input class="input" name="telefone" value="{{ $r0->telefone }}" required>
        <button class="btn">Enviar por SMS</button>
    </form>
    <a class="btn btn-soft btn-block mt no-print" href="{{ route('bilheteira.vender') }}">Nova venda</a>
</div>
@endsection
