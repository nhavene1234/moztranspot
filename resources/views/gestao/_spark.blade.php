@php
    $n = count($serie); $mx = max(array_merge($serie, [1]));
    $pts = collect($serie)->map(fn ($v, $i) => round($i * 70 / max(1, $n - 1), 1) . ',' . round(26 - ($v / $mx) * 24, 1))->implode(' ');
@endphp
<svg viewBox="0 0 70 28" preserveAspectRatio="none"><polyline points="{{ $pts }}" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linejoin="round"/></svg>
