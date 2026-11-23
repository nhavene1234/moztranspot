@extends('layouts.base')
@section('titulo', 'Código de verificação')
@section('conteudo')
<div class="center-screen"><div class="modal">
    <div class="center mb"><a class="brand" href="#" style="justify-content:center;color:var(--navy)">@include('partials.logo') <span>Moz<b>Transport</b></span></a></div>
    <h2 class="center">Recuperar Palavra-passe</h2>
    <div class="progress-steps"><span class="on"><b>✓</b> Email</span><i></i><span class="on"><b>2</b> Código</span><i></i><span><b>3</b> Nova senha</span></div>
    @if (session('estado'))<div class="alert alert-ok">{{ session('estado') }}</div>@endif
    <div class="alert alert-info">Enviámos um código de 6 dígitos para o email:<br><b class="mono">{{ $email }}</b>
        <div class="small">O código expira em <b class="mono" data-countdown="{{ $segundos }}"></b></div></div>
    @if ($demo)<div class="alert alert-warn small">Modo demonstração — o código é <b class="mono">{{ $demo }}</b> (em produção só chega por email).</div>@endif
    <form method="post" action="{{ route('painel.recuperar.verificar') }}">@csrf
        <div class="field"><label>Código de Verificação</label>
            <input class="input code-input" name="codigo" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus>
            @error('codigo')<div class="err">{{ $message }}</div>@enderror</div>
        <button class="btn btn-block">Validar Código e Continuar</button>
    </form>
    <form method="post" action="{{ route('painel.recuperar.reenviar') }}" class="mt center small muted">@csrf Não recebeu o código? <button class="link-btn">Reenviar</button></form>
    <a class="btn btn-outline btn-block mt" href="{{ route('painel.entrar') }}">Voltar ao Login</a>
</div></div>
@endsection
