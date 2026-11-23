@extends('layouts.app')
@section('titulo', 'Criar conta')
@section('semtab', '1')
@section('app')
<div class="app-head"><div class="top"><a class="back" href="{{ route('entrar') }}">@include('partials.icone', ['n' => 'back'])</a><div><h1>Criar conta</h1><div class="sub">Passageiro · leva menos de um minuto</div></div></div></div>
<div class="app-pad">
    @include('partials.alertas', ['semErros' => true])
    <form method="post" action="{{ route('conta.guardar') }}">@csrf
        <div class="field"><label>Nome completo *</label><input class="input @error('nome') is-invalid @enderror" name="nome" value="{{ old('nome') }}" required>@error('nome')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Telefone</label><input class="input" name="telefone" value="{{ old('telefone') }}" placeholder="+258 84 000 0000"></div>
        <div class="field"><label>Email *</label><input class="input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Nº do documento (BI / Passaporte)</label><input class="input" name="documento" value="{{ old('documento') }}"></div>
        <div class="field"><label>Palavra-passe *</label>
            <div class="pass-wrap"><input class="input @error('password') is-invalid @enderror" type="password" name="password" data-strength="#forca" required minlength="8"><button type="button" data-toggle-pass>Mostrar</button></div>
            <div class="strength" id="forca"><i></i><i></i><i></i><i></i></div><div class="hint">Mínimo de 8 caracteres, com letras e números.</div>
            @error('password')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Confirmar palavra-passe *</label><input class="input" type="password" name="password_confirmation" required></div>
        <label class="check mb"><input type="checkbox" name="termos" value="1" required> Aceito os termos de utilização e a política de privacidade.</label>
        <button class="btn btn-lg btn-block" style="background:var(--blue)">Criar conta</button>
    </form>
</div>
@endsection
