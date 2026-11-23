@extends('layouts.base')
@section('titulo', 'Sem permissão')
@section('conteudo')
<div class="center-screen"><div class="modal center">
    <h2>Sem permissão</h2>
    <p class="muted">O seu perfil não tem acesso a esta página.</p>
    <a class="btn" href="{{ auth()->check() ? auth()->user()->rotaInicial() : url('/') }}">Voltar</a>
</div></div>
@endsection
