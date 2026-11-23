@extends('layouts.base')
@section('titulo', 'Aceder ao painel')
@section('conteudo')
<div class="split">
    <div class="hero" style="background-image:linear-gradient(160deg,rgba(7,30,58,.82),rgba(12,58,107,.55)),url('{{ asset('img/autocarro.jpg') }}')">
        <a class="brand" href="#" style="padding:0">@include('partials.logo') <span class="brand-nome">{{ config('moz.nome') }}</span></a>
        <div>
            <h2>Viaje sem filas.<br>Venda com controlo.</h2>
            <p>Sistema de bilheteira e gestão de frotas de Moçambique.</p>
        </div>
        <span></span>
    </div>
    <div class="form"><div class="box">
        <h1>Aceder ao Painel</h1>
        <p class="muted">Introduza as suas credenciais para gerir vendas e rotas.</p>

        @if (session('bloqueio'))
            <div class="alert alert-err"><strong>Conta bloqueada temporariamente</strong>
                Excedeu as {{ session('bloqueio_max') }} tentativas falhadas. Tente novamente em: <b class="mono" data-countdown="{{ session('bloqueio') }}"></b>
            </div>
        @endif
        @if (session('estado'))<div class="alert alert-ok">{{ session('estado') }}</div>@endif
        @if ($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif

        <form method="post" action="{{ route('painel.entrar') }}">@csrf
            <div class="field"><label>Endereço de Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
            <div class="field"><label>Palavra-passe</label>
                <div class="pass-wrap"><input class="input" type="password" name="password" required><button type="button" data-toggle-pass>Mostrar</button></div></div>
            <div class="row between mb">
                <label class="check"><input type="checkbox" name="lembrar" value="1"> Lembrar sessão</label>
                <a href="{{ route('painel.recuperar.form') }}" class="small">Esqueci a palavra-passe</a>
            </div>
            <button class="btn btn-lg btn-block">Entrar no Painel</button>
        </form>

        <p class="small muted" style="margin-top:22px">© {{ date('Y') }} {{ config('moz.nome') }}.</p>
    </div></div>
</div>
@endsection
