<?php
namespace App\Translations;

//todo: declare constant DEFAULT_LANG (example value: 'en' if English is default)

class DictionaryStatic
{
    protected static $loaded = [];

    public static function load($lang = '')
    {
        if (empty($lang)) {
            $lang = self::get_session_language();
        }
        if (!$lang || !method_exists(static::class, 'set_' . $lang)) {
            $lang = DEFAULT_LANG;
        }
        if (empty(static::$loaded[static::class]) || static::$loaded[static::class] !== $lang) {
            static::{'set_' . $lang}();
            static::$loaded[static::class] = $lang;
        }
    }

    public static function get_session_language()
    {
        static $lang_code = '';
        if (empty($lang_code)) {
            //todo: get language code from user session params
            $lang_code = 'en';
        }
        return empty($lang_code) ? DEFAULT_LANG : $lang_code;
    }

    protected static function translate_n_x(
        $n,
        $x,
        $n_x,
        $n_x1,
        $n_x234,
        $and_half,
        $zero_value = '',
        $half_value = ''
    ) {
        //example: 2 apples - 2, apple, %d apples, %d apples, %d apples
        //to russian: 2 яблока - 2, яблоко, %d яблок, %d яблоко, %d яблока
        $rounded_n = floor($n);
        $last_digit = intval(mb_substr('' . $rounded_n, -1));
        $plas_half = $n > 1.0 && sprintf("%.1f", $n - $rounded_n) === '0.5';
        $str = $n_x;
        $zero_value = $zero_value ? $zero_value : $n_x;
        $half_value = $half_value ? $half_value : $n_x234;
        if ($n < 0.5) {
            $str = $zero_value;
        } elseif ($n >= 0.5 && $n < 1.0) {
            $str = $half_value;
        } elseif ($n === 1) {
            $str = $x;
        } elseif ($last_digit === 1) {
            $str = $n_x1;
        } elseif ($last_digit > 1 && $last_digit < 5 && ($n < 5 || $n > 15)) {
            $str = $n_x234; //2 дня, 22 дня, 32 дня, но 12 дней
        }
        return sprintf($str, $n, ($plas_half ? ' ' . $and_half : ''));
    }
    protected static function translate_i_x($n, $x, $n_x, $n_x1, $n_x234, $zero_value = '')
    {
        //example: 2 apples - 2, apple, %d apples, %d apples, %d apples
        //to russian: 2 яблока - 2, яблоко, %d яблок, %d яблоко, %d яблока
        $n = intval($n);
        $last_digit = intval(mb_substr('' . $n, -1));
        $str = $n_x;
        $zero_value = $zero_value ? $zero_value : $n_x;
        if ($n < 1) {
            $str = $zero_value;
        } elseif ($n === 1) {
            $str = $x;
        } elseif ($last_digit === 1) {
            $str = $n_x1;
        } elseif ($last_digit > 1 && $last_digit < 5 && ($n < 5 || $n > 15)) {
            $str = $n_x234; //2 дня, 22 дня, 32 дня, но 12 дней
        }
        return sprintf($str, $n);
    }
}
