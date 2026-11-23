@extends('layouts.painel')
@section('titulo', 'Perfil do utilizador')
@section('seccao', 'Administração')
@section('painel')
<div class="page-head"><div><h1>Gerir dados pessoais</h1><p>Actualize as suas informações básicas e credenciais de acesso ao painel.</p></div></div>
@if (session('estado_senha'))<div class="alert alert-ok">{{ session('estado_senha') }}</div>@endif
<div class="grid2" style="align-items:start">
    <form class="card" method="post" action="{{ route('perfil.actualizar') }}">@csrf @method('PUT')
        <h2 class="mb">Informações básicas</h2>
        <div class="row mb"><span class="avatar" style="width:64px;height:64px;font-size:20px">{{ $user->iniciais() }}</span><div><b>{{ $user->rotuloPerfil() }}</b><div class="small muted">{{ $user->transportadora->nome ?? $user->agencia ?? 'Todas as transportadoras' }}</div></div></div>
        <div class="field"><label>Nome completo</label><input class="input" name="nome" value="{{ old('nome', $user->nome) }}" required>@error('nome')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Endereço de email</label><input class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Contacto telefónico</label><input class="input" name="telefone" value="{{ old('telefone', $user->telefone) }}"></div>
        <button class="btn">Gravar alterações</button>
    </form>
    <form class="card" method="post" action="{{ route('perfil.senha') }}">@csrf @method('PUT')
        <h2>Segurança da conta</h2><p class="card-sub">Recomendamos que altere a palavra-passe de 3 em 3 meses.</p>
        <div class="field"><label>Palavra-passe actual</label><input class="input" type="password" name="password_actual" required>@error('password_actual')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Nova palavra-passe</label><input class="input" type="password" name="password" data-strength="#forca" required minlength="8"><div class="strength" id="forca"><i></i><i></i><i></i><i></i></div>@error('password')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Confirmar nova palavra-passe</label><input class="input" type="password" name="password_confirmation" required></div>
        <button class="btn">Actualizar palavra-passe</button>
    </form>
</div>
@endsection
