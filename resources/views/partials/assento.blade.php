@php $occ = in_array($a->id, $ocupados); @endphp
<label class="seat {{ $occ ? 'occ' : '' }}" data-id="{{ $a->id }}" title="Lugar {{ $a->rotulo }}">
    @if (($modo ?? 'um') === 'ver')
        <span>{{ $a->rotulo }}</span>
    @else
        <input type="{{ $modo === 'varios' ? 'checkbox' : 'radio' }}" name="{{ $modo === 'varios' ? 'assento_ids[]' : 'assento_id' }}" value="{{ $a->id }}" data-rotulo="{{ $a->rotulo }}" @disabled($occ)>
        <span>{{ $a->rotulo }}</span>
    @endif
</label>
