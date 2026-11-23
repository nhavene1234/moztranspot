@extends('layouts.base')
@section('titulo', 'Aceder ao painel')
@section('conteudo')
<div class="split">
    <div class="hero">
        <a class="brand" href="#" style="padding:0">@include('partials.logo') <span>Moz<b>Transport</b></span></a>
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

        @if (config('moz.demo'))
        <div class="demo">
            <div class="small muted mb" style="text-align:center">AMBIENTE DE DEMONSTRAÇÃO — palavra-passe de todas as contas: <b>{{ config('moz.demo_senha') }}</b></div>
            @foreach ([['manuel.zoca@moztransport.co.mz', 'Bilheteiro', '#1ba3e0'], ['mikael.chongo@moztransport.co.mz', 'Cobrador', '#c2611a'], ['p.nhalicolo@moztransport.co.mz', 'Gestor', '#16a34a'], ['nelson.nhavene@moztransport.co.mz', 'Admin', '#6d4ae0']] as [$mail, $nome, $cor])
                <form method="post" action="{{ route('painel.entrar') }}" style="display:inline-block;width:48%;margin-bottom:6px">@csrf
                    <input type="hidden" name="email" value="{{ $mail }}"><input type="hidden" name="password" value="{{ config('moz.demo_senha') }}">
                    <button style="width:100%;color:{{ $cor }};border-color:{{ $cor }}">{{ $nome }}</button>
                </form>
            @endforeach
        </div>
        @endif
        <p class="small muted" style="margin-top:22px">© {{ date('Y') }} MozTransport · Sistema de Bilheteira Interprovincial.</p>
    </div></div>
</div>
@endsection
