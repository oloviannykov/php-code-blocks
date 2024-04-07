<?php

use ValueBoolean; //look at ReturnTypes.php
use Path\To\OpenHABImportedDevices;
use Path\To\OpenHAB;
use Path\To\OpenHABItem;

//use App\Translations\DeviceDictionary;

class DeviceModel
{
    const
        SETTINGS__OH_KEY = 'openhab',
        SETTINGS__OH_SERVER_URL = 'server_url',
        SETTINGS__OH_LOGIN = 'login',
        SETTINGS__OH_PASSWORD = 'password',
        SETTINGS__OH_SITEMAP = 'sitemap';
    const
        FILTER__TYPE = 'type',
        FILTER__LABEL_OR_NAME = 'label_or_name',
        FILTER__STATE_CHANGE = 'state_change',
        FILTER__DEVICE_ID = 'device_id',
        FILTER__SYSTEM_STATUS = 'system_status',
        FILTER__TASK_ID = 'task_id';

    const
        TYPE__SWITCH = 'Switch',
        TYPE__DIMMER = 'Dimmer',
        TYPE__NUMBER = 'Number',
        TYPE__STRING = 'String',
        TYPE__GROUP = 'Group'
    ;

    const
        SYSTEM_STATUS__ENABLED = 'enabled',
        SYSTEM_STATUS__DISABLED = 'disabled';

    public static function get_openhab_settings(): array
    {
        $data = []; //todo: load settings here
        return [
            self::SETTINGS__OH_SERVER_URL => empty($data[self::SETTINGS__OH_SERVER_URL])
                ? '' : $data[self::SETTINGS__OH_SERVER_URL],
            self::SETTINGS__OH_LOGIN => empty($data[self::SETTINGS__OH_LOGIN])
                ? '' : $data[self::SETTINGS__OH_LOGIN],
            self::SETTINGS__OH_PASSWORD => empty($data[self::SETTINGS__OH_PASSWORD])
                ? '' : $data[self::SETTINGS__OH_PASSWORD],
            self::SETTINGS__OH_SITEMAP => empty($data[self::SETTINGS__OH_SITEMAP])
                ? '' : $data[self::SETTINGS__OH_SITEMAP],
        ];
    }

    public static function validate_openhab_settings($data): string
    {
        $errors = [];
        DeviceDictionary::load();
        if (empty($data[self::SETTINGS__OH_SERVER_URL])) {
            $errors[] = DeviceDictionary::$server_url_required;
        }
        if (empty($data[self::SETTINGS__OH_LOGIN])) {
            $errors[] = DeviceDictionary::$api_login_required;
        }
        if (empty($data[self::SETTINGS__OH_PASSWORD])) {
            $errors[] = DeviceDictionary::$api_password_required;
        }
        if ($errors) {
            return count($errors) > 2 ? DeviceDictionary::$complete_settings : implode('; ', $errors);
        }
        return '';
    }

    public static function get_openhab_sitemap_domain(): array
    {
        $oh = new OpenHAB();
        if (!$oh->can_connect()) {
            return [];
        }
        $list = $oh->getSitemaps();
        if (empty($list['error'])) {
            return $list;
        }
        errors_collect(__METHOD__, ['getSitemaps error' => $list['error']]);
        return [];
    }

    /* //todo: use your implementation
    public static function set_openhab_sitemap($sitemap): ValueBoolean {
        DeviceDictionary::load();
        $r = new ValueBoolean;
        if(empty($sitemap) || !is_string($sitemap)) {
            errors_collect(__METHOD__, ['wrong sitemap name' => $sitemap]);
            return $r->error(DeviceDictionary::$wrong_sitemap_name, 'wrong_sitemap_name');
        }
        $settings = self::get_openhab_settings();
        if(empty($settings[self::SETTINGS__OH_SERVER_URL])) {
            return $r->error(DeviceDictionary::$connection_settings_required, 'connection_settings_required');
        }
        $settings[self::SETTINGS__OH_SITEMAP] = $sitemap;
        $saved = SettingsRegistry::set_by_key(self::SETTINGS__OH_KEY, $settings);
        if($saved) {
            return $r->success();
        }
        return $r->error(DeviceDictionary::$settings_registry_error, 'settings_registry_error');
    }*/

    public static function get_default_filter(): array
    {
        return [
            self::FILTER__TYPE => self::TYPE__SWITCH
        ];
    }

    public static function type_domain(): array
    {
        return [
            self::TYPE__SWITCH => 'Switcher',
            self::TYPE__DIMMER => 'Dimmer',
            self::TYPE__NUMBER => 'Number',
            self::TYPE__STRING => 'String',
            self::TYPE__GROUP => 'Group'
        ];
    }

    public static function system_status_domain()
    {
        DeviceDictionary::load();
        return [
            self::SYSTEM_STATUS__ENABLED => DeviceDictionary::$enabled,
            self::SYSTEM_STATUS__DISABLED => DeviceDictionary::$disabled,
        ];
    }

    public static function imported_device_id_domain(): array
    {
        return []; //OpenHABImportedDevices::device_id_domain();
    }

    public static function get_filtered_list($filter, $task_ids_by_device_id = []): array
    {
        $oh = new OpenHAB();
        if (!$oh->can_connect()) {
            errors_collect('DeviceModel.get_filtered_list', 'Trying to get OpenHAB items without connection settings');
            return [];
        }
        $items = [];
        $log = ['filter' => $filter];
        $state_change_filter = empty($filter[self::FILTER__STATE_CHANGE])
            ? 0 : (int) $filter[self::FILTER__STATE_CHANGE];

        if (!empty($filter[self::FILTER__DEVICE_ID])) {
            //todo: load device instance by id from imported, then set name filter
            //$filter[self::FILTER__LABEL_OR_NAME] = $device->value->get_name();
        }

        if (!empty($filter[self::FILTER__LABEL_OR_NAME])) {
            //try to get one by name
            $item = $oh->getItem($filter[self::FILTER__LABEL_OR_NAME], true);
            if (is_a($item, OpenHABItem::class)) {
                $items = [$item->get_name() => $item];
            } else {
                $log["OpenHAB.getItem"] = $items;
                $items = [];
            }
        }
        if (empty($items)) { //not found by name so get list
            $items = $oh->getItemsList($filter, true);
        }
        if (!empty($items['error']) || empty($items) || !is_array($items)) {
            $log["OpenHAB.getItemsList"] = $items;
            return [];
        }

        $imported_devices_by_name = [];//ImportedDevices::get_list($filter, OpenHABItem::FIELD__NAME);

        /** @var $item OpenHABItem */
        foreach ($items as $itemName => &$item) {
            //LABEL OR NAME FILTER
            if (!empty($filter[self::FILTER__LABEL_OR_NAME])) {
                $name_and_label = mb_strtolower($item->get_name() . ';' . $item->get_label());
                $needle = mb_strtolower($filter[self::FILTER__LABEL_OR_NAME]);
                if (mb_strpos($name_and_label, $needle) === false) {
                    unset($items[$itemName]);
                    continue;
                }
            }

            if (isset($imported_devices_by_name[$item->get_name()])) {
                $device_id = $imported_devices_by_name[$item->get_name()]->get_id();
                $import_date = $imported_devices_by_name[$item->get_name()]->get_import_date();
                $task_ids = empty($task_ids_by_device_id[$device_id]) ? [] : $task_ids_by_device_id[$device_id];
                $item->set_id($device_id);
                $item->set_import_date($import_date);
                $item->set_connected_tasks($task_ids);
            }

            //SYSTEM STATUS FILTER
            if (!empty($filter[self::FILTER__SYSTEM_STATUS])) {
                if ($filter[self::FILTER__SYSTEM_STATUS] === self::SYSTEM_STATUS__ENABLED) {
                    $needle = true;
                } elseif ($filter[self::FILTER__SYSTEM_STATUS] === self::SYSTEM_STATUS__DISABLED) {
                    $needle = false;
                } else {
                    $needle = null;
                }
                if (!is_null($needle) && $item->is_imported() !== $needle) {
                    unset($items[$itemName]);
                    continue;
                }
            }

            //TASK ID FILTER
            if (!empty($filter[self::FILTER__TASK_ID])) {
                if (
                    !in_array(
                        $filter[self::FILTER__TASK_ID],
                        $item->get_connected_tasks()
                    )
                ) {
                    unset($items[$itemName]);
                    continue;
                }
            }
        }
        unset($item);

        //add data from sitemap
        $settings = self::get_openhab_settings();
        if (empty($settings[self::SETTINGS__OH_SITEMAP])) {
            return $items;
        }
        $map = $oh->getSitemapByName($settings[self::SETTINGS__OH_SITEMAP]);
        $readonly_widgets = [
            'Chart',
            'Text'
        ];
        foreach ($map as $itemName => $data) {
            if (!isset($items[$itemName])) {
                continue;
            }
            if (!empty($data['label'])) {
                $items[$itemName]->set_label($data['label']);
            }
            if (!empty($data['max'])) {
                $items[$itemName]->set_minvalue($data['min']);
                $items[$itemName]->set_maxvalue($data['max']);
            }
            if (!empty($data['opt'])) {
                if (isset($data['opt'][0]) && $data['opt'][0] === 'ON' && $data['opt'][1] === 'OFF') {
                    $data['opt'] = [
                        'ON' => 'On',
                        'OFF' => 'Off',
                    ];
                }
                $items[$itemName]->set_options($data['opt']);
            }
            if (!empty($data['type']) && in_array($data['type'], $readonly_widgets)) {
                $items[$itemName]->set_state_is_read_only(true);
            }

            if ($state_change_filter) {
                switch ($state_change_filter) {
                    case 1: //mutable
                        if ($items[$itemName]->get_state_is_read_only()) {
                            unset($items[$itemName]);
                        }
                        break;
                    case 2: //readable
                        if (!$items[$itemName]->get_state_is_read_only()) {
                            unset($items[$itemName]);
                        }
                        break;
                }
            }
        }

        return $items;
    }
}
