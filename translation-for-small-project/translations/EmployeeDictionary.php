<?php
namespace App\Translations;

class EmployeeDictionary extends DictionaryStatic
{
    public static
    $admin_panel_access,
    $account,
    $account_number,
    $attachments,
    $attachment_not_found,
    $allowed_actions,
    $allowed_categories,
    $allowed_locations,
    $bank_account,
    $birth_date,
    $bank,
    $categories,
    $casa_de_campo_permission,
    $civil_state,
    $children_quantity,
    $country,
    $contract_number,
    $company_name,
    $contacts,
    $documents,
    $not_hired,
    $employees,
    $employee,
    $employee_profile,
    $end_date,
    $employee_is_required,
    $employee_not_found,
    $email,
    $expires,
    $ended_working,
    $employee_removing_confirmation_multiline_message,
    $employee_general,
    $employee_summary,
    $fired_employees,
    $full_name,
    $female,
    $full_access,
    $failed_to_remove_employee_from_invisibility_log,
    $failed_to_remove_employee_from_work_journal,
    $failed_to_remove_employee_from_holidays_calendar,
    $failed_to_remove_from_task_executors,
    $failed_to_upload_file,
    $failed_to_save_schedule,
    $failed_to_fire_the_employee,
    $failed_to_remove_admin_via_employee_access,
    $first_name,
    $health_insurance,
    $home_address,
    $home_city,
    $is_fired,
    $is_visible_now,
    $is_invisible_now,
    $inventory_access,
    $login,
    $locations,
    $main_language,
    $mobile_phone,
    $married,
    $messengers,
    $male,
    $not_started_to_work,
    $notes,
    $number,
    $new_password,
    $of,
    $payment,
    $passport,
    $profession,
    $professions_list,
    $payment_currency,
    $profession_required,
    $payment_period_in_days,
    $premium_per_month,
    $profession_filter,
    $removed_nothing_from_attachments,
    $some_attachments_are_not_removed,
    $start_date,
    $sex,
    $surname,
    $set_responsible_of_inventory,
    $set_another_executor_of_tasks,
    $summary,
    $started_working,
    $salary_per_month,
    $single,
    $type,
    $to_add_employee,
    $time_format_24hours_colon_60minutes,
    $to_fire,
    $to_edit_work_schedule,
    $upload_file,
    $until,
    $work,
    $working_employees,
    $work_schedule,
    $wrong_time_range,
    $wrong_messengers_list,
    $wrong_inventory_actions_list,
    $wrong_profession_id,
    $work_journal_and_comments,
    $wrong_employee_id,
    $you_can_only_start_or_stop_invisibility;
    protected static function set_en()
    {
        self::$admin_panel_access = 'Admin panel access';
        self::$attachments = 'Attachments';
        self::$attachment_not_found = "Attachment not found";
        self::$account = 'account';
        self::$account_number = 'Account number';
        self::$allowed_actions = 'Allowed actions';
        self::$allowed_categories = 'Allowed categories';
        self::$allowed_locations = 'Allowed locations';
        self::$birth_date = 'Birth day';
        self::$bank_account = 'Bank account';
        self::$bank = 'Bank';
        self::$categories = 'Categories';
        self::$casa_de_campo_permission = 'Casa De Campo permission';
        self::$civil_state = 'Civil state';
        self::$children_quantity = 'Children quantity';
        self::$country = 'Country';
        self::$contract_number = 'Contract number';
        self::$company_name = 'Company name';
        self::$contacts = 'Contacts';
        self::$documents = 'Documents';
        self::$employee_general = 'Employee general';
        self::$employee_summary = 'Employee summary';
        self::$employee = 'Employee';
        self::$end_date = 'End date';
        self::$employee_not_found = 'Employee not found';
        self::$email = 'Email';
        self::$employee_removing_confirmation_multiline_message = "The employee will be removed from task executors,"
            . " his tasks will be deligated to majordomo."
            . "\nStarted processes will be removed too.\nDO YOU STILL WANT TO REMOVE HIM?";
        self::$expires = 'Expires';
        self::$failed_to_remove_employee_from_invisibility_log = 'Failed to remove employee from invisibility log';
        self::$failed_to_remove_employee_from_work_journal = 'Failed to remove employee from work journal';
        self::$failed_to_remove_employee_from_holidays_calendar = 'Failed to remove employee from holidays calendar';
        self::$failed_to_remove_from_task_executors = 'Failed to remove from task executors';
        self::$failed_to_upload_file = 'Failed to upload file';
        self::$failed_to_save_schedule = 'Failed to save schedule';
        self::$failed_to_fire_the_employee = 'Failed to fire the employee';
        self::$failed_to_remove_admin_via_employee_access = 'Failed to remove admin-via-emplyee access';
        self::$first_name = 'First name';
        self::$fired_employees = 'Fired employees';
        self::$full_name = 'Full name';
        self::$profession = 'Profession';
        self::$professions_list = 'Professions list';
        self::$to_add_employee = 'Add employee';
        self::$to_fire = 'Fire';
        self::$employees = 'Employees';
        self::$employee_profile = 'Employee profile';
        self::$ended_working = 'Ended working';
        self::$profession_required = 'Profession is required';
        self::$payment_period_in_days = 'Payment period in days';
        self::$payment_currency = 'Payment currency';
        self::$profession_filter = 'Profession filter';
        self::$not_hired = 'Not hired';
        self::$new_password = 'New password';
        self::$employee_is_required = 'Employee is required';
        self::$surname = 'Surname';
        self::$set_responsible_of_inventory = 'Set responsible of inventory';
        self::$set_another_executor_of_tasks = 'Set another executor of tasks';
        self::$summary = 'Summary';
        self::$home_city = 'Home city';
        self::$sex = 'Sex';
        self::$main_language = 'Main language';
        self::$home_address = 'Home address';
        self::$mobile_phone = 'Mobile phone';
        self::$notes = 'Notes';
        self::$passport = 'Passport';
        self::$type = 'Type';
        self::$number = 'Number';
        self::$health_insurance = 'Health insurance';
        self::$time_format_24hours_colon_60minutes = "Time format: hours 01-23, colon ':', minutes 00-59";
        self::$to_edit_work_schedule = 'Edit work schedule';
        self::$removed_nothing_from_attachments = "Removed nothing from attachments";
        self::$some_attachments_are_not_removed = "Some attachments are not removed";
        self::$start_date = 'Start date';
        self::$is_fired = 'Is fired';
        self::$is_visible_now = 'is visible now';
        self::$is_invisible_now = 'is INvisible now';
        self::$payment = 'Payment';
        self::$of = 'of';
        self::$started_working = 'Started working';
        self::$salary_per_month = 'Salary per month';
        self::$premium_per_month = 'Premium per month';
        self::$not_started_to_work = 'Not started to work';
        self::$messengers = 'Messengers';
        self::$login = 'Login';
        self::$full_access = 'Full access';
        self::$inventory_access = 'Inventory access';
        self::$locations = 'Locations';
        self::$male = 'male';
        self::$female = 'female';
        self::$single = 'single';
        self::$married = 'married';
        self::$upload_file = 'Upload file';
        self::$until = 'until';
        self::$work = 'Work';
        self::$working_employees = 'Working employees';
        self::$work_schedule = 'Work schedule';
        self::$wrong_profession_id = 'Wrong profession ID';
        self::$work_journal_and_comments = 'Work journal and comments';
        self::$wrong_employee_id = 'Wrong employee id';
        self::$wrong_time_range = 'Wrong time range';
        self::$wrong_messengers_list = "Wrong messengers list";
        self::$wrong_inventory_actions_list = 'Wrong inventory actions list';
        self::$you_can_only_start_or_stop_invisibility = 'You can only start or stop invisibility';
    }
    protected static function set_es()
    {
        self::$admin_panel_access = 'Acceso a panel de admin';
        self::$account = 'Cuenta';
        self::$account_number = 'Numero de cuenta';
        self::$attachments = 'Adjuntos';
        self::$attachment_not_found = "Adjunto no fue encontrado";
        self::$allowed_actions = 'Acciones permitidas';
        self::$allowed_categories = 'Categorias permitidas';
        self::$allowed_locations = 'Ubicaciones permitidas';
        self::$birth_date = 'Fecha de nacimiento';
        self::$bank = 'Banco';
        self::$bank_account = 'Cuenta en banco';
        self::$casa_de_campo_permission = 'Permiso de Casa De Campo';
        self::$civil_state = 'Estado civil';
        self::$children_quantity = 'Cantidad de hijos';
        self::$country = 'Pais';
        self::$contract_number = 'Numero de contracto';
        self::$company_name = 'Nombre de compania';
        self::$contacts = 'Contactos';
        self::$documents = 'Documentos';
        self::$employee_general = 'General sobre empleado';
        self::$end_date = 'Fecha de terminacion';
        self::$employees = 'Los empleados';
        self::$employee_profile = 'Perfil del empleado';
        self::$employee_summary = 'Resumen de empleado';
        self::$employee_removing_confirmation_multiline_message = "El empleado sera eliminado de executores de tareas,"
            . " sus tareas seran delegidos al mayordomo."
            . "\nProcesos empezados seran eliminados tambien.\nTODAVIA QUIERES ELIMINARLO?";
        self::$employee = 'Empleado';
        self::$employee_is_required = 'Necesito un empleado';
        self::$employee_not_found = 'El empleado no fue encontrado';
        self::$email = 'Correo electronico';
        self::$expires = 'Expira';
        self::$full_access = 'Acceso completo';
        self::$female = 'femenino';
        self::$failed_to_remove_employee_from_invisibility_log = 'Todos empleados no estan disponibles';
        self::$failed_to_remove_employee_from_work_journal = 'No pude eliminar el empleado del diario de trabajo';
        self::$failed_to_remove_employee_from_holidays_calendar = 'No pude eliminar el empleado del calendario de vacaciones';
        self::$failed_to_remove_from_task_executors = 'No pude eliminar del executores de tarea';
        self::$failed_to_upload_file = 'No pude cargar archivo';
        self::$failed_to_save_schedule = 'No pude guardar horario';
        self::$failed_to_fire_the_employee = 'No pude dispedir el empleado';
        self::$failed_to_remove_admin_via_employee_access = 'No pude eliminar acceso admin-por-empleado';
        self::$first_name = 'Nombre';
        self::$fired_employees = 'Empleados despedidos';
        self::$full_name = 'Nombre completo';
        self::$health_insurance = 'Seguro medico';
        self::$home_address = 'Direccion de casa';
        self::$home_city = 'Pueblo residencial';
        self::$is_fired = 'Esta despedido(a)';
        self::$profession = 'Profecion';
        self::$professions_list = 'Lista de profeciones';
        self::$payment_currency = 'Moneda de pago';
        self::$payment_period_in_days = 'Periodo de pago en dias';
        self::$to_add_employee = 'Agregar un empleado';
        self::$to_fire = 'Dispedir';
        self::$profession_required = 'Necesito profecion';
        self::$upload_file = 'Cargar archivo';
        self::$profession_filter = 'Filtro de profecion';
        self::$not_hired = 'No contratado';
        self::$new_password = 'Nueva contraseña';
        self::$surname = 'Apellido';
        self::$set_responsible_of_inventory = 'Hacer encargado de inventario';
        self::$set_another_executor_of_tasks = 'Poner otro ejecutor de tareas';
        self::$summary = 'Resumen';
        self::$sex = 'Sexo';
        self::$main_language = 'Idioma general';
        self::$mobile_phone = 'Numero de celular';
        self::$notes = 'Notas';
        self::$passport = 'Pasoporte';
        self::$type = 'Tipo';
        self::$number = 'Numero';
        self::$time_format_24hours_colon_60minutes = "Formato de tiempo: horas 01-23, colon ':', minutas 00-59";
        self::$to_edit_work_schedule = 'Editar horario de trabajo';
        self::$removed_nothing_from_attachments = "No pude borrar ningun adjunto";
        self::$some_attachments_are_not_removed = "No pude borrar algunos adjuntos";
        self::$start_date = 'Fecha de inicio';
        self::$is_invisible_now = 'ya esta INvisible';
        self::$is_visible_now = 'ya esta visible';
        self::$payment = 'Pago';
        self::$of = 'de';
        self::$started_working = 'Ha iniciado trbajar';
        self::$ended_working = 'Termino trabajar';
        self::$salary_per_month = 'Salario mensual';
        self::$premium_per_month = 'Premio mensual';
        self::$not_started_to_work = 'No ha empezado trabajar';
        self::$messengers = 'Mensajeros';
        self::$login = 'El login';
        self::$inventory_access = 'Acceso a inventario';
        self::$categories = 'Categorias';
        self::$locations = 'Ubicaciones';
        self::$male = 'masculino';
        self::$single = 'soltero(a)';
        self::$married = 'casado(a)';
        self::$until = 'hasta';
        self::$work = 'Trabajo';
        self::$working_employees = 'Empleados contratados';
        self::$wrong_profession_id = 'Identificacion de profecion esta incorrecta';
        self::$wrong_employee_id = 'ID de empleado esta incorrecto';
        self::$work_journal_and_comments = 'Diario de trabajo y comentarios';
        self::$work_schedule = 'Horario de trabajo';
        self::$wrong_time_range = 'Rango de horas esta incorrecto';
        self::$wrong_messengers_list = "La lista de mensajeros esta incorrecta";
        self::$wrong_inventory_actions_list = 'Lista de acciones de inventario esta incorrecta';
        self::$you_can_only_start_or_stop_invisibility = 'Se puede solo prender o apagar invisibilidad';
    }
    protected static function set_ru()
    {
        self::$account_number = 'Номер счет';
        self::$attachments = 'Приложения';
        self::$attachment_not_found = "Приложение не найдено";
        self::$admin_panel_access = 'Доступ к панели админа';
        self::$allowed_actions = 'Разрешенные действия';
        self::$allowed_categories = 'Разрешенные категории';
        self::$allowed_locations = 'Разрешенные расположения';
        self::$account = 'Счет';
        self::$bank_account = 'Банковский счет';
        self::$birth_date = 'Дата рождения';
        self::$bank = 'Банк';
        self::$categories = 'Категории';
        self::$casa_de_campo_permission = 'Пропуск de Casa De Campo';
        self::$country = 'Страна';
        self::$contract_number = 'Номер контракта';
        self::$company_name = 'Имя компании';
        self::$civil_state = 'Семейный статус';
        self::$children_quantity = 'Кол-во детей';
        self::$contacts = 'Контакты';
        self::$employee_general = 'Основное о работнике';
        self::$end_date = 'Дата окончания';
        self::$employee = 'Работник';
        self::$employee_summary = 'Сводка по работнику';
        self::$employees = 'Работники';
        self::$employee_profile = 'Профиль работника';
        self::$employee_is_required = 'Укажите работника';
        self::$employee_not_found = 'Работник не найден';
        self::$employee_removing_confirmation_multiline_message = "Работник будет удален из исполнителей задач"
            . " и его задачи будут делегированы администратору."
            . "\nНачатые процессы будут тоже удалены.\nВСЁ ЕЩЕ ХОТИТЕ УДАЛИТЬ ЕГО?";
        self::$email = 'Электронная почта';
        self::$expires = 'Истекает';
        self::$ended_working = 'Закончил(а) работу';
        self::$full_name = 'Полное имя';
        self::$failed_to_remove_employee_from_invisibility_log = 'Не удалось удалить работника из журнала невидимости';
        self::$failed_to_remove_employee_from_work_journal = 'Не удалось удалить работника из журнала работы';
        self::$failed_to_remove_employee_from_holidays_calendar = 'Не удалось удалить работника из календаря выходных';
        self::$failed_to_remove_from_task_executors = 'Не удалось удалить из исполнителей задачи';
        self::$failed_to_upload_file = 'Не удалось загрузить файл';
        self::$failed_to_save_schedule = 'Не удалось сохранить расписание';
        self::$failed_to_fire_the_employee = 'Не удалось уволить работника';
        self::$failed_to_remove_admin_via_employee_access = 'Не удалось удалить доступ админ-через-работника';
        self::$first_name = 'Имя';
        self::$fired_employees = 'Уволенные';
        self::$is_fired = 'Уволен(а)';
        self::$profession = 'Профессия';
        self::$professions_list = 'Список профессий';
        self::$payment_currency = 'Валюта платежа';
        self::$profession_required = 'Укажите профессию';
        self::$profession_filter = 'Фильтр профессии';
        self::$payment_period_in_days = 'Период платежа в днях';
        self::$to_add_employee = 'Добавить работника';
        self::$to_fire = 'Уволить';
        self::$upload_file = 'Загрузить файл';
        self::$wrong_profession_id = 'Неверный идентификатор профессии';
        self::$not_hired = 'Не нанят(а)';
        self::$new_password = 'Новый пароль';
        self::$wrong_employee_id = 'Неверный ID работника';
        self::$work_journal_and_comments = 'Журнал работы и комментарии';
        self::$surname = 'Фамилия';
        self::$set_responsible_of_inventory = 'Указать ответственного за инвентарь';
        self::$set_another_executor_of_tasks = 'Указать другого исполнителя задач';
        self::$summary = 'Сводка';
        self::$home_city = 'Город проживания';
        self::$sex = 'Пол';
        self::$main_language = 'Основной язык';
        self::$home_address = 'Домашний адрес';
        self::$mobile_phone = 'Номер мобильного';
        self::$notes = 'Заметки';
        self::$documents = 'Документы';
        self::$passport = 'Пасспорт';
        self::$type = 'Тип';
        self::$number = 'Номер';
        self::$health_insurance = 'Мед. страховка';
        self::$work_schedule = 'Расписание работы';
        self::$time_format_24hours_colon_60minutes = "Формат времени: часы 01-23, двоеточие ':', минуты 00-59";
        self::$to_edit_work_schedule = 'Редактировать расписание работы';
        self::$wrong_time_range = 'Неверный временной диапазон';
        self::$wrong_messengers_list = "Неверный список мессенджеров";
        self::$wrong_inventory_actions_list = 'Неверный список инвентарных действий';
        self::$you_can_only_start_or_stop_invisibility = 'Можно только включить и отключить режим невидимки';
        self::$removed_nothing_from_attachments = "Не удалось удалить приложения";
        self::$some_attachments_are_not_removed = "Некоторые приложения не удалось удалить";
        self::$start_date = 'Дата начала';
        self::$is_invisible_now = 'теперь НЕвидимый';
        self::$is_visible_now = 'теперь видимый';
        self::$payment = 'Оплата';
        self::$until = 'до';
        self::$of = 'от';
        self::$work = 'Работа';
        self::$working_employees = 'Работающие';
        self::$started_working = 'Начал(а) работать';
        self::$salary_per_month = 'Месячная зар.плата';
        self::$premium_per_month = 'Месячная премия';
        self::$not_started_to_work = 'Не начал(а) работать';
        self::$messengers = 'Мессенджеры';
        self::$login = 'Логин';
        self::$full_access = 'Полный доступ';
        self::$inventory_access = 'Доступ к инвентарю';
        self::$locations = 'Расположения';
        self::$male = 'мужской';
        self::$female = 'женский';
        self::$single = 'неженат/незамужем';
        self::$married = 'женат/замужем';
    }
}