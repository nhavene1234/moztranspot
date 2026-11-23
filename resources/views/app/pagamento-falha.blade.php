@extends('layouts.app')
@section('titulo', 'Pagamento falhou')
@section('semtab', '1')
@section('app')
@php $pendente = $reserva->estado === 'pendente' && $reserva->expira_em && $reserva->expira_em->isFuture(); @endphp
<div class="app-pad center" style="justify-content:center">
    <div class="big-icon err">@include('partials.icone', ['n' => 'x'])</div>
    <h1>Pagamento não concluído</h1>
    <p class="muted">{{ $falha && $falha->codigo_erro && str_contains($falha->codigo_erro, 'INVALID') ? 'O número indicado não é válido para o método escolhido.' : 'A transacção foi recusada ou não foi confirmada.' }}</p>
    @if ($falha)<p class="small muted">Código do erro: <span class="mono">{{ $falha->codigo_erro }}</span></p>@endif
    @if ($pendente)
        <div class="timer"><span>Os lugares continuam reservados</span><span class="mono" data-countdown="{{ max(0, (int) now()->diffInSeconds($reserva->expira_em, false)) }}"></span></div>
        <a class="btn btn-lg btn-block" style="background:var(--blue)" href="{{ route('app.pagamento', $reserva) }}">Tentar novamente</a>
    @else
        <div class="alert alert-warn">O tempo da reserva terminou e os lugares foram libertados.</div>
        <a class="btn btn-lg btn-block" style="background:var(--blue)" href="{{ route('app.lugares', $reserva->viagem) }}">Escolher lugares novamente</a>
    @endif
    <a class="btn btn-outline btn-block mt" href="{{ route('app.inicio') }}">Voltar ao início</a>
</div>
@endsection
