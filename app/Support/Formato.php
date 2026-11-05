<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Formatos de números y fechas compartidos por las vistas. Las fechas usan el
 * idioma de la app (APP_LOCALE).
 */
class Formato
{
    /** Entero con punto de miles, como en Chile: 8240 -> "8.240". */
    public static function miles(float|int $valor): string
    {
        return number_format(round($valor), 0, ',', '.');
    }

    /** "45 min", "3h 20" o "2h". */
    public static function minutos(int $minutos): string
    {
        $minutos = max(0, $minutos);
        if ($minutos < 60) {
            return "{$minutos} min";
        }

        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return $resto === 0 ? "{$horas}h" : sprintf('%dh %02d', $horas, $resto);
    }

    /** "90 s", "2 min" o "2 min 30 s". */
    public static function segundos(int $segundos): string
    {
        if ($segundos < 120 && $segundos % 60 !== 0) {
            return "{$segundos} s";
        }

        $min = intdiv($segundos, 60);
        $resto = $segundos % 60;

        return $resto === 0 ? "{$min} min" : "{$min} min {$resto} s";
    }

    /** "Lunes 31 de agosto". */
    public static function fechaLarga(CarbonInterface $fecha): string
    {
        return Str::ucfirst($fecha->isoFormat('dddd D [de] MMMM'));
    }

    /** "Lunes", "Martes"… */
    public static function nombreDia(CarbonInterface $fecha): string
    {
        return Str::ucfirst($fecha->isoFormat('dddd'));
    }

    /** "L", "M", "M", "J"… */
    public static function inicialDia(CarbonInterface $fecha): string
    {
        return Str::upper(Str::substr($fecha->isoFormat('dd'), 0, 1));
    }

    /** "Hoy", "Ayer", el día de la semana si fue hace menos de 7 días, o "12 sept". */
    public static function diaRelativo(CarbonInterface $fecha, CarbonInterface $hoy): string
    {
        $dias = (int) $fecha->copy()->startOfDay()->diffInDays($hoy->copy()->startOfDay());

        return match (true) {
            $dias === 0 => 'Hoy',
            $dias === 1 => 'Ayer',
            $dias > 1 && $dias < 7 => self::nombreDia($fecha),
            default => $fecha->isoFormat('D MMM'),
        };
    }
}
