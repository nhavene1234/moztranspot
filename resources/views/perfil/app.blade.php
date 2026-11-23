@extends('layouts.app')
@section('titulo', 'Perfil')
@section('app')
<div class="app-head"><div class="row"><span class="avatar" style="width:52px;height:52px;font-size:17px;background:rgba(255,255,255,.22)">{{ $user->iniciais() }}</span><div><h1>{{ $user->nome }}</h1><div class="sub">{{ $user->email }}</div></div></div></div>
<div class="app-body">
    @if (session('estado'))<div class="alert alert-ok">{{ session('estado') }}</div>@endif
    @if (session('estado_senha'))<div class="alert alert-ok">{{ session('estado_senha') }}</div>@endif
    <form class="app-card" method="post" action="{{ route('perfil.actualizar') }}">@csrf @method('PUT')
        <h3 class="mb">Dados pessoais</h3>
        <div class="field"><label>Nome completo</label><input class="input" name="nome" value="{{ old('nome', $user->nome) }}" required>@error('nome')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Telefone</label><input class="input" name="telefone" value="{{ old('telefone', $user->telefone) }}"></div>
        <div class="field"><label>Documento</label><input class="input" name="documento" value="{{ old('documento', $user->documento) }}"></div>
        <button class="btn btn-block" style="background:var(--blue)">Guardar alterações</button>
    </form>
    <form class="app-card" method="post" action="{{ route('perfil.senha') }}">@csrf @method('PUT')
        <h3 class="mb">Alterar palavra-passe</h3>
        <div class="field"><label>Palavra-passe actual</label><input class="input" type="password" name="password_actual" required>@error('password_actual')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Nova palavra-passe</label><input class="input" type="password" name="password" required minlength="8">@error('password')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Confirmar</label><input class="input" type="password" name="password_confirmation" required></div>
        <button class="btn btn-outline btn-block">Actualizar palavra-passe</button>
    </form>
    <form class="app-card" method="post" action="{{ route('perfil.notificacoes') }}">@csrf @method('PUT')
        <label class="row between" style="cursor:pointer"><span><b>Notificações</b><div class="small muted">Lembretes de viagem por SMS/email</div></span>
            <span class="switch"><input type="checkbox" name="notificacoes" value="1" {{ $user->notificacoes ? 'checked' : '' }} data-autosubmit><i></i></span></label>
    </form>
    <form method="post" action="{{ route('sair') }}">@csrf<button class="btn btn-danger-outline btn-block">Terminar sessão</button></form>
</div>
@endsection
