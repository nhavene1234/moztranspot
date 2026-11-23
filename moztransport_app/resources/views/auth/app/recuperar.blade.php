@extends('layouts.app')
@section('titulo', 'Recuperar palavra-passe')
@section('semtab', '1')
@section('app')
<div class="app-head"><div class="top"><a class="back" href="{{ route('entrar') }}">@include('partials.icone', ['n' => 'back'])</a><div><h1>Recuperar palavra-passe</h1><div class="sub">Passo 1 de 2 · email</div></div></div></div>
<div class="app-pad">
    @if ($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif
    <p class="muted">Indique o email da sua conta. Enviamos um código de 6 dígitos, válido durante 5 minutos.</p>
    <form method="post" action="{{ route('recuperar.enviar') }}">@csrf
        <div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
        <button class="btn btn-lg btn-block" style="background:var(--blue)">Enviar código</button>
    </form>
</div>
@endsection
