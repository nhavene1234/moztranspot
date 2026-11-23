@extends('layouts.painel')
@section('titulo', 'Transportadoras')
@section('seccao', 'Painel Admin')
@section('painel')
<div class="page-head"><div><h1>Transportadoras Registadas</h1><p>Faça a gestão das operadoras autorizadas a operar rotas e vender bilhetes no sistema.</p></div><button class="btn" data-modal-open="m-t">+ Adicionar transportadora</button></div>
<div class="grid3">
    @foreach ($transportadoras as $t)
        <div class="card"><div class="row between"><h2>{{ $t->nome }}</h2><span class="pill ok">{{ $t->estado === 'activa' ? 'Activa' : 'Inactiva' }}</span></div>
            <div class="muted small mb">{{ $t->descricao }}</div>
            <div class="row between small"><span class="muted">Autocarros activos:</span><b>{{ $t->autocarros_count }}</b></div>
            <div class="row between small"><span class="muted">Rotas concessionadas:</span><b>{{ $t->rotas_count }}</b></div>
            <div class="row between small"><span class="muted">Gestor de frota:</span><b>{{ $t->gestor->nome ?? '—' }}</b></div>
            @if ($t->contacto)<div class="row between small"><span class="muted">Contacto:</span><b>{{ $t->contacto }}</b></div>@endif
            <a class="small" href="{{ route('gestao.dashboard', ['transportadora' => $t->id]) }}" style="display:block;margin-top:12px">Ver detalhes da frota ›</a></div>
    @endforeach
</div>
<div class="modal-bg {{ $errors->any() ? 'open' : '' }}" id="m-t"><div class="modal"><h2>Nova transportadora</h2>
    <form method="post" action="{{ route('admin.transportadoras.guardar') }}">@csrf
        <div class="field"><label>Nome *</label><input class="input" name="nome" value="{{ old('nome') }}" required>@error('nome')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="grid2"><div class="field"><label>NUIT</label><input class="input" name="nuit" value="{{ old('nuit') }}"></div><div class="field"><label>Contacto</label><input class="input" name="contacto" value="{{ old('contacto') }}"></div></div>
        <div class="field"><label>Endereço</label><input class="input" name="endereco" value="{{ old('endereco') }}"></div>
        <div class="actions"><button type="button" class="btn btn-outline" data-modal-close>Cancelar</button><button class="btn">Adicionar</button></div></form></div></div>
@endsection
