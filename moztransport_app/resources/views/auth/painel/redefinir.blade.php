@extends('layouts.base')
@section('titulo', 'Nova palavra-passe')
@section('conteudo')
<div class="center-screen"><div class="modal">
    <h2 class="center">Nova palavra-passe</h2>
    <div class="progress-steps"><span class="on"><b>✓</b> Email</span><i></i><span class="on"><b>✓</b> Código</span><i></i><span class="on"><b>3</b> Nova senha</span></div>
    <form method="post" action="{{ route('painel.recuperar.redefinir') }}">@csrf
        <div class="field"><label>Nova palavra-passe</label>
            <div class="pass-wrap"><input class="input" type="password" name="password" data-strength="#forca" required minlength="8" autofocus><button type="button" data-toggle-pass>Mostrar</button></div>
            <div class="strength" id="forca"><i></i><i></i><i></i><i></i></div><div class="hint">Mínimo de 8 caracteres, com letras e números.</div>
            @error('password')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Confirmar nova palavra-passe</label><input class="input" type="password" name="password_confirmation" required></div>
        @error('codigo')<div class="alert alert-err">{{ $message }}</div>@enderror
        <button class="btn btn-block">Guardar nova palavra-passe</button>
    </form>
</div></div>
@endsection
