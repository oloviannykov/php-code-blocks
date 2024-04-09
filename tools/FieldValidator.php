<?php
namespace App\Models\tools;

use App\Models\tools\Calendar;
use App\Translations\FieldValidatorDictionary;

class FieldValidator
{
    const
        TYPE_TEXT = 'TYPE_TEXT',//space allowed
        PATTERN_TEXT = '.*',

        TYPE_WORD = 'TYPE_WORD',//no space allowed
        PATTERN_WORD = '^\S+$',

        TYPE_LETTER = 'TYPE_LETTER',//one letter
        PATTERN_LETTER = '^[A-Za-z]{1}$',

        TYPE_NUMERIC = 'TYPE_NUMERIC',
        PATTERN_NUMERIC = '^\-?[\d]+(\.?[\d]+)?$',

        TYPE_DATE = 'TYPE_DATE',
        PATTERN_DATE = '^\d{4}\-\d{2}\-\d{2}$',

        TYPE_TIME = 'TYPE_TIME',
        PATTERN_TIME = '^([0-9]|0[0-9]|1[0-9]|2[0-3]):([0-9]|[0-5][0-9])$',
        TYPE_TIME_RANGE = 'TYPE_TIME_RANGE',

        TYPE_PHONE = 'TYPE_PHONE',
        PATTERN_PHONE = '^(\+\d{1,2}\s?)?(\(\d{2,3}\)\s?)?\d{2}[\d\-\s]{2,12}\d{2}$',
        //'^\+?[\d\(][\d\-\(\)\s]{5,18}$',

        TYPE_EMAIL = 'TYPE_EMAIL',
        PATTERN_EMAIL = '^\w+\@\w+\.\w+$',

        TYPE_CURRENCY = 'TYPE_CURRENCY',
        PATTERN_CURRENCY = '^[A-Za-z]{3}$',

        TYPE_LANGUAGE_CODE = 'TYPE_LANGUAGE_CODE',
        PATTERN_LANGUAGE_CODE = '^[A-Za-z]{2}$',

        TYPE_COUNTRY_CODE = 'TYPE_COUNTRY_CODE',
        PATTERN_COUNTRY_CODE = '^[A-Za-z]{2}$',

        TYPE_ARRAY = 'TYPE_ARRAY',
        TYPE_BOOLEAN = 'TYPE_BOOLEAN'
    ;

    private static function get_type_name($type): string
    {
        if (!defined('self::' . $type)) {
            return '';
        }
        return str_replace('TYPE_', '', $type);
    }

    public static function get_type_pattern($type): string
    {
        $name = self::get_type_name($type);
        if ($name && defined('self::PATTERN_' . $name)) {
            return constant('self::PATTERN_' . $name);
        }
        return '';
    }

    public static function get_type_description($type): string
    {
        FieldValidatorDictionary::load();
        $trans = [
            self::TYPE_ARRAY => FieldValidatorDictionary::$list_of_values,
            self::TYPE_BOOLEAN => FieldValidatorDictionary::$true_or_false,
            self::TYPE_COUNTRY_CODE => FieldValidatorDictionary::$latin_letters_country_code,
            self::TYPE_CURRENCY => FieldValidatorDictionary::$latin_letters_currency_code,
            self::TYPE_DATE => FieldValidatorDictionary::$date_in_format_year_month_date,
            self::TYPE_EMAIL => FieldValidatorDictionary::$email_address_format,
            self::TYPE_LANGUAGE_CODE => FieldValidatorDictionary::$latin_letters_language_code,
            self::TYPE_LETTER => FieldValidatorDictionary::$one_latin_letter,
            self::TYPE_NUMERIC => FieldValidatorDictionary::$digits_with_dash_and_point,
            self::TYPE_PHONE => FieldValidatorDictionary::$phone_number_format,
            self::TYPE_TEXT => FieldValidatorDictionary::$any_symbols,
            self::TYPE_TIME => FieldValidatorDictionary::$time_in_format_hours_minutes,
            self::TYPE_TIME_RANGE => FieldValidatorDictionary::$time_range_hhmm_hhmm,
            self::TYPE_WORD => FieldValidatorDictionary::$word_without_space,
        ];
        return empty($trans[$type]) ? '' : $trans[$type];
    }

    public static function validate_field($field, $rules, &$data): bool
    {
        return self::validate(
            $field,
            $rules,
            isset($data[$field]) ? $data[$field] : ''
        );
    }

    public static function validate($field, $rules, $value): bool
    {
        if (empty($rules['type'])) {
            return true;
        }
        //$console_mode = started_from_console();
        if (empty($value)) {
            if (empty($rules['required'])) {
                return true;
            } else {
                return false;
            }
        }
        $t = $rules['type'];
        //array validation
        if ($t === self::TYPE_ARRAY) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($value as $key => $item) {
                if (!empty($rules['keys_list']) && !in_array($key, $rules['keys_list'])) {
                    return false;
                }
            }
            if (isset($rules['minqty']) && count($value) < $rules['minqty']) {
                return false;
            }
            if (isset($rules['maxqty']) && count($value) > $rules['maxqty']) {
                return false;
            }
            return true;
        }

        //email
        if ($t === self::TYPE_EMAIL) {
            return is_string($value)
                && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }

        //time range validation
        if ($t === self::TYPE_TIME_RANGE) {
            if (!is_array($value)) {
                return false;
            }
            if (empty($value['from']) || empty($value['until'])) {
                return false;
            }
            $from = Calendar::hoursPointParse($value['from']);
            if ($from) {
                $from = mktime($from[0], $from[1]);
            }
            $until = Calendar::hoursPointParse($value['until']);
            if ($until) {
                $until = mktime($until[0], $until[1]);
            }
            if (!$from || !$until || $from >= $until) {
                return false;
            }
            return true;
        }
        if ($t === self::TYPE_BOOLEAN) {
            if (!is_bool($value) && (!is_numeric($value) || !in_array((string) $value, ['0', '1']))) {
                return false;
            }
            return true;
        }
        if ($t === self::TYPE_NUMERIC) {
            $pattern = self::PATTERN_NUMERIC;
            $match = [];
            if (!is_numeric($value) || !preg_match('/' . $pattern . '/i', $value, $match)) {
                return false;
            }
            return true;
        }
        if ($t === self::TYPE_PHONE) {
            $pattern = self::PATTERN_PHONE;
            $match = [];
            $digits_qty = is_string($value)
                ? strlen(preg_replace('/\D/i', '', $value)) : 0;
            if (
                $digits_qty < 6 || $digits_qty > 16
                || !preg_match('/' . $pattern . '/i', $value, $match)
            ) {
                return false;
            }
            return true;
        }
        if ($t === self::TYPE_WORD) {
            $pattern = self::PATTERN_WORD;
            $match = [];
            if (
                !is_string($value)
                || !preg_match('/' . $pattern . '/i', $value, $match)
            ) {
                return false;
            }
            return true;
        }

        //value validation
        if (is_array($value)) {
            return false;
        }
        $pattern = self::get_type_pattern($t);
        $match = [];
        if ($pattern && !preg_match('/' . $pattern . '/i', $value, $match)) {
            return false;
        }

        return true;
    }


    public static function validate_form(&$data, $rules_list): array
    {
        FieldValidatorDictionary::load();
        $error = '';
        $wrong_field = '';
        foreach ($rules_list as $field => $rules) {
            if (!self::validate($field, $rules, isset($data[$field]) ? $data[$field] : '')) {
                $wrong_field = $field;
                $error = $field . ' ' . FieldValidatorDictionary::$is_invalid
                    . (!isset($data[$field]) || is_array($data[$field]) ? '' : ' (' . $data[$field] . ')')
                    . '.' . (
                    isset($rules['type']) ?
                    ' ' . FieldValidatorDictionary::$format . ': '
                    . self::get_type_description($rules['type'])
                    : ''
                );
                break;
            }
        }
        return [empty($error), $wrong_field, $error];
    }

}
