<?php
namespace App\Translations;

class TimeDictionary extends DictionaryStatic
{
    public static
    $minutes,
    $hours,
    $wrong_hours_range_list,
    $wrong_hours_range,
    $begin_of_range_before_ending_of_prev_range;
    private static
    $n_min,
    $n_h,
    $x_ago,
    $in_x,
    $schedule_has_more_then_n_timeranges;
    protected static function set_en()
    {
        self::$minutes = 'minutes';
        self::$hours = 'hours';
        self::$n_min = '%d min';
        self::$n_h = '%d h';
        self::$x_ago = '%s ago';
        self::$in_x = 'in %s';
        self::$wrong_hours_range_list = 'Wrong hours range list';
        self::$wrong_hours_range = 'Wrong hours range';
        self::$begin_of_range_before_ending_of_prev_range = 'Begin of range is before ending of previous range';
        self::$schedule_has_more_then_n_timeranges = 'schedule has more then %d time-ranges';
    }
    protected static function set_es()
    {
        self::$minutes = 'minutos';
        self::$hours = 'horas';
        self::$n_min = '%d min';
        self::$n_h = '%d h';
        self::$x_ago = 'hace %s';
        self::$in_x = 'en %s';
        self::$wrong_hours_range_list = 'Lista de horas esta incorrecta';
        self::$wrong_hours_range = 'Rango de horas esta incorrecto';
        self::$begin_of_range_before_ending_of_prev_range = 'Inicio de rango esta antes de fin de rango previo';
        self::$schedule_has_more_then_n_timeranges = 'horario tiene mas de %d rangos de hora';
    }
    protected static function set_ru()
    {
        self::$minutes = 'минут';
        self::$hours = 'часов';
        self::$n_min = '%d мин';
        self::$n_h = '%d ч';
        self::$x_ago = '%s назад';
        self::$in_x = 'через %s';
        self::$wrong_hours_range_list = 'Неверный список временных интервалов';
        self::$wrong_hours_range = 'Неверный временной интервал';
        self::$begin_of_range_before_ending_of_prev_range = 'Начало интервала раньше конца предыдущего интервала';
        self::$schedule_has_more_then_n_timeranges = 'в расписании указано больше %d временных интервалов';
    }
    public static function schedule_has_more_then_n_timeranges($n)
    {
        return sprintf(self::$schedule_has_more_then_n_timeranges, $n);
    }
    public static function x_ago($x)
    {
        return sprintf(self::$x_ago, $x);
    }
    public static function n_minutes($n)
    {
        return sprintf(self::$n_min, $n);
    }
    public static function n_hours($n)
    {
        return sprintf(self::$n_h, $n);
    }
    public static function n_minutes_ago($n)
    {
        return self::x_ago(self::n_minutes($n));
    }
    public static function n_hours_ago($n)
    {
        return self::x_ago(self::n_hours($n));
    }
    public static function in_n_minutes($n)
    {
        return self::in_x(self::n_minutes($n));
    }
    public static function in_n_hours($n)
    {
        return self::in_x(self::n_hours($n));
    }
    public static function in_x($s)
    {
        return sprintf(self::$in_x, $s);
    }
}
