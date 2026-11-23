@if (session('estado'))<div class="alert alert-ok">{{ session('estado') }}</div>@endif
@if (session('erro'))<div class="alert alert-err">{{ session('erro') }}</div>@endif
@if ($errors->any() && ! ($semErros ?? false))
    <div class="alert alert-err"><strong>Verifique os dados:</strong>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif
