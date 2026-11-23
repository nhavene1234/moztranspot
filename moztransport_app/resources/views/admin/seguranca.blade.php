@extends('layouts.painel')
@section('titulo', 'Segurança')
@section('seccao', 'Painel Admin')
@section('painel')
<div class="page-head"><div><h1>Segurança e Cópias</h1><p>Regras de bloqueio, prazos de reserva e cópias de segurança da base de dados.</p></div></div>
<div class="grid2" style="align-items:start">
    <form class="card" method="post" action="{{ route('admin.seguranca.guardar') }}">@csrf
        <h2 class="card-title">Configuração de bloqueio e reservas</h2><div class="card-sub">Aplica-se a todos os utilizadores.</div>
        <div class="field"><label>Tentativas de login antes do bloqueio</label><input class="input" type="number" name="max_tentativas_login" min="3" max="10" value="{{ old('max_tentativas_login', $def['max_tentativas_login']) }}">@error('max_tentativas_login')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Duração do bloqueio de login (minutos)</label><input class="input" type="number" name="minutos_bloqueio_login" min="1" max="120" value="{{ old('minutos_bloqueio_login', $def['minutos_bloqueio_login']) }}"></div>
        <div class="field"><label>Tempo de bloqueio do lugar durante o pagamento (minutos)</label><input class="input" type="number" name="minutos_bloqueio_lugar" min="3" max="60" value="{{ old('minutos_bloqueio_lugar', $def['minutos_bloqueio_lugar']) }}"></div>
        <div class="field"><label>Prazo para cancelar/remarcar (horas antes da partida)</label><input class="input" type="number" name="horas_limite_cancelamento" min="0" max="168" value="{{ old('horas_limite_cancelamento', $def['horas_limite_cancelamento']) }}"></div>
        <button class="btn">Guardar definições</button>
    </form>
    <div class="card"><h2 class="card-title">Cópias de segurança (Backup)</h2><div class="card-sub">Motor da base de dados: <b>{{ $driver }}</b></div>
        <div class="row between small"><span class="muted">Último backup:</span><b>{{ $def['ultimo_backup'] ?: 'nunca' }}</b></div>
        <form method="post" action="{{ route('admin.seguranca.backup') }}" class="mt">@csrf<button class="btn btn-block">Fazer backup agora</button></form>
        @if ($backups->count())<h3 class="mt2 mb">Ficheiros recentes</h3>@foreach ($backups as $b)<div class="mono small muted">{{ $b }}</div>@endforeach
        <p class="hint">Guardados em storage/app/backups. Copie-os regularmente para fora do servidor.</p>@endif
        <p class="hint mt">Para backups automáticos diários, agende <span class="mono">php artisan moz:backup</span> no cron do servidor (ver README).</p>
    </div>
</div>
@endsection
