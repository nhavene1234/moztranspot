@extends('layouts.painel')
@section('titulo', 'Auditoria e Segurança')
@section('seccao', 'Painel Admin')
@section('painel')
<div class="page-head"><div><h1>Logs de Auditoria</h1><p>Histórico de acções dos utilizadores para conformidade e segurança da rede.</p></div>
    <a class="btn btn-outline" href="{{ route('admin.auditoria.exportar', request()->query()) }}">⬇ Exportar CSV</a></div>
<div class="card">
    <form method="get" class="filters">
        <div class="field"><label>Período</label><select class="input" name="periodo" data-autosubmit>@foreach (['hoje' => 'Hoje', '7d' => 'Últimos 7 dias', '30d' => 'Últimos 30 dias', 'todos' => 'Tudo'] as $k => $t)<option value="{{ $k }}" {{ $periodo === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></div>
        <div class="field"><label>Utilizador</label><select class="input" name="utilizador" data-autosubmit><option value="">Todos</option>@foreach ($utilizadores as $n)<option {{ request('utilizador') === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
        <div class="field"><label>Acção</label><select class="input" name="acao" data-autosubmit><option value="">Todas as acções</option>@foreach ($acoes as $a)<option {{ request('acao') === $a ? 'selected' : '' }}>{{ $a }}</option>@endforeach</select></div>
    </form>
    <div class="tbl-scroll"><table class="tbl"><thead><tr><th>Data / Hora</th><th>Utilizador</th><th>Acção realizada</th><th>Endereço IP</th></tr></thead><tbody>
    @forelse ($logs as $l)
        <tr><td class="mono small">{{ $l->created_at->format('d/m/Y H:i') }}</td><td><b>{{ $l->utilizador_nome ?? 'Sistema Automático' }}</b></td>
            <td><span class="dot {{ $l->cor }}"></span>{{ $l->acao }}@if ($l->detalhe) <span class="muted small">— {{ $l->detalhe }}</span>@endif</td><td class="mono small">{{ $l->ip ?? '—' }}</td></tr>
    @empty<tr><td colspan="4" class="center muted" style="padding:24px">Sem registos neste período.</td></tr>@endforelse
    </tbody></table></div>{{ $logs->links() }}
</div>
@endsection
