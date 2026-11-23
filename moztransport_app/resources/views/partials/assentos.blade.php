{{-- Mapa de lugares. Parâmetros: $viagem, $ocupados[], $modo = 'um' | 'varios' | 'ver' --}}
@php
    $modo = $modo ?? 'um';
    $porFila = $viagem->autocarro->assentos->groupBy(fn ($a) => $a->fila);
@endphp
<div class="bus" @if ($modo !== 'ver') data-seatmap data-preco="{{ $viagem->preco }}" data-multi="{{ $modo === 'varios' ? 1 : 0 }}" data-lista="#sel-lista" data-total="#sel-total" data-btn="#sel-btn" @else data-live-seats="{{ route('bilheteira.lugares.estado', $viagem) }}" data-info="#live-info" @endif>
    <div class="cabine"><span>MOTORISTA</span><span>CABINE</span><span>PORTA</span></div>
    @foreach ($porFila as $fila => $assentos)
        <div class="bus-row">
            @foreach ($assentos->take(2) as $a) @include('partials.assento', ['a' => $a]) @endforeach
            <span class="fila">{{ $fila }}</span>
            @foreach ($assentos->slice(2, 2) as $a) @include('partials.assento', ['a' => $a]) @endforeach
        </div>
    @endforeach
    <div class="legend"><span><i class="l"></i>Livre</span><span><i class="o"></i>Ocupado</span>@if ($modo !== 'ver')<span><i class="s"></i>Seleccionado</span>@endif</div>
</div>
