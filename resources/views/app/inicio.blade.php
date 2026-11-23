@extends('layouts.app')
@section('titulo', 'Início')
@section('app')
<div class="app-head">
    <div class="row between"><a class="brand" href="#">@include('partials.logo') <span class="brand-nome">{{ config('moz.nome') }}</span></a><span class="small" style="color:#cfe8f7">Olá, {{ auth()->user()->primeiroNome() }}</span></div>
    <h1 style="margin-top:18px">Para onde vai?</h1><div class="sub">Pesquise viagens entre as províncias.</div>
</div>
<div class="app-body" style="margin-top:-8px">
    @include('partials.alertas', ['semErros' => true])
    <form class="app-card" method="get" action="{{ route('app.viagens') }}">
        <div class="field"><label>Origem</label>
            <select class="input" name="origem" data-destinos='@json($mapa)' data-alvo="#destino" required>
                @foreach ($mapa->keys()->sort() as $o)<option {{ old('origem', request('origem', 'Maputo')) === $o ? 'selected' : '' }}>{{ $o }}</option>@endforeach
            </select></div>
        <div class="field"><label>Destino</label><select class="input" name="destino" id="destino" data-valor="{{ request('destino') }}" required></select></div>
        <div class="field"><label>Data da viagem</label><input class="input" type="date" name="data" min="{{ now()->toDateString() }}" value="{{ request('data', now()->toDateString()) }}" required></div>
        <button class="btn btn-lg btn-block" style="background:var(--blue)">Pesquisar viagens</button>
    </form>
    @if ($populares->count())
        <h3 class="mt2 mb">Rotas populares</h3>
        @foreach ($populares as $p)
            <a class="app-card list-row" href="{{ route('app.viagens', ['origem' => $p->origem, 'destino' => $p->destino, 'data' => now()->toDateString()]) }}" style="color:inherit;text-decoration:none">
                <span class="ic" style="background:var(--accent-soft);color:var(--blue-d)">@include('partials.icone', ['n' => 'route'])</span>
                <div class="grow"><b>{{ $p->origem }} → {{ $p->destino }}</b><div class="small muted">{{ $p->total }} bilhetes vendidos</div></div>
                <span class="muted">›</span>
            </a>
        @endforeach
    @endif
</div>
@endsection
