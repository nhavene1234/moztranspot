@if ($empresas->count())
<form method="get" class="no-print">
    @foreach (request()->except('transportadora', 'page') as $k => $v)@if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
    <select class="input" name="transportadora" data-autosubmit style="min-width:210px">
        <option value="">Todas as transportadoras</option>
        @foreach ($empresas as $e)<option value="{{ $e->id }}" {{ $tid === $e->id ? 'selected' : '' }}>{{ $e->nome }}</option>@endforeach
    </select>
</form>
@elseif ($empresa)
<span class="badge-co sel">{{ $empresa->nome }}</span>
@endif
