@extends('layouts.app')
@section('titulo', 'Remarcar')
@section('app')
@php $o = $reserva->viagem; @endphp
<div class="app-head"><div class="top"><a class="back" href="{{ route('app.bilhete', $reserva) }}">@include('partials.icone', ['n' => 'back'])</a><div><h1>Remarcar viagem</h1><div class="sub">{{ $o->rota->nome }} · mesmo preço</div></div></div></div>
<div class="app-body">
    <div class="alert alert-info small">Viagem actual: {{ $o->data->translatedFormat('D, d M') }} às {{ $o->hora }}. Escolha uma nova viagem da mesma rota e preço.</div>
    @forelse ($alternativas as $v)
        @php $livres = $v->autocarro->capacidade_total - $v->ocupados_count; @endphp
        <a class="app-card trip" style="display:block;color:inherit;text-decoration:none" href="{{ route('app.remarcar.lugares', [$reserva, $v]) }}">
            <div class="row between"><b>{{ $v->data->translatedFormat('D, d M') }} · {{ $v->hora }}</b><span class="pill ok">{{ $livres }} livres</span></div>
            <div class="small muted">{{ $v->classe }} · chegada {{ $v->hora_chegada }}</div>
        </a>
    @empty
        <div class="app-card center muted">Sem outras viagens disponíveis nos próximos 30 dias com o mesmo preço.</div>
    @endforelse
</div>
@endsection
