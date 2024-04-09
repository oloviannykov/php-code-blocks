<?php
namespace App\Translations;

class FieldValidatorDictionary extends DictionaryStatic
{
    public static
    $format,
    $is_invalid,
    $any_symbols,
    $word_without_space,
    $one_latin_letter,
    $digits_with_dash_and_point,
    $date_in_format_year_month_date,
    $time_in_format_hours_minutes,
    $time_range_hhmm_hhmm,
    $phone_number_format,
    $email_address_format,
    $latin_letters_currency_code,
    $latin_letters_language_code,
    $latin_letters_country_code,
    $list_of_values,
    $true_or_false;
    protected static function set_en()
    {
        self::$format = 'Format';
        self::$is_invalid = 'is invalid';
        self::$any_symbols = 'any symbols';
        self::$word_without_space = 'any symbols excepting spaces';
        self::$one_latin_letter = 'one latin letter';
        self::$digits_with_dash_and_point = 'digits with dash and point (-.)';
        self::$date_in_format_year_month_date = 'date in format year-month-date';
        self::$time_in_format_hours_minutes = 'time in format hours:minutes, hours 00-23, minutes 00-59';
        self::$time_range_hhmm_hhmm = 'time range hours:minutes - hours:minutes, hours 00-23, minutes 00-59';
        self::$phone_number_format = 'phone number in format +5(444)333-22-11, spaces allowed, minimum 6 digits';
        self::$email_address_format = 'e-mail address xxx@xxx.xxx including _.-';
        self::$latin_letters_currency_code = '3 latin letters currency code';
        self::$latin_letters_language_code = '2 latin letters language code';
        self::$latin_letters_country_code = '2 latin letters country code';
        self::$list_of_values = 'list of values';
        self::$true_or_false = 'true or false, 1 or 0';
    }
    protected static function set_es()
    {
        self::$format = 'Formato';
        self::$is_invalid = 'esta incorrecto';
        self::$any_symbols = 'cualquieros símbolos';
        self::$word_without_space = 'cualquieros símbolos excepto espacio';
        self::$one_latin_letter = 'una letra latina';
        self::$digits_with_dash_and_point = 'cifras incluendo - y .';
        self::$date_in_format_year_month_date = 'fecha en formato ano-mes-dia';
        self::$time_in_format_hours_minutes = 'hora en formato hora:minutes (horas 00-23, minutes 00-59)';
        self::$time_range_hhmm_hhmm = 'rango de hora hora:minutes - hora:minutes (horas 00-23, minutes 00-59)';
        self::$phone_number_format = 'numero de telefono en formato +5(444)333-22-11 incluendo espacio, minimo 6 cifras';
        self::$email_address_format = 'correo electronico xxx@xxx.xxx incluendo _.-';
        self::$latin_letters_currency_code = 'codigo de moneda de 3 letras latinas';
        self::$latin_letters_language_code = 'codigo de idioma de 2 letras latinas';
        self::$latin_letters_country_code = 'codigo de pais de 3 letras latinas';
        self::$list_of_values = 'lista de valores';
        self::$true_or_false = 'verdad o falso, 1 o 0';
    }
    protected static function set_ru()
    {
        self::$format = 'Формат';
        self::$is_invalid = 'указано не верно';
        self::$any_symbols = 'любые символы';
        self::$word_without_space = 'любые символы кроме пробела';
        self::$one_latin_letter = 'одна латинская буква';
        self::$digits_with_dash_and_point = 'цифры, - и .';
        self::$date_in_format_year_month_date = 'дата в формате год-месяц-день';
        self::$time_in_format_hours_minutes = 'время в формате часы:минуты (часы 00-23, минуты 00-59)';
        self::$time_range_hhmm_hhmm = 'временной интервал часы:минуты - часы:минуты (часы 00-23, минуты 00-59)';
        self::$phone_number_format = 'номер телефона в формате +5(444)333-22-11 в т.ч. пробел, минимум 6 цифр';
        self::$email_address_format = 'электронная почта xxx@xxx.xxx включая _.-';
        self::$latin_letters_currency_code = 'код валюты из 3-х латинских букв';
        self::$latin_letters_language_code = 'код языка из 2-х латинских букв';
        self::$latin_letters_country_code = 'код страны из 3-х латинских букв';
        self::$list_of_values = 'список значений';
        self::$true_or_false = 'правда или ложь, 1 или 0';
    }
}