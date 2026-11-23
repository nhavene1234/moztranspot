@extends('layouts.app')
@section('titulo', 'Conta criada')
@section('semtab', '1')
@section('app')
<div class="app-pad center" style="justify-content:center">
    <div class="big-icon ok">@include('partials.icone', ['n' => 'check'])</div>
    <h1>Conta criada!</h1>
    <p class="muted">Bem-vindo, {{ $nome }}. Já pode pesquisar viagens e comprar o seu bilhete.</p>
    <a class="btn btn-lg btn-block mt" style="background:var(--blue)" href="{{ route('entrar') }}">Entrar</a>
</div>
@endsection
