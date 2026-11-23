<?php

namespace App\Support;

class Csv
{
    /**
     * Impede "injecção de fórmulas" quando o CSV é aberto no Excel/LibreOffice:
     * valores que começam por = + - @ passam a texto simples.
     */
    public static function seguro(mixed $valor): mixed
    {
        if (is_string($valor) && $valor !== '' && in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $valor;
        }

        return $valor;
    }
}
