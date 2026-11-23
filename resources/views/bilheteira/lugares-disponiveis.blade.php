@extends('layouts.painel')
@section('titulo', 'Lugares disponíveis')
@section('seccao', 'Bilheteira')
@section('painel')
<form method="get" class="card mb row wrap">
    <div class="field grow" style="margin:0"><label>Viagem (hoje e amanhã)</label>
        <select class="input" name="viagem" data-autosubmit>
            @foreach ($viagens as $v)<option value="{{ $v->id }}" {{ $viagem && $viagem->id === $v->id ? 'selected' : '' }}>{{ $v->data->format('d/m') }} · {{ $v->hora }} · {{ $v->rota->nome }} · {{ $v->rota->transportadora->nome }}</option>@endforeach
        </select></div>
</form>
@if ($viagem)
<div class="card">
    <div class="row between wrap">
        <div><b style="font-size:16px">{{ $viagem->rota->transportadora->nome }} · {{ $viagem->autocarro->matricula }}</b><div class="muted small">{{ $viagem->rota->nome }} · {{ $viagem->data->translatedFormat('d M') }} às {{ $viagem->hora }}</div></div>
        <div class="row"><span class="pill err" id="live-info">{{ count($ocupados) }} ocupados · {{ $viagem->autocarro->capacidade_total - count($ocupados) }} livres</span><span class="pill ok">● Ao vivo</span></div>
    </div>
    <h3 class="center mt2 mb">Ocupação em tempo real</h3>
    @include('partials.assentos', ['modo' => 'ver'])
</div>
@else
<div class="card center muted">Sem viagens disponíveis.</div>
@endif
@endsection
