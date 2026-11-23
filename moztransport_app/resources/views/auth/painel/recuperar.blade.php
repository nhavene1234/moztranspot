@extends('layouts.base')
@section('titulo', 'Recuperar palavra-passe')
@section('conteudo')
<div class="center-screen"><div class="modal">
    <div class="center mb"><a class="brand" href="#" style="justify-content:center;color:var(--navy)">@include('partials.logo') <span>Moz<b>Transport</b></span></a></div>
    <h2 class="center">Recuperar Palavra-passe</h2>
    <p class="muted center">Verifique a sua identidade em 3 passos para redefinir as credenciais.</p>
    <div class="progress-steps"><span class="on"><b>1</b> Email</span><i></i><span><b>2</b> Código</span><i></i><span><b>3</b> Nova senha</span></div>
    @if ($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('painel.recuperar.enviar') }}">@csrf
        <div class="field"><label>Email da conta</label><input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
        <button class="btn btn-block">Enviar código</button>
    </form>
    <a class="btn btn-outline btn-block mt" href="{{ route('painel.entrar') }}">Voltar ao Login</a>
</div></div>
@endsection
