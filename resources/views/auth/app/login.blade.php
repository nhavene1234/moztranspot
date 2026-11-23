@extends('layouts.app')
@section('titulo', 'Entrar')
@section('semtab', '1')
@section('app')
<div class="login-hero" style="background-image:linear-gradient(180deg,rgba(7,30,58,.55) 0%,rgba(7,30,58,.05) 38%,rgba(7,30,58,.88) 100%),url('{{ asset('img/autocarro.jpg') }}')" role="img" aria-label="Autocarro de passageiros em viagem">
    <a class="brand" href="#">@include('partials.logo') <span class="brand-nome">{{ config('moz.nome') }}</span></a>
    <div class="login-hero-txt">
        <h1>Bem-vindo de volta</h1>
        <div class="sub">Compre o seu bilhete de autocarro sem filas.</div>
    </div>
</div>
<div class="app-pad">
    @if (session('bloqueio'))
        <div class="alert alert-err"><strong>Conta bloqueada temporariamente</strong>Tente novamente em <b class="mono" data-countdown="{{ session('bloqueio') }}"></b></div>
    @endif
    @if (session('estado'))<div class="alert alert-ok">{{ session('estado') }}</div>@endif
    @if ($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('entrar') }}">@csrf
        <div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
        <div class="field"><label>Palavra-passe</label>
            <div class="pass-wrap"><input class="input" type="password" name="password" required><button type="button" data-toggle-pass>Mostrar</button></div></div>
        <div class="row between mb"><label class="check"><input type="checkbox" name="lembrar" value="1"> Lembrar-me</label><a class="small" href="{{ route('recuperar.form') }}">Esqueci a palavra-passe</a></div>
        <button class="btn btn-lg btn-block" style="background:var(--blue)">Entrar</button>
    </form>
    <p class="center muted mt2">Ainda não tem conta? <a href="{{ route('conta.criar') }}"><b>Criar conta</b></a></p>
</div>
@endsection
