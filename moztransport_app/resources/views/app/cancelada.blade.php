@extends('layouts.app')
@section('titulo', 'Reserva cancelada')
@section('semtab', '1')
@section('app')
<div class="app-pad center" style="justify-content:center">
    <div class="big-icon wait">@include('partials.icone', ['n' => 'check'])</div>
    <h1>Reserva cancelada</h1>
    <p class="muted">O lugar foi libertado e o valor de <b>@mt($valor)</b> será reembolsado para o método de pagamento original.</p>
    <a class="btn btn-lg btn-block" style="background:var(--blue)" href="{{ route('app.inicio') }}">Voltar ao início</a>
</div>
@endsection
