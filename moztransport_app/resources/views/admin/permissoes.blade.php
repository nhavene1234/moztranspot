@extends('layouts.painel')
@section('titulo', 'Gerir permissões')
@section('seccao', 'Painel Admin')
@section('painel')
<div class="page-head"><div><h1>Matriz de Permissões</h1><p>Defina os níveis de acesso globais para cada tipo de utilizador no sistema.</p></div></div>
<form method="post" action="{{ route('admin.permissoes.guardar') }}" class="card">@csrf
    <div class="tbl-scroll"><table class="tbl">
        <thead><tr class="perm-row"><th>Permissão do sistema</th>@foreach (\App\Models\Permissao::PERFIS as $p)<th>{{ \App\Models\Utilizador::PERFIS[$p] }}</th>@endforeach</tr></thead><tbody>
        @foreach (\App\Models\Permissao::CHAVES as $chave => [$texto, $alerta])
            <tr class="perm-row {{ $alerta ? 'alerta' : '' }}"><td><b>{{ $texto }}</b> @if ($alerta)<span class="pill warn">ALERTA DE SEGURANÇA</span>@endif</td>
                @foreach (\App\Models\Permissao::PERFIS as $p)
                    @php $on = $matriz[$chave][$p]->ativo ?? false; @endphp
                    <td>@if ($p === 'administrador')<span class="switch"><input type="checkbox" checked disabled><i style="opacity:.55"></i></span>
                        @else<span class="switch"><input type="checkbox" name="perm[{{ $p }}][{{ $chave }}]" value="1" {{ $on ? 'checked' : '' }}><i></i></span>@endif</td>
                @endforeach</tr>
        @endforeach
        </tbody></table></div>
    <div class="alert alert-warn mt small">As alterações realizadas nesta matriz afectam de imediato todos os utilizadores associados a cada perfil. O administrador mantém sempre todas as permissões.</div>
    <button class="btn">Guardar permissões</button>
</form>
@endsection
