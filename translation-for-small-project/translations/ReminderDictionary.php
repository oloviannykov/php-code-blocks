<?php
namespace App\Translations;

use App\Translations\DateDictionary;

class ReminderDictionary extends DictionaryStatic
{
    public static
    $casa_de_campo_permission,
    $custom_event,
    $date,
    $description,
    $defaults,
    $default_language,
    $event_type,
    $end_month_and_day,
    $every_x_days_reminders,
    $employee_passport_expiration,
    $employee_cdc_permission_expiration,
    $employee_birth_day,
    $employee_credit_expiration,
    $employee_health_insurance_payment,
    $health_insurance,
    $is_finished,
    $language_and_receiver_below_replace_defaults_above,
    $mark_selected_as_read,
    $message_language,
    $message_receiver,
    $month_day,
    $month_and_day,
    $monthly_reminders,
    $other_employee_events,
    $other_events,
    $passport,
    $read,
    $report_receivers,
    $status,
    $summary,
    $step_in_days,
    $start_month_and_day,
    $task,
    $unread,
    $week_day,
    $weekly_reminders,
    $yearly_reminders;
    private static
    $xs_xx,
    $x_expires_xx,
    $x_has_expired,
    $x_has_birth_day_xx,
    $x_has_birth_day;
    protected static function set_en()
    {
        self::$custom_event = 'Custom event';
        self::$casa_de_campo_permission = 'Casa de Campo permission';
        self::$date = 'Date';
        self::$description = 'Description';
        self::$defaults = 'Defaults';
        self::$default_language = 'Default language';
        self::$event_type = 'Event type';
        self::$every_x_days_reminders = 'Every x days reminders';
        self::$employee_passport_expiration = 'Employee PASSPORT expiration';
        self::$employee_cdc_permission_expiration = 'Employee Casa de Campo PERMISSION expiration';
        self::$employee_birth_day = 'Employee BIRTH DAY';
        self::$employee_credit_expiration = 'Employee CREDIT expiration';
        self::$employee_health_insurance_payment = 'Employee HEALTH INSURANCE payment';
        self::$end_month_and_day = 'End month and day';
        self::$health_insurance = 'health insurance';
        self::$is_finished = 'is finished';
        self::$language_and_receiver_below_replace_defaults_above = 'Language and receiver'
            . ' specified below will replace defaults specified above';
        self::$mark_selected_as_read = 'Mark selected as read';
        self::$message_language = 'Message language';
        self::$message_receiver = 'Message receiver';
        self::$month_day = 'Month day';
        self::$month_and_day = 'Month and day';
        self::$monthly_reminders = 'Monthly reminders';
        self::$other_employee_events = 'Other employee events';
        self::$other_events = 'Other events';
        self::$passport = "passport";
        self::$read = 'read';
        self::$report_receivers = 'Report receivers';
        self::$summary = 'Summary';
        self::$step_in_days = 'Step in days';
        self::$start_month_and_day = 'Start month and day';
        self::$status = 'Status';
        self::$task = 'Task';
        self::$unread = 'unread';
        self::$week_day = 'Week day';
        self::$weekly_reminders = 'Weekly reminders';
        self::$xs_xx = '%s\'s %s';
        self::$x_expires_xx = '%s expires %s';
        self::$x_has_expired = '%s has expired';
        self::$x_has_birth_day_xx = '%s has birth day %s';
        self::$x_has_birth_day = '%s has birth day';
        self::$yearly_reminders = 'Yearly reminders';
    }
    protected static function set_es()
    {
        self::$custom_event = 'Evento personalizado';
        self::$casa_de_campo_permission = 'permiso de Casa de Campo';
        self::$default_language = 'Lengua por omisión';
        self::$date = 'Fecha';
        self::$description = 'Descripcion';
        self::$defaults = 'Omisiones';
        self::$end_month_and_day = 'Mes y dia final';
        self::$event_type = 'Tipo de evento';
        self::$employee_passport_expiration = 'Vencimiento de PASOPORTE del empleado';
        self::$employee_cdc_permission_expiration = 'Vencimiento de PERMISO de Casa de Campo del empleado';
        self::$employee_birth_day = 'CUMPLEANOS del empleado';
        self::$employee_credit_expiration = 'Vencimiento de CREDITO del empleado';
        self::$employee_health_insurance_payment = 'Pago del SEGURO MEDICO del empleado';
        self::$every_x_days_reminders = 'Recordatorio cada X dias';
        self::$health_insurance = 'seguro medico';
        self::$is_finished = 'esta terminado';
        self::$language_and_receiver_below_replace_defaults_above = 'Lengua y receptor'
            . ' specificados abajo reemplaza omisiones specificados arriba';
        self::$mark_selected_as_read = 'Marcar elegidos como leido';
        self::$month_day = 'Dia de mes';
        self::$month_and_day = 'Mes y dia';
        self::$monthly_reminders = 'Recordatorio mensual';
        self::$message_language = 'Lengua de mensaje';
        self::$message_receiver = 'Recipiente';
        self::$other_employee_events = 'Otros eventos del empleado';
        self::$other_events = 'Otros eventos';
        self::$passport = "pasoporte";
        self::$read = 'leido';
        self::$report_receivers = 'Receptores de reporte';
        self::$step_in_days = 'Paso en dias';
        self::$start_month_and_day = 'Mes y dia inicial';
        self::$summary = 'Resumen';
        self::$status = 'Estado';
        self::$task = 'Tarea';
        self::$unread = 'no leido';
        self::$week_day = 'Dia de semana';
        self::$weekly_reminders = 'Recordatorio semanal';
        self::$xs_xx = '%2$s de %1$s';
        self::$x_expires_xx = '%s se vence %s';
        self::$x_has_expired = '%s esta vencido';
        self::$x_has_birth_day_xx = '%s tiene cumpleanos %s';
        self::$x_has_birth_day = '%s tiene cumpleanos';
        self::$yearly_reminders = 'Recordatorio annual';
    }
    protected static function set_ru()
    {
        self::$custom_event = 'Пользовательское событие';
        self::$casa_de_campo_permission = 'пропуск в Каса-де-кампо';
        self::$date = 'Дата';
        self::$description = 'Описание';
        self::$defaults = 'Умолчания';
        self::$default_language = 'Язык по умолчанию';
        self::$event_type = 'Тип события';
        self::$end_month_and_day = 'Конечный месяц и день';
        self::$employee_passport_expiration = 'Истекает срок ПАССПОРТА работника';
        self::$employee_cdc_permission_expiration = 'Истекает срок ПРОПУСКА в Каса-де-кампо';
        self::$employee_birth_day = 'ДЕНЬ РОЖДЕНИЯ работника';
        self::$employee_credit_expiration = 'Истекает КРЕДИТ работника';
        self::$employee_health_insurance_payment = 'Платеж МЕД. СТРАХОВКИ работника';
        self::$every_x_days_reminders = 'Напоминания каждые Х дней';
        self::$health_insurance = 'мед. страховка';
        self::$is_finished = 'завершено';
        self::$language_and_receiver_below_replace_defaults_above = 'Язык и получатель,'
            . ' указанные ниже, заменят умолчания, указанные выше';
        self::$mark_selected_as_read = 'Пометить выбранное как прочитанное';
        self::$message_language = 'Язык сообщения';
        self::$message_receiver = 'Получатель';
        self::$month_day = 'День месяца';
        self::$month_and_day = 'Месяц и день';
        self::$monthly_reminders = 'Ежемесячные напоминания';
        self::$other_employee_events = 'Другие события работника';
        self::$other_events = 'Прочие события';
        self::$passport = 'пасспорт';
        self::$read = 'прочитано';
        self::$report_receivers = 'Получатели отчета';
        self::$status = 'Статус';
        self::$summary = 'Сводка';
        self::$step_in_days = 'Шаг в днях';
        self::$start_month_and_day = 'Начальный месяц и день';
        self::$task = 'Задача';
        self::$unread = 'не прочитано';
        self::$week_day = 'День недели';
        self::$weekly_reminders = 'Еженедельные напоминания';
        self::$xs_xx = '%2$s %1$s';
        self::$x_expires_xx = '%s истекает %s';
        self::$x_has_expired = '%s истек';
        self::$x_has_birth_day_xx = 'У %s будет день рождения %s';
        self::$x_has_birth_day = 'У %s день рождения';
        self::$yearly_reminders = 'Ежегодные напоминания';
    }
    public static function xs_xx($x, $xx)
    {
        return sprintf(self::$xs_xx, $x, $xx);
    }
    public static function x_expires_xx($x, $xx)
    {
        return sprintf(self::$x_expires_xx, $x, $xx);
    }
    public static function x_expires_in_n_days($x, $n)
    {
        $lang = self::$loaded[self::class];
        DateDictionary::load($lang);
        return self::x_expires_xx($x, DateDictionary::in_n_days($n));
    }
    public static function x_has_expired($x)
    {
        return sprintf(self::$x_has_expired, $x);
    }
    public static function x_has_birth_day($x)
    {
        return sprintf(self::$x_has_birth_day, $x);
    }
    public static function x_has_birth_day_in_n_days($x, $n)
    {
        $lang = self::$loaded[self::class];
        DateDictionary::load($lang);
        return sprintf(
            self::$x_has_birth_day_xx,
            $x,
            DateDictionary::in_n_days($n)
        );
    }
    public static function task_in_n_days($n)
    {
        $lang = self::$loaded[self::class];
        DateDictionary::load($lang);
        return self::$task . ' ' . DateDictionary::in_n_days($n);
    }
}
