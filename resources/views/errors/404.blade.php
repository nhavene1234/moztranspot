@extends('layouts.base')
@section('titulo', 'Não encontrado')
@section('conteudo')
<div class="center-screen"><div class="modal center">
    <h2>Página não encontrada</h2>
    <p class="muted">O endereço não existe ou a viagem já não está disponível.</p>
    <a class="btn" href="{{ auth()->check() ? auth()->user()->rotaInicial() : url('/') }}">Voltar ao início</a>
</div></div>
@endsection
