<?php

namespace App\Support;

class Fmt
{
    /** 2500 => "2.500 MT" */
    public static function mt($valor): string
    {
        return number_format((float) $valor, 0, ',', '.') . ' MT';
    }

    /** 570 => "9h 30m" */
    public static function duracao(int $minutos): string
    {
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        return $m ? "{$h}h {$m}m" : "{$h}h";
    }

    public static function metodoRotulo(?string $m): string
    {
        return \App\Models\Pagamento::METODOS[$m] ?? ($m ?? '—');
    }
}
