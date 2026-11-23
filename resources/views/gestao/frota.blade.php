@extends('layouts.painel')
@section('titulo', 'Autocarros')
@section('seccao', 'Gestão')
@section('painel')
<div class="page-head"><div><h1>Autocarros</h1><p>Registe os autocarros da frota, guarde o livrete de cada um e controle quais estão activos.</p></div>
    <div class="row wrap">@include('gestao._empresa')<button class="btn" data-modal-open="m-bus">+ Novo autocarro</button></div></div>

<div class="card">
    <form method="get" class="filters">
        @if (request('transportadora'))<input type="hidden" name="transportadora" value="{{ request('transportadora') }}">@endif
        <div class="field grow"><label>Pesquisar</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Matrícula ou modelo"></div>
        <button class="btn btn-outline">Pesquisar</button>
    </form>
    <div class="tbl-scroll"><table class="tbl">
        <thead><tr><th>Matrícula</th><th>Modelo</th>@if ($admin)<th>Transportadora</th>@endif<th>Livrete</th><th>Viagens</th><th>Estado</th><th class="right">Acções</th></tr></thead><tbody>
        @forelse ($autocarros as $a)
            @php $fill = ['_put' => 1, '_titulo' => 'Editar autocarro', 'matricula' => $a->matricula, 'modelo' => $a->modelo, 'ano' => $a->ano]; @endphp
            <tr>
                <td><b class="mono">{{ $a->matricula }}</b><div class="small muted">{{ $a->capacidade_total }} lugares</div></td>
                <td>{{ $a->modelo ?: '—' }}{{ $a->ano ? ' · ' . $a->ano : '' }}</td>
                @if ($admin)<td>{{ $a->transportadora->nome }}</td>@endif
                <td>@if ($a->livrete_caminho)
                        <a class="pill ok" href="{{ route('gestao.autocarros.livrete', $a) }}" target="_blank" rel="noopener">Ver livrete</a>
                        <div class="small muted" style="margin-top:3px">{{ $a->livrete_em?->format('d/m/Y') }}</div>
                    @else<span class="pill warn">Em falta</span>@endif</td>
                <td>{{ $a->viagens_count }}<div class="small muted">{{ $a->viagens_futuras_count }} futura(s)</div></td>
                <td><span class="pill {{ $a->estado === 'activo' ? 'ok' : 'err' }}">{{ $a->estado === 'activo' ? 'Activo' : 'Desactivado' }}</span></td>
                <td class="right"><div class="row" style="justify-content:flex-end">
                    <button class="btn btn-sm btn-outline" data-modal-open="m-bus" data-action="{{ route('gestao.autocarros.actualizar', $a) }}" data-fill="{{ json_encode($fill) }}">Editar</button>
                    <form method="post" action="{{ route('gestao.autocarros.estado', $a) }}" @if ($a->estado === 'activo' && $a->viagens_futuras_count) data-confirm="Este autocarro tem {{ $a->viagens_futuras_count }} viagem(ns) futura(s). Elas mantêm-se, mas deixa de poder ser usado em novas viagens. Continuar?" @endif>@csrf
                        <button class="btn btn-sm {{ $a->estado === 'activo' ? 'btn-danger-outline' : 'btn-soft' }}">{{ $a->estado === 'activo' ? 'Desactivar' : 'Activar' }}</button></form>
                    <form method="post" action="{{ route('gestao.autocarros.eliminar', $a) }}" data-confirm="Eliminar o autocarro {{ $a->matricula }}? Esta acção não pode ser desfeita.">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger-outline" @disabled($a->viagens_count)>Eliminar</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="7" class="center muted" style="padding:26px">Ainda não há autocarros. Clique em “+ Novo autocarro”.</td></tr>
        @endforelse
        </tbody></table></div>
    <p class="small muted mt">Só é possível eliminar autocarros sem viagens. Os restantes podem ser desactivados, para preservar o histórico de vendas.</p>
</div>

<div class="modal-bg" id="m-bus" data-reset="1"><div class="modal">
    <h2 data-title="Novo autocarro">Novo autocarro</h2><p class="muted small mb">O autocarro é criado com 44 lugares (mapa 2+2).</p>
    <form method="post" action="{{ route('gestao.autocarros.guardar') }}" data-create-action="{{ route('gestao.autocarros.guardar') }}" enctype="multipart/form-data">@csrf
        <input type="hidden" name="_method" value="PUT" disabled>
        @if ($admin)<div class="field"><label>Transportadora *</label><select class="input" name="transportadora_id"><option value="">—</option>@foreach ($empresas as $e)<option value="{{ $e->id }}" {{ (int) old('transportadora_id', $tid) === $e->id ? 'selected' : '' }}>{{ $e->nome }}</option>@endforeach</select></div>@endif
        <div class="field"><label>Matrícula *</label><input class="input mono" name="matricula" required placeholder="ABC-123-MP" maxlength="20"></div>
        <div class="grid2"><div class="field"><label>Modelo</label><input class="input" name="modelo" maxlength="60"></div>
            <div class="field"><label>Ano</label><input class="input" type="number" name="ano" min="1980" max="{{ now()->year + 1 }}"></div></div>
        <div class="field"><label>Livrete do carro (PDF, JPG ou PNG, até 5 MB)</label><input class="input" type="file" name="livrete" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
            <div class="small muted" style="margin-top:4px">Ao editar, escolher um novo ficheiro substitui o livrete actual.</div></div>
        <label class="row small" style="gap:8px;margin-bottom:6px"><input type="checkbox" name="remover_livrete" value="1"> Remover o livrete actual (ao editar)</label>
        <div class="actions"><button type="button" class="btn btn-outline" data-modal-close>Cancelar</button><button class="btn">Guardar</button></div>
    </form></div></div>
@endsection
