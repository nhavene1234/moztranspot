<div class="steps">
    @foreach ([1 => ['Escolher viagem', 'Rotas, horários e preço'], 2 => ['Escolher lugar', 'Mapa interactivo do autocarro'], 3 => ['Dados e pagamento', 'Confirmação e emissão']] as $n => [$t, $s])
        <div class="step {{ $n <= $passo ? 'on' : '' }} {{ $n < $passo ? 'done' : '' }}"><b>{{ $n < $passo ? '✓' : $n }}</b><div>{{ $t }}<small>{{ $s }}</small></div></div>
    @endforeach
</div>
