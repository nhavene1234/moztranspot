@extends('layouts.painel')
@section('titulo', 'Validar bilhetes')
@section('seccao', 'Cobrador')
@push('libs')<script src="{{ asset('js/vendor/jsQR.js') }}"></script>@endpush
@section('painel')
@php $validados = $passageiros->where('bilhete.estado', 'usado')->count(); @endphp
<div class="page-head"><div><h1>{{ $viagem->rota->nome }}</h1><p>{{ $viagem->rota->transportadora->nome }} · {{ $viagem->data->format('d/m') }} às {{ $viagem->hora }} · Matrícula <b class="mono">{{ $viagem->autocarro->matricula }}</b>{{ $viagem->motorista ? ' · Motorista: ' . $viagem->motorista->nome : '' }}</p></div>
    <span class="pill accent" style="font-size:13px">{{ $validados }} / {{ $passageiros->count() }} embarcados</span></div>
<div class="grid2" style="align-items:start">
    <div class="card">
        <h2 class="mb">Validar bilhete</h2>
        <div class="scan-box" data-scanner="{{ route('cobrador.validar', $viagem) }}">
            <video class="hidden" muted></video><div class="frame"></div><div class="placeholder center">Câmara desligada</div>
        </div>
        <button class="btn btn-block mt" id="btn-camera" type="button">Ligar câmara e ler QR</button>
        <div id="resultado-scan" class="result hidden"></div>
        @if (session('resultado'))
            @php $res = session('resultado'); @endphp
            <div class="result {{ $res['ok'] ? 'ok' : 'no' }}"><h2>{{ $res['ok'] ? 'Bilhete válido' : 'Bilhete recusado' }}</h2><div>{{ $res['mensagem'] }}</div>
                @if (! empty($res['passageiro']))<div class="small" style="margin-top:6px">{{ $res['passageiro'] }} · lugar {{ $res['lugar'] }}</div>@endif</div>
        @endif
        <form method="post" action="{{ route('cobrador.validar', $viagem) }}" class="mt">@csrf
            <div class="field"><label>Ou escreva o código do bilhete</label><div class="row"><input class="input mono" name="codigo" placeholder="MT-2026-000123" required><button class="btn">Validar</button></div>@error('codigo')<div class="err">{{ $message }}</div>@enderror</div>
        </form>
        @if ($viagem->estado !== 'concluida')
            <form method="post" action="{{ route('cobrador.terminar', $viagem) }}" data-confirm="Terminar esta viagem?">@csrf<button class="btn btn-outline btn-block">Terminar viagem</button></form>
        @endif
    </div>
    <div class="card">
        <h2 class="mb">Passageiros ({{ $passageiros->count() }})</h2>
        <div class="tbl-scroll"><table class="tbl">
            <thead><tr><th>Lugar</th><th>Passageiro</th><th>Documento</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse ($passageiros as $r)
                <tr><td><b>{{ $r->assento->rotulo }}</b></td><td>{{ $r->nome_passageiro }}</td><td class="small muted">{{ $r->documento }}</td>
                    <td><span class="pill {{ $r->bilhete?->estado === 'usado' ? 'ok' : 'warn' }}">{{ $r->bilhete?->estado === 'usado' ? 'Embarcou' : 'Por embarcar' }}</span></td></tr>
            @empty
                <tr><td colspan="4" class="center muted" style="padding:20px">Sem passageiros.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection
