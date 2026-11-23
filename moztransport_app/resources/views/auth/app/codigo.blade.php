@extends('layouts.app')
@section('titulo', 'Nova palavra-passe')
@section('semtab', '1')
@section('app')
<div class="app-head"><div class="top"><a class="back" href="{{ route('recuperar.form') }}">@include('partials.icone', ['n' => 'back'])</a><div><h1>Código e nova palavra-passe</h1><div class="sub">Passo 2 de 2</div></div></div></div>
<div class="app-pad">
    @if (session('estado'))<div class="alert alert-ok">{{ session('estado') }}</div>@endif
    <div class="alert alert-info">Código enviado para <b class="mono">{{ $email }}</b>. Expira em <b class="mono" data-countdown="{{ $segundos }}"></b>.</div>
    @if ($demo)<div class="alert alert-warn small">Modo demonstração — o código é <b class="mono">{{ $demo }}</b>.</div>@endif
    <form method="post" action="{{ route('recuperar.redefinir') }}">@csrf
        <div class="field"><label>Código de 6 dígitos</label><input class="input code-input" name="codigo" inputmode="numeric" maxlength="6" required autofocus>@error('codigo')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Nova palavra-passe</label>
            <div class="pass-wrap"><input class="input" type="password" name="password" data-strength="#forca" required minlength="8"><button type="button" data-toggle-pass>Mostrar</button></div>
            <div class="strength" id="forca"><i></i><i></i><i></i><i></i></div>@error('password')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Confirmar palavra-passe</label><input class="input" type="password" name="password_confirmation" required></div>
        <button class="btn btn-lg btn-block" style="background:var(--blue)">Guardar nova palavra-passe</button>
    </form>
    <form method="post" action="{{ route('recuperar.reenviar') }}" class="center small muted mt">@csrf Não recebeu? <button class="link-btn">Reenviar código</button></form>
</div>
@endsection
