@extends('layouts.painel')
@section('titulo', 'Gerir permissões')
@section('seccao', 'Painel Admin')
@section('painel')
<div class="page-head"><div><h1>Matriz de Permissões</h1><p>Defina o que cada perfil pode fazer no sistema. Marque ou desmarque os interruptores e clique em «Guardar».</p></div></div>
<form method="post" action="{{ route('admin.permissoes.guardar') }}" class="card">@csrf
    <div class="tbl-scroll"><table class="tbl">
        <thead><tr class="perm-row"><th>Permissão do sistema</th>@foreach (\App\Models\Permissao::PERFIS as $p)<th style="text-align:center">{{ \App\Models\Utilizador::PERFIS[$p] }}</th>@endforeach</tr></thead><tbody>
        @foreach (\App\Models\Permissao::CHAVES as $chave => [$texto, $alerta])
            <tr class="perm-row {{ $alerta ? 'alerta' : '' }}"><td><b>{{ $texto }}</b> @if ($alerta)<span class="pill warn">ALERTA DE SEGURANÇA</span>@endif</td>
                @foreach (\App\Models\Permissao::PERFIS as $p)
                    @php $on = $matriz[$chave][$p]->ativo ?? false; @endphp
                    <td style="text-align:center">
                        @if ($p === 'administrador')
                            <span class="switch" title="O administrador tem sempre todas as permissões"><input type="checkbox" checked disabled><i style="opacity:.55"></i></span>
                        @elseif (\App\Models\Permissao::aplica($p, $chave))
                            <span class="switch"><input type="checkbox" name="perm[{{ $p }}][{{ $chave }}]" value="1" {{ $on ? 'checked' : '' }} aria-label="{{ $texto }} — {{ \App\Models\Utilizador::PERFIS[$p] }}"><i></i></span>
                        @else
                            <span class="muted" title="Não se aplica a este perfil">—</span>
                        @endif
                    </td>
                @endforeach</tr>
        @endforeach
        </tbody></table></div>
    <div class="alert alert-warn mt small">As alterações afectam de imediato todos os utilizadores do perfil (o menu e as páginas passam a refletir a nova permissão). O administrador mantém sempre todas as permissões. O símbolo «—» indica que a permissão não se aplica ao perfil.</div>
    <div class="row" style="gap:10px;flex-wrap:wrap">
        <button class="btn">Guardar permissões</button>
        <button class="btn btn-outline" type="submit" formaction="{{ route('admin.permissoes.repor') }}" onclick="return confirm('Repor todas as permissões para os valores por omissão?')">Repor valores por omissão</button>
    </div>
</form>
@endsection
