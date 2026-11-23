@extends('layouts.base')
@section('corpo', 'app')
@section('conteudo')
<div class="phone">
    @yield('app')
    @hasSection('semtab')
    @else
        <nav class="tabbar">
            <a href="{{ route('app.inicio') }}" class="{{ request()->routeIs('app.inicio', 'app.viagens', 'app.lugares') ? 'on' : '' }}">@include('partials.icone', ['n' => 'home']) Início</a>
            <a href="{{ route('app.bilhetes') }}" class="{{ request()->routeIs('app.bilhete*', 'app.remarcar*', 'app.cancelada') ? 'on' : '' }}">@include('partials.icone', ['n' => 'ticket']) Bilhetes</a>
            <a href="{{ route('app.historico') }}" class="{{ request()->routeIs('app.historico') ? 'on' : '' }}">@include('partials.icone', ['n' => 'clock']) Histórico</a>
            <a href="{{ route('perfil') }}" class="{{ request()->routeIs('perfil*') ? 'on' : '' }}">@include('partials.icone', ['n' => 'user']) Perfil</a>
        </nav>
    @endif
</div>
@endsection
