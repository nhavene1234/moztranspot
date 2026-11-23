@extends('layouts.app')
@section('titulo', 'Novo lugar')
@section('semtab', '1')
@section('app')
<div class="app-head"><div class="top"><a class="back" href="{{ route('app.remarcar', $reserva) }}">@include('partials.icone', ['n' => 'back'])</a>
    <div><h1>Escolha o novo lugar</h1><div class="sub">{{ $viagem->data->translatedFormat('D, d M') }} às {{ $viagem->hora }}</div></div></div></div>
<form class="app-body" method="post" action="{{ route('app.remarcar.confirmar', [$reserva, $viagem]) }}">@csrf
    @if ($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif
    @include('partials.assentos', ['modo' => 'um'])
    <div class="app-card mt"><div class="row between"><span class="muted">Novo lugar</span><b id="sel-lista">—</b></div><span id="sel-total" class="hidden"></span></div>
    <button class="btn btn-lg btn-block disabled" id="sel-btn" style="background:var(--blue)" disabled>Confirmar remarcação</button>
</form>
@endsection
