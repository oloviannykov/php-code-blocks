<?php
namespace App\Translations;

class FieldDictionary extends DictionaryStatic
{
    public static
    $any,
    $action,
    $actions,
    $all,
    $administrator,
    $comment,
    $content,
    $description,
    $date,
    $dates_range,
    $details,
    $employee,
    $end_date,
    $finish,
    $file,
    $field,
    $from_date,
    $first_dates_range,
    $function,
    $key_word,
    $manual_input,
    $number,
    $name,
    $no,
    $not_selected,
    $no_data,
    $page,
    $report,
    $state,
    $start,
    $select_all,
    $second_dates_range,
    $server_time,
    $start_time,
    $summary,
    $type,
    $task,
    $title,
    $total,
    $turned_on,
    $turned_off,
    $user,
    $until_date,
    $unknown,
    $value,
    $who,
    $was_printed,
    $yes;
    protected static
    $n_m_symbols;
    protected static function set_en()
    {
        self::$administrator = 'Administrator';
        self::$all = 'All';
        self::$any = 'any';
        self::$action = 'Action';
        self::$actions = 'Actions';
        self::$comment = 'Comment';
        self::$content = 'Content';
        self::$date = 'Date';
        self::$description = 'Description';
        self::$dates_range = 'Dates range';
        self::$details = 'Details';
        self::$employee = 'Employee';
        self::$end_date = 'End date';
        self::$finish = 'Finish';
        self::$file = 'File';
        self::$field = 'Field';
        self::$from_date = 'From date';
        self::$first_dates_range = 'First dates range';
        self::$function = 'Function';
        self::$key_word = 'Key word';
        self::$manual_input = 'manual input';
        self::$number = 'Number';
        self::$name = 'Name';
        self::$no = 'No';
        self::$not_selected = 'not selected';
        self::$no_data = 'no data';
        self::$n_m_symbols = '%d-%d symbols';
        self::$page = 'Page';
        self::$report = 'Report';
        self::$second_dates_range = 'Second dates range';
        self::$state = 'State';
        self::$start = 'Start';
        self::$select_all = 'select all';
        self::$server_time = 'server time';
        self::$start_time = 'Start time';
        self::$summary = 'Summary';
        self::$type = 'Type';
        self::$task = 'Task';
        self::$total = 'Total';
        self::$title = 'Title';
        self::$turned_on = 'Turned ON';
        self::$turned_off = 'Turned OFF';
        self::$until_date = 'Until date';
        self::$user = 'User';
        self::$unknown = 'unknown';
        self::$value = 'Value';
        self::$who = 'Who';
        self::$was_printed = 'Printed';
        self::$yes = 'Yes';
    }
    protected static function set_es()
    {
        self::$administrator = 'Administrador';
        self::$any = 'cualquier';
        self::$action = 'Accion';
        self::$actions = 'Acciones';
        self::$all = 'Todos';
        self::$comment = 'Comentario';
        self::$content = 'Contenido';
        self::$date = 'Fecha';
        self::$dates_range = 'Rango de fechas';
        self::$description = 'Descripcion';
        self::$details = 'Detalles';
        self::$employee = 'Empleado';
        self::$end_date = 'Fecha de fin';
        self::$finish = 'Fin';
        self::$file = 'Archivo';
        self::$field = 'Campo';
        self::$function = 'Funccion';
        self::$from_date = 'Fecha inicial';
        self::$first_dates_range = 'Primero rango de fechas';
        self::$key_word = 'Palabra llave';
        self::$manual_input = 'entrada manual';
        self::$number = 'Numero';
        self::$name = 'Nombre';
        self::$no = 'No';
        self::$not_selected = 'no elegido';
        self::$no_data = 'no hay datos';
        self::$n_m_symbols = '%d-%d simbolos';
        self::$page = 'Pagina';
        self::$report = 'Reporte';
        self::$second_dates_range = 'Segundo rango de fechas';
        self::$state = 'Estado';
        self::$start = 'Inicio';
        self::$select_all = 'elegir todos';
        self::$server_time = 'hora de servidor';
        self::$start_time = 'Hora de inicio';
        self::$summary = 'Resumen';
        self::$type = 'Tipo';
        self::$task = 'Tarea';
        self::$total = 'Total';
        self::$title = 'Titulo';
        self::$turned_on = 'Prendido';
        self::$turned_off = 'Apagado';
        self::$until_date = 'Fecha final';
        self::$user = 'Usuario';
        self::$unknown = 'disconocido';
        self::$value = 'Valor';
        self::$who = 'Quien';
        self::$was_printed = 'Imprimido';
        self::$yes = 'Si';
    }
    protected static function set_ru()
    {
        self::$administrator = 'Администратор';
        self::$any = 'любой';
        self::$all = 'Все';
        self::$action = 'Действие';
        self::$actions = 'Действия';
        self::$comment = 'Комментарий';
        self::$content = 'Содержимое';
        self::$date = 'Дата';
        self::$dates_range = 'Период';
        self::$details = 'Детальнее';
        self::$description = 'Описание';
        self::$employee = 'Работник';
        self::$end_date = 'Дата завершения';
        self::$finish = 'Конец';
        self::$file = 'Файл';
        self::$field = 'Поле';
        self::$function = 'Функция';
        self::$from_date = 'Начальная дата';
        self::$first_dates_range = 'Первый диапазон дат';
        self::$key_word = 'Ключевое слово';
        self::$manual_input = 'ручной ввод';
        self::$name = 'Имя';
        self::$number = 'Номер';
        self::$no = 'Нет';
        self::$n_m_symbols = '%d-%d символов';
        self::$not_selected = 'не выбрано';
        self::$no_data = 'нет данных';
        self::$page = 'Страница';
        self::$report = 'Отчет';
        self::$second_dates_range = 'Второй диапазон дат';
        self::$server_time = 'время сервера';
        self::$state = 'Состояние';
        self::$start = 'Начало';
        self::$select_all = 'выделить все';
        self::$start_time = 'Время начала';
        self::$summary = 'Резюме';
        self::$type = 'Тип';
        self::$task = 'Задача';
        self::$total = 'Итого';
        self::$title = 'Заголовок';
        self::$turned_on = 'ВКЛючен';
        self::$turned_off = 'ОТКЛючен';
        self::$user = 'Пользователь';
        self::$until_date = 'Конечная дата';
        self::$unknown = 'неизвестно';
        self::$value = 'Значение';
        self::$who = 'Кто';
        self::$was_printed = 'Распечатано';
        self::$yes = 'Да';
    }
    public static function n_m_symbols($n, $m)
    {
        return sprintf(self::$n_m_symbols, $n, $m);
    }
}
