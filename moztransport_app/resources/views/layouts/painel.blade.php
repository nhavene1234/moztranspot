@extends('layouts.base')
@php
    $u = auth()->user();
    $tema = ['administrador' => 't-admin', 'gestor' => 't-gestor', 'cobrador' => 't-cobrador'][$u->perfil] ?? '';
    if ($u->perfil === 'administrador' && request()->routeIs('gestao.*')) { $tema = 't-gestor'; }
    $menu = [];
    $item = fn ($rota, $icone, $texto, $on = null) => ['rota' => $rota, 'icone' => $icone, 'texto' => $texto, 'on' => $on ?? $rota];
    if ($u->perfil === 'bilheteiro') {
        $menu[] = $item('bilheteira.vender', 'ticket', 'Vender bilhete', 'bilheteira.vender*');
        $menu[] = $item('bilheteira.lugares.lista', 'seat', 'Lugares disponíveis', 'bilheteira.lugares*');
        $menu[] = $item('bilheteira.vendas', 'cash', 'Vendas do dia', 'bilheteira.vendas*');
    } elseif ($u->perfil === 'cobrador') {
        $menu[] = $item('cobrador.viagens', 'bus', 'As minhas viagens', 'cobrador.*');
    } elseif ($u->perfil === 'gestor') {
        $menu[] = $item('gestao.dashboard', 'chart', 'Dashboard');
        $menu[] = $item('gestao.vendas', 'ticket', 'Vendas');
        if ($u->tem('ver_relatorios')) { $menu[] = $item('gestao.relatorios', 'list', 'Relatórios'); $menu[] = $item('gestao.estatisticas', 'bars', 'Estatísticas'); }
        if ($u->tem('gerir_rotas')) { $menu[] = $item('gestao.rotas', 'route', 'Rotas e horários', 'gestao.rotas*'); }
    } elseif ($u->perfil === 'administrador') {
        $menu[] = $item('admin.visao', 'home', 'Visão geral');
        $menu[] = $item('admin.utilizadores', 'users', 'Utilizadores');
        $menu[] = $item('admin.permissoes', 'lock', 'Permissões');
        $menu[] = $item('admin.transportadoras', 'building', 'Transportadoras');
        $menu[] = $item('admin.auditoria', 'list', 'Auditoria');
        $menu[] = $item('admin.seguranca', 'shield', 'Segurança');
        $menu[] = ['sep' => 'Operação'];
        $menu[] = $item('gestao.dashboard', 'chart', 'Dashboard de vendas');
        $menu[] = $item('gestao.relatorios', 'list', 'Relatórios');
        $menu[] = $item('gestao.rotas', 'route', 'Rotas e horários', 'gestao.rotas*');
    }
    $menu[] = ['sep' => 'Conta'];
    $menu[] = $item('perfil', 'user', 'O meu perfil', 'perfil*');
@endphp
@section('corpo', $tema)
@section('conteudo')
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="flagbar"></div>
        <a class="brand" href="{{ $u->rotaInicial() }}">@include('partials.logo') <span>Moz<b>Transport</b></span></a>
        <nav class="nav">
            @foreach ($menu as $m)
                @if (isset($m['sep']))
                    <div class="small" style="margin:14px 12px 6px;color:#64809f;text-transform:uppercase;letter-spacing:.08em;font-size:10px">{{ $m['sep'] }}</div>
                @else
                    <a href="{{ route($m['rota']) }}" class="{{ request()->routeIs($m['on']) ? 'on' : '' }}">@include('partials.icone', ['n' => $m['icone']]) {{ $m['texto'] }}</a>
                @endif
            @endforeach
        </nav>
        <div class="side-user">
            <span class="avatar">{{ $u->iniciais() }}</span>
            <div><div class="n">{{ $u->nome }}</div><div class="r">{{ $u->rotuloPerfil() }}{{ $u->transportadora ? ' · ' . $u->transportadora->nome : '' }}</div></div>
            <form method="post" action="{{ route('sair') }}">@csrf<button title="Terminar sessão">Sair</button></form>
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <button class="btn btn-outline btn-sm menu-btn" data-menu type="button">☰</button>
            <div class="crumbs">@yield('seccao', 'Painel') › <b>@yield('titulo')</b></div>
            <div class="grow"></div>
            @yield('topo')
            @if ($u->transportadora)<span class="badge-co sel">{{ $u->transportadora->nome }}</span>
            @elseif ($u->agencia)<span class="badge-co sel">{{ $u->agencia }}</span>
            @elseif ($u->perfil === 'administrador')<span class="badge-co">Todas as transportadoras</span>@endif
        </header>
        <main class="content">
            @include('partials.alertas')
            @yield('painel')
        </main>
    </div>
</div>
@endsection
