<?php
/**
* Class can be used to output <select> tah directly from PHP
* Made for Bootstrap Front-End
*/

class SelectorField
{
    private
        $name = '',
        $options = [],
        $selected = '',
        $required = false,
        $id = '',
        $class_name = '',
        $title = '',
        $all_marker = '...',
        $no_option_all = false;

    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    public function options($options)
    {
        $this->options = $options;
        return $this;
    }
    public function no_option_all()
    {
        $this->no_option_all = true;
        return $this;
    }
    public function selected($selected)
    {
        $this->selected = $selected;
        return $this;
    }
    public function selected_or_default(&$data, $field, $default = '')
    {
        $this->selected = empty($data[$field]) ? $default : $data[$field];
        return $this;
    }
    public function required($value)
    {
        $this->required = $value;
        return $this;
    }
    public function id($id)
    {
        $this->id = $id;
        return $this;
    }

    public function class_name($class_name)
    {
        $this->class_name = $class_name;
        return $this;
    }

    public function title($title)
    {
        $this->title = $title;
        return $this;
    }

    public function all_marker($all_marker)
    {
        $this->all_marker = $all_marker;
        return $this;
    }

    private function html($value): string
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }

    public function get_html(): string
    {
        $result = '<select class="form-control ' . self::html($this->class_name) . '"'
            . ($this->name ? ' name="' . self::html($this->name) . '"' : '')
            . ($this->id ? ' id="' . self::html($this->id) . '"' : '')
            . ($this->required ? ' required' : '')
            . ($this->title ? ' title="' . self::html($this->title) . '"' : '')
            . '>';
        $not_selected = $this->selected !== '0' && $this->selected !== 0 && empty($this->selected);
        if (!$this->no_option_all) {
            $result .= '<option value=""' . ($not_selected ? ' selected' : '') . '>'
                . $this->all_marker . '</option>';
        }
        foreach ($this->options as $key => $value) {
            $result .= '<option value="' . self::html($key) . '"'
                . (!$not_selected && $key == $this->selected ? ' selected' : '') . '>'
                . self::html($value)
                . '</option>';
        }
        $result .= '</select>';

        return $result;
    }
}
