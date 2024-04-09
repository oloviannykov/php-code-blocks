<?php
namespace App\Translations;

class DateDictionary extends DictionaryStatic
{
    public static
    $august,
    $april,
    $all_available_days_were_used,
    $allocated,
    $calendar,
    $can_not_increment_days_quantity,
    $days_counter_not_found,
    $days_quantity,
    $day,
    $days,
    $december,
    $date_is_required,
    $dates_range_is_required,
    $dates_conflict,
    $days_left,
    $every_day,
    $first,
    $full_day,
    $free_half_day,
    $free_all_day,
    $february,
    $half_day,
    $holidays_calendar,
    $hospital_half_day,
    $hospital_all_day,
    $in_half_day,
    $is_free,
    $is_used,
    $january,
    $june,
    $july,
    $last,
    $left,
    $month,
    $months,
    $month_day,
    $medical_care,
    $medical_care_days_left,
    $maximum_dates_range_exceeded,
    $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday,
    $monday_2s, $tuesday_2s, $wednesday_2s, $thursday_2s, $friday_2s, $saturday_2s, $sunday_2s,
    $march,
    $may,
    $missing_half_day,
    $missing_all_day,
    $november,
    $no_available_holidays,
    $try_less_days_quantity,
    $record_was_found_inside_the_dates_range,
    $september,
    $second,
    $october,
    $today,
    $tomorrow,
    $used,
    $used_days,
    $used_days_of_medical_care,
    $use_rest_of_the_days,
    $use_full_day_only_for_multiple_insert,
    $wrong_year_format,
    $wrong_day_type,
    $works,
    $works_all_day,
    $wrong_start_date,
    $wrong_end_date,
    $wrong_date_format,
    $wrong_date_range,
    $week,
    $week_day,
    $year,
    $yesterday,
    $year_required;
    private static
    $n_days,
    $and_half,
    $n_days_x1,
    $n_days_x234,
    $n_months,
    $n_months_x1,
    $n_months_x234,
    $used_x_of_medical_care,
    $every_x,
    $x_left,
    $x_extra,
    $january2,
    $february2,
    $march2,
    $april2,
    $may2,
    $june2,
    $july2,
    $august2,
    $september2,
    $october2,
    $november2,
    $december2,
    $day_month_weekday,
    $in_n_days,
    $in_x1_days,
    $in_x234_days,
    $maximum_period_length_x;
    protected static function set_en()
    {
        self::$day = 'day';
        self::$days = 'days';
        self::$and_half = 'and half';
        self::$n_days = '%d%s days'; //%d - integer part, %s - half part; ex. n=2.5 --> '2 and half days'
        self::$n_days_x1 = '%d%s days';
        self::$n_days_x234 = '%d%s days';
        self::$month = 'month';
        self::$months = 'months';
        self::$month_day = 'Month day';
        self::$n_months = '%d%s months';
        self::$n_months_x1 = '%d%s months';
        self::$n_months_x234 = '%d%s months';
        self::$year = 'year';
        self::$week = 'week';
        self::$week_day = 'week day';
        self::$full_day = 'full day';
        self::$first = 'first';
        self::$second = 'second';
        self::$last = 'last';
        self::$every_day = 'every day';
        self::$every_x = 'every %s';
        self::$half_day = 'half day';
        self::$days_quantity = 'days quantity';
        self::$calendar = 'calendar';
        self::$holidays_calendar = 'holidays calendar';
        self::$works = 'works';
        self::$works_all_day = 'works all day';
        self::$is_free = 'is free';
        self::$free_half_day = 'free half day';
        self::$free_all_day = 'free all day';
        self::$hospital_half_day = 'hospital half day';
        self::$hospital_all_day = 'hospital all day';
        self::$missing_half_day = 'missing half day';
        self::$missing_all_day = 'missing all day';
        self::$allocated = 'allocated';
        self::$used = 'used';
        self::$used_days = 'used days';
        self::$days_left = 'days left';
        self::$medical_care = 'medical care';
        self::$is_used = 'is used';
        self::$used_days_of_medical_care = 'used days of medical care';
        self::$used_x_of_medical_care = 'used %s of medical care';
        self::$medical_care_days_left = 'medical care days left';
        self::$all_available_days_were_used = 'all available days were used';
        self::$left = 'left';
        self::$x_left = '%s left'; //%s - '... days' / '... months' etc.
        self::$x_extra = '%s extra'; //%s - '... days' / '... months' etc.
        self::$use_rest_of_the_days = 'use rest of the days';
        self::$date_is_required = 'Date is required';
        self::$wrong_date_format = 'Wrong date format';
        self::$dates_range_is_required = 'Dates range is required';
        self::$wrong_start_date = 'Wrong start date';
        self::$wrong_end_date = 'Wrong end date';
        self::$wrong_date_range = 'Wrong date range';
        self::$dates_conflict = 'Dates conflict';
        self::$maximum_dates_range_exceeded = 'Maximum dates range exceeded';
        self::$wrong_day_type = 'Wrong day type';
        self::$use_full_day_only_for_multiple_insert = 'Use full day only for multiple insert';
        self::$days_counter_not_found = 'Days counter not found';
        self::$no_available_holidays = 'No available holidays';
        self::$try_less_days_quantity = 'Try less days quantity';
        self::$record_was_found_inside_the_dates_range = 'Record was found inside the dates range';
        self::$can_not_increment_days_quantity = 'Can not increment days quantity';
        self::$year_required = 'Year is required';
        self::$yesterday = 'yesterday';
        self::$wrong_year_format = 'Wrong year format';
        self::$monday_2s = 'MO';
        self::$monday = 'Monday';
        self::$tuesday_2s = 'TU';
        self::$tuesday = 'Tuesday';
        self::$wednesday_2s = 'WE';
        self::$wednesday = 'Wednesday';
        self::$thursday_2s = 'TH';
        self::$thursday = 'Thursday';
        self::$friday_2s = 'FR';
        self::$friday = 'Friday';
        self::$saturday_2s = 'SA';
        self::$saturday = 'Saturday';
        self::$sunday_2s = 'SU';
        self::$sunday = 'Sunday';
        self::$january = 'January';
        self::$february = 'February';
        self::$march = 'March';
        self::$april = 'April';
        self::$may = 'May';
        self::$june = 'June';
        self::$july = 'July';
        self::$august = 'August';
        self::$september = 'September';
        self::$october = 'October';
        self::$november = 'November';
        self::$december = 'December';
        self::$january2 = 'January';
        self::$february2 = 'February';
        self::$march2 = 'March';
        self::$april2 = 'April';
        self::$may2 = 'May';
        self::$june2 = 'June';
        self::$july2 = 'July';
        self::$august2 = 'August';
        self::$september2 = 'September';
        self::$october2 = 'October';
        self::$november2 = 'November';
        self::$december2 = 'December';
        self::$day_month_weekday = '%2$s %1$d, %3$s';
        self::$today = "today";
        self::$tomorrow = "tomorrow";
        self::$in_n_days = "in %d days";
        self::$in_x1_days = "in %d days";
        self::$in_x234_days = "in %d days";
        self::$in_half_day = "in half day";
        self::$maximum_period_length_x = 'Maximum period length %s';
    }
    protected static function set_es()
    {
        self::$day = 'dia';
        self::$days = 'dias';
        self::$and_half = 'y medio';
        self::$n_days = '%d%s dias';
        self::$n_days_x1 = '%d%s dias';
        self::$n_days_x234 = '%d%s dias';
        self::$month = 'mes';
        self::$months = 'meses';
        self::$month_day = 'Dia de mes';
        self::$n_months = '%d%s meses';
        self::$n_months_x1 = '%d%s meses';
        self::$n_months_x234 = '%d%s meses';
        self::$year = 'ano';
        self::$yesterday = 'ayer';
        self::$week = 'semana';
        self::$week_day = 'dia de semana';
        self::$full_day = 'todo dia';
        self::$first = 'primero';
        self::$second = 'segundo';
        self::$last = 'ultimo';
        self::$every_day = 'cada dia';
        self::$every_x = 'cada %s';
        self::$half_day = 'medio dia';
        self::$days_quantity = 'cantidad de dias';
        self::$calendar = 'calendario';
        self::$holidays_calendar = 'calendario de dias libres';
        self::$works = 'trabaja';
        self::$works_all_day = 'trabaja todo dia';
        self::$is_free = 'esta libre';
        self::$free_half_day = 'libre medio dia';
        self::$free_all_day = 'libre todo dia';
        self::$hospital_half_day = 'hospital medio dia';
        self::$hospital_all_day = 'hospital todo dia';
        self::$missing_half_day = 'ausencia medio dia';
        self::$missing_all_day = 'ausencia todo dia';
        self::$allocated = 'asignado';
        self::$used = 'ha usado';
        self::$used_days = 'ha usado dias';
        self::$days_left = 'dias quedan';
        self::$medical_care = 'licencia medica';
        self::$is_used = 'esta usado';
        self::$used_days_of_medical_care = 'dias usados de licencia medica';
        self::$used_x_of_medical_care = 'usado %s de licencia medica';
        self::$medical_care_days_left = 'resto de licencia medica';
        self::$all_available_days_were_used = 'se acabaron los dias disponibles';
        self::$left = 'quedan';
        self::$x_left = 'quedan %s';
        self::$x_extra = '%s fuera de limito';
        self::$use_rest_of_the_days = 'usar el resto de dias';
        self::$date_is_required = 'Necesito la fecha';
        self::$wrong_date_format = 'Formato de la fecha esta incorrecto';
        self::$dates_range_is_required = 'Necesito las fechas del inicio y fin';
        self::$wrong_start_date = 'Fecha inicial esta incorrecta';
        self::$wrong_end_date = 'Fecha final esta incorrecta';
        self::$wrong_date_range = 'Las fechas estan incorrectas';
        self::$dates_conflict = 'Conflicto de las fechas';
        self::$maximum_dates_range_exceeded = 'Maximo rango de fechas fue excedido';
        self::$wrong_day_type = 'Tipo de dia esta incorrecto';
        self::$use_full_day_only_for_multiple_insert = 'Usa todo dia para inserción múltiple';
        self::$days_counter_not_found = 'Contador de dias no fue encontrado';
        self::$no_available_holidays = 'No pude encontrar dias libles disponibles';
        self::$try_less_days_quantity = 'Prueba menor cantidad de dias';
        self::$record_was_found_inside_the_dates_range = 'Un registro fue encontrado dentro de rango de fechas';
        self::$can_not_increment_days_quantity = 'No pude incrementar cantidad de dias';
        self::$year_required = 'Necesito el ano';
        self::$wrong_year_format = 'Formato de ano esta incorrecto';
        self::$monday_2s = 'LU';
        self::$monday = 'Lunes';
        self::$tuesday_2s = 'MA';
        self::$tuesday = 'Martes';
        self::$wednesday_2s = 'MI';
        self::$wednesday = 'Miercoles';
        self::$thursday_2s = 'JU';
        self::$thursday = 'Jueves';
        self::$friday_2s = 'VI';
        self::$friday = 'Viernes';
        self::$saturday_2s = 'SA';
        self::$saturday = 'Sabado';
        self::$sunday_2s = 'DO';
        self::$sunday = 'Domingo';
        self::$january = 'Enero';
        self::$february = 'Febrero';
        self::$march = 'Marzo';
        self::$april = 'Abril';
        self::$may = 'Mayo';
        self::$june = 'Junio';
        self::$july = 'Julio';
        self::$august = 'Agosto';
        self::$september = 'Septiembre';
        self::$october = 'Octubre';
        self::$november = 'Noviembre';
        self::$december = 'Diciembre';
        self::$january2 = 'de Enero';
        self::$february2 = 'de Febrero';
        self::$march2 = 'de Marzo';
        self::$april2 = 'de Abril';
        self::$may2 = 'de Mayo';
        self::$june2 = 'de Junio';
        self::$july2 = 'de Julio';
        self::$august2 = 'de Agosto';
        self::$september2 = 'de Septiembre';
        self::$october2 = 'de Octubre';
        self::$november2 = 'de Noviembre';
        self::$december2 = 'de Diciembre';
        self::$day_month_weekday = '%1$d %2$s, %3$s';
        self::$today = "hoy";
        self::$tomorrow = "manana";
        self::$in_n_days = "en %d dias";
        self::$in_x1_days = "en %d dias";
        self::$in_x234_days = "en %d dias";
        self::$in_half_day = "en medio dia";
        self::$maximum_period_length_x = 'Maximo rango de fechas %s';
    }
    protected static function set_ru()
    {
        self::$day = 'день';
        self::$days = 'дни';
        self::$and_half = 'с половиной';
        self::$n_days = '%d%s дней';
        self::$n_days_x1 = '%d%s день';
        self::$n_days_x234 = '%d%s дня';
        self::$month = 'месяц';
        self::$months = 'месяцы';
        self::$month_day = 'День месяца';
        self::$n_months = '%d%s месяцев';
        self::$n_months_x1 = '%d%s месяц';
        self::$n_months_x234 = '%d%s месяца';
        self::$year = 'год';
        self::$yesterday = 'вчера';
        self::$week = 'неделя';
        self::$week_day = 'день недели';
        self::$full_day = 'весь день';
        self::$first = 'первый';
        self::$second = 'второй';
        self::$last = 'последний';
        self::$every_day = 'каждый день';
        self::$every_x = 'каждые %s';
        self::$half_day = 'пол дня';
        self::$days_quantity = 'количество дней';
        self::$calendar = 'календарь';
        self::$holidays_calendar = 'календарь выходных';
        self::$works = 'работает';
        self::$works_all_day = 'работает весь день';
        self::$is_free = 'свободен';
        self::$free_half_day = 'свободен пол дня';
        self::$free_all_day = 'свободен весь день';
        self::$hospital_half_day = 'больничный пол дня';
        self::$hospital_all_day = 'больничный весь день';
        self::$missing_half_day = 'отсутствие пол дня';
        self::$missing_all_day = 'отсутствие весь день';
        self::$allocated = 'выделено';
        self::$used = 'использовано';
        self::$used_days = 'использовано дней';
        self::$days_left = 'осталось дней';
        self::$medical_care = 'больничные';
        self::$is_used = 'использовано';
        self::$used_days_of_medical_care = 'использовано больничных';
        self::$used_x_of_medical_care = 'использовано %s больничных';
        self::$medical_care_days_left = 'осталось больничных дней';
        self::$all_available_days_were_used = 'все доступные дни были использованы';
        self::$left = 'осталось';
        self::$x_left = 'осталось %s';
        self::$x_extra = '%s сверх лимита';
        self::$use_rest_of_the_days = 'использовать остаток дней';
        self::$date_is_required = 'Укажите дату';
        self::$wrong_date_format = 'Неверный формат даты';
        self::$dates_range_is_required = 'Укажите начальную и конечную дату';
        self::$wrong_start_date = 'Неверная дата начала';
        self::$wrong_end_date = 'Неверная дата окончания';
        self::$wrong_date_range = 'Неверный интервал дат';
        self::$dates_conflict = 'Конфликт дат';
        self::$maximum_dates_range_exceeded = 'Превышен максимальный диапазон дат';
        self::$wrong_day_type = 'Неверный тип дня';
        self::$use_full_day_only_for_multiple_insert = 'Используйте полный день для множественной вставки';
        self::$days_counter_not_found = 'Счетчик дней не найден';
        self::$no_available_holidays = 'Нет доступных выходных';
        self::$try_less_days_quantity = 'Попробуйте меньшее количество дней';
        self::$record_was_found_inside_the_dates_range = 'Найдена запись внутри диапазона дат';
        self::$can_not_increment_days_quantity = 'Не удалось увеличить количество дней';
        self::$year_required = 'Укажите год';
        self::$wrong_year_format = 'Неверный формат года';
        self::$monday_2s = 'ПН';
        self::$monday = 'Понедельник';
        self::$tuesday_2s = 'ВТ';
        self::$tuesday = 'Вторник';
        self::$wednesday_2s = 'СР';
        self::$wednesday = 'Среда';
        self::$thursday_2s = 'ЧТ';
        self::$thursday = 'Четверг';
        self::$friday_2s = 'ПТ';
        self::$friday = 'Пятница';
        self::$saturday_2s = 'СБ';
        self::$saturday = 'Суббота';
        self::$sunday_2s = 'ВС';
        self::$sunday = 'Воскресенье';
        self::$january = 'Январь';
        self::$february = 'Февраль';
        self::$march = 'Март';
        self::$april = 'Апрель';
        self::$may = 'Май';
        self::$june = 'Июнь';
        self::$july = 'Июль';
        self::$august = 'Август';
        self::$september = 'Сентябрь';
        self::$october = 'Октябрь';
        self::$november = 'Ноябрь';
        self::$december = 'Декабрь';
        self::$january2 = 'Января';
        self::$february2 = 'Февраля';
        self::$march2 = 'Марта';
        self::$april2 = 'Апреля';
        self::$may2 = 'Мая';
        self::$june2 = 'Июня';
        self::$july2 = 'Июля';
        self::$august2 = 'Августа';
        self::$september2 = 'Сентября';
        self::$october2 = 'Октября';
        self::$november2 = 'Ноября';
        self::$december2 = 'Декабря';
        self::$day_month_weekday = '%1$d %2$s, %3$s';
        self::$today = "сегодня";
        self::$tomorrow = "завтра";
        self::$in_n_days = "через %d дней";
        self::$in_x1_days = "через %d день";
        self::$in_x234_days = "через %d дня";
        self::$in_half_day = "через пол дня";
        self::$maximum_period_length_x = 'Максимальный диапазон дат %s';
    }
    public static function n_days($n)
    {
        return self::translate_n_x(
            $n,
            self::$day,
            self::$n_days,
            self::$n_days_x1,
            self::$n_days_x234,
            self::$and_half,
            self::$n_days,
            self::$half_day
        );
    }
    public static function n_months($n)
    {
        return self::translate_n_x(
            $n,
            self::$month,
            self::$n_months,
            self::$n_months_x1,
            self::$n_months_x234,
            self::$and_half
        );
    }
    public static function maximum_period_length_n_days($n)
    {
        return sprintf(self::$maximum_period_length_x, self::n_days($n));
    }
    public static function used_n_days_of_medical_care($n)
    {
        return sprintf(self::$used_x_of_medical_care, self::n_days($n));
    }
    public static function allocated_n_days($n)
    {
        return self::$allocated . ' ' . self::n_days($n);
    }
    public static function used_n_days($n)
    {
        return self::$used . ' ' . self::n_days($n);
    }
    public static function n_days_left($n)
    {
        return sprintf(self::$x_left, self::n_days($n));
    }
    public static function n_days_extra($n)
    {
        return sprintf(self::$x_extra, self::n_days($n));
    }
    public static function in_n_days($n)
    {
        return self::translate_n_x(
            $n,
            self::$tomorrow,
            self::$in_n_days,
            self::$in_x1_days,
            self::$in_x234_days,
            self::$and_half,
            self::$today,
            self::$in_half_day
        );
    }
    public static function every_n_days($n)
    {
        if ((int) $n === 1) {
            return self::$every_day;
        }
        return sprintf(self::$every_x, self::n_days($n));
    }
    public static function day_month_and_weekday($date)
    {
        //date('F j, l', $date) --> January 30, Saturday
        $month_key = mb_strtolower(date("M", $date)); //--> jan - dec
        $day = date("d", $date);
        $wd_key = mb_strtolower(date("D", $date)); //--> mon - sun
        $month_dic = array(
            'jan' => self::$january2,
            'feb' => self::$february2,
            'mar' => self::$march2,
            'apr' => self::$april2,
            'may' => self::$may2,
            'jun' => self::$june2,
            'jul' => self::$july2,
            'aug' => self::$august2,
            'sep' => self::$september2,
            'oct' => self::$october2,
            'nov' => self::$november2,
            'dec' => self::$december2,
        );
        $wd_dic = array(
            'mon' => self::$monday,
            'tue' => self::$tuesday,
            'wed' => self::$wednesday,
            'thu' => self::$thursday,
            'fri' => self::$friday,
            'sat' => self::$saturday,
            'sun' => self::$sunday,
        );
        $month_value = $month_dic[$month_key];
        $wd_value = $wd_dic[$wd_key];
        return sprintf(self::$day_month_weekday, $day, $month_value, $wd_value);
    }
}
