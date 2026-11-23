@extends('layouts.app')
@section('titulo', 'Escolher lugar')
@section('semtab', '1')
@section('app')
<div class="app-head"><div class="top"><a class="back" href="{{ url()->previous() }}">@include('partials.icone', ['n' => 'back'])</a>
    <div><h1>Escolha os lugares</h1><div class="sub">{{ $viagem->rota->nome }} · {{ $viagem->data->translatedFormat('d M') }} às {{ $viagem->hora }}</div></div></div></div>
<form class="app-body" method="post" action="{{ route('app.reservar', $viagem) }}">@csrf
    @if ($errors->any())<div class="alert alert-err">{{ $errors->first() }}</div>@endif
    <div class="alert alert-info small">Pode escolher <b>até 5 lugares</b> para esta viagem e pagar tudo de uma só vez.</div>
    @include('partials.assentos', ['modo' => 'varios'])
    <div class="app-card mt">
        <div class="row between"><span class="muted">Lugares (máx. 5)</span><b id="sel-lista">—</b></div>
        <div class="row between"><span class="muted">Total · {{ $viagem->rota->transportadora->nome }}</span><b id="sel-total">—</b></div>
    </div>
    <p class="small muted center">Os lugares ficam bloqueados durante {{ app(\App\Services\ReservaService::class)->minutosBloqueio() }} minutos para fazer o pagamento.</p>
    <button class="btn btn-lg btn-block disabled" id="sel-btn" style="background:var(--blue)" disabled>Continuar para pagamento</button>
</form>
@endsection
