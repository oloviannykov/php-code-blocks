<?php
/**
* Entity of device from OpenHAB JSON API (OpenHAB.php)
*/

class OpenHABItem
{
    private
        $id = 0,
        $name = false,
        $type = false,
        $label = false,
        $minvalue = 0,
        $maxvalue = 0,
        $options = [],
        $state = false,
        $state_is_read_only = false,
        $category = false,
        $import_date = 0,
        $system_name = '';

    const
        FIELD__ID = 'id', //int(11) NOT NULL AUTO_INCREMENT
        FIELD__NAME = 'name', //varchar(50) NOT NULL
        FIELD__TYPE = 'type', //varchar(30) NOT NULL
        FIELD__OPTIONS = 'options', //int(11) unsigned
        FIELD__MINVALUE = 'minvalue', //int(11) unsigned
        FIELD__MAXVALUE = 'maxvalue', //text NOT NULL
        FIELD__LABEL = 'label', //varchar(30) NOT NULL
        FIELD__STATE = 'state', //varchar(100) NOT NULL
        FIELD__STATEISREADONLY = 'state_is_read_only', //tinyint(1) DEFAULT 0
        FIELD__IMPORT_DATE = 'import_date', //varchar(30) NOT NULL
        FIELD__SYSTEM_NAME = 'system_name', //int(11) unsigned
        FIELD__CATEGORY = 'category'; //varchar(30) NOT NULL DEFAULT ''

    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->set_from_array($data);
        }
    }

    public function get_id()
    {
        return empty($this->id) ? 0 : (int) $this->id;
    }
    public function get_name()
    {
        return empty($this->name) ? '' : (string) $this->name;
    }
    public function get_minvalue()
    {
        return empty($this->minvalue) ? 0 : (int) $this->minvalue;
    }
    public function get_maxvalue()
    {
        return empty($this->maxvalue) ? 0 : (int) $this->maxvalue;
    }
    public function get_options()
    {
        return empty($this->options) ? [] : $this->options;
    }
    public function get_type()
    {
        return empty($this->type) ? '' : (string) $this->type;
    }
    public function get_label()
    {
        return empty($this->label) ? '' : (string) $this->label;
    }
    public function get_state()
    {
        return empty($this->state) ? '' : (string) $this->state;
    }
    public function get_state_is_read_only()
    {
        return (bool) $this->state_is_read_only;
    }
    public function get_category()
    {
        return empty($this->category) ? '' : (string) $this->category;
    }

    public function is_imported()
    {
        return (bool) $this->import_date;
    }
    public function get_import_date()
    {
        return (int) $this->import_date;
    }
    public function get_system_name()
    {
        return empty($this->system_name) ? '' : (string) $this->system_name;
    }

    ////////////////////

    public function set_id($id)
    {
        return $this->id = intval($id);
    }
    public function set_import_date($date)
    {
        $this->import_date = (int) $date;
    }
    public function set_system_name($name)
    {
        $this->system_name = empty($name) || !is_string($name) ? '' : (string) $name;
    }
    public function set_label($label)
    {
        return $this->label = is_string($label) ? $label : '';
    }
    public function set_minvalue($value)
    {
        return $this->minvalue = is_numeric($value) ? $value : 0;
    }
    public function set_maxvalue($value)
    {
        return $this->maxvalue = is_numeric($value) ? $value : 0;
    }
    public function set_options($array)
    {
        $this->options = empty($array) || !is_array($array) ? [] : $array;
    }
    public function set_state_is_read_only($read_only)
    {
        $this->state_is_read_only = (bool) $read_only;
    }

    public function get_fields()
    {
        return [
            self::FIELD__ID => 'number',
            self::FIELD__NAME => 'string',
            self::FIELD__MINVALUE => 'number',
            self::FIELD__MAXVALUE => 'number',
            self::FIELD__OPTIONS => 'array',
            self::FIELD__TYPE => 'string',
            self::FIELD__LABEL => 'string',
            self::FIELD__STATE => 'string',
            self::FIELD__STATEISREADONLY => 'boolean',
            self::FIELD__CATEGORY => 'string',
            self::FIELD__IMPORT_DATE => 'number',
            self::FIELD__SYSTEM_NAME => 'string',
        ];
    }

    private function get_default_values_by_type()
    {
        return [
            'string' => '',
            'domain' => '',
            'array' => [],
            'number' => 0,
            'boolean' => false,
        ];
    }

    public function set_from_array($data)
    {
        $default_values_by_type = $this->get_default_values_by_type();
        foreach ($this->get_fields() as $field_name => $field_type) {
            if (!empty($data[$field_name])) {
                if ($field_type === 'array' && is_string($data[$field_name])) {
                    $this->{$field_name} = json_decode($data[$field_name], 1);
                    continue;
                } elseif ($field_type === 'domain') {
                    $domain = $this->{$field_name . '_domain'}();
                    if (isset($domain[$data[$field_name]])) {
                        $this->{$field_name} = $data[$field_name];
                        continue;
                    }
                } else {
                    $this->{$field_name} = $data[$field_name];
                    continue;
                }
            }

            $this->{$field_name} = $default_values_by_type[$field_type];
        }
        return true;
    }

    public function get_as_array()
    {
        $result = [];
        foreach ($this->get_fields() as $field_name => $field_type) {
            $result[$field_name] = $this->{$field_name};
        }
        return $result;
    }

    public function __toString(): string
    {
        $result = [];
        foreach ($this->get_as_array() as $field => $value) {
            $tmp = $field . ' = ' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $result[] = $tmp;
        }
        return implode("\n", $result);
    }
}
