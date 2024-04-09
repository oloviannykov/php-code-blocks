<?php
namespace App\Translations;

class ButtonsDictionary extends DictionaryStatic
{
    public static
    $to_add,
    $to_apply,
    $go_back,
    $to_clear,
    $to_close,
    $to_continue,
    $to_cancel,
    $to_create,
    $details,
    $to_edit,
    $less,
    $to_move_up,
    $to_move_down,
    $more,
    $to_open,
    $print,
    $remove_all,
    $to_remove,
    $to_search,
    $to_save,
    $to_update,
    $to_view;
    protected static function set_en()
    {
        self::$to_clear = 'Clear';
        self::$to_close = 'Close';
        self::$to_open = 'Open';
        self::$to_continue = 'Continue';
        self::$to_add = 'Add';
        self::$to_remove = 'Remove';
        self::$to_view = 'View';
        self::$to_search = 'Search';
        self::$to_cancel = 'Cancel';
        self::$to_apply = 'Apply';
        self::$go_back = 'Go back';
        self::$to_create = 'Create';
        self::$to_update = 'Update';
        self::$to_save = 'Save';
        self::$to_edit = 'Edit';
        self::$to_move_up = 'Move up';
        self::$to_move_down = 'Move down';
        self::$remove_all = 'Remove all';
        self::$print = 'Print';
        self::$details = 'Details';
        self::$more = 'more';
        self::$less = 'less';
    }
    protected static function set_es()
    {
        self::$to_clear = 'Vacear';
        self::$to_close = 'Cerrar';
        self::$to_open = 'Abrir';
        self::$to_continue = 'Continuar';
        self::$to_add = 'Agregar';
        self::$to_remove = 'Eliminar';
        self::$to_view = 'Ver';
        self::$to_search = 'Buscar';
        self::$to_cancel = 'Cancelar';
        self::$to_apply = 'Aplicar';
        self::$go_back = 'Regresar';
        self::$to_create = 'Crear';
        self::$to_update = 'Actualizar';
        self::$to_save = 'Guardar';
        self::$to_edit = 'Editar';
        self::$to_move_up = 'Levantar';
        self::$to_move_down = 'Bajar';
        self::$remove_all = 'Borrar todo';
        self::$print = 'Imprimir';
        self::$details = 'Detalles';
        self::$less = 'menos';
        self::$more = 'mas';
    }
    protected static function set_ru()
    {
        self::$to_clear = 'Очистить';
        self::$to_close = 'Закрыть';
        self::$to_open = 'Открыть';
        self::$to_continue = 'Далее';
        self::$to_add = 'Добавить';
        self::$to_remove = 'Удалить';
        self::$to_view = 'Смотреть';
        self::$to_search = 'Искать';
        self::$to_cancel = 'Отменить';
        self::$to_apply = 'Применить';
        self::$go_back = 'Вернуться';
        self::$to_create = 'Создать';
        self::$to_update = 'Обновить';
        self::$to_save = 'Сохранить';
        self::$to_edit = 'Редактировать';
        self::$to_move_up = 'Поднять';
        self::$to_move_down = 'Опустить';
        self::$remove_all = 'Удалить всё';
        self::$print = 'Распечатать';
        self::$details = 'Детальнее';
        self::$less = 'меньше';
        self::$more = 'больше';
    }
}

