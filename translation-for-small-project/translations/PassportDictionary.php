<?php
namespace App\Translations;

class PassportDictionary extends DictionaryStatic
{
    public static
    $passport,
    $cedula,
    $local_passport,
    $international_passport,
    $other,
    $dom_republic,
    $ukraine,
    $rus_federation,
    $us_of_america,
    $venezuela,
    $haiti;
    protected static function set_en()
    {
        self::$passport = 'Passport';
        self::$cedula = 'cedula';
        self::$local_passport = 'local passport';
        self::$international_passport = 'international passport';
        self::$other = 'other';
        self::$dom_republic = 'Dominican Republic';
        self::$ukraine = 'Ukraine';
        self::$rus_federation = 'Russian Federation';
        self::$us_of_america = 'United States of America';
        self::$venezuela = 'Venezuela';
        self::$haiti = 'Haiti';
    }
    protected static function set_es()
    {
        self::$passport = 'Pasoporte';
        self::$cedula = 'cedula';
        self::$local_passport = 'pasoporte local';
        self::$international_passport = 'pasoporte internacional';
        self::$other = 'otro';
        self::$dom_republic = 'Republica Dominicana';
        self::$ukraine = 'Ucrania';
        self::$rus_federation = 'Federación Rusa';
        self::$us_of_america = 'Estados Unidos de America';
        self::$venezuela = 'Venezuela';
        self::$haiti = 'Haiti';
    }
    protected static function set_ru()
    {
        self::$passport = 'Пасспорт';
        self::$cedula = 'седула';
        self::$local_passport = 'местный пасспорт';
        self::$international_passport = 'загран. пасспорт';
        self::$other = 'другой';
        self::$dom_republic = 'Доминиканская республика';
        self::$ukraine = 'Украина';
        self::$rus_federation = 'Российская федерация';
        self::$us_of_america = 'Соединенные Штаты Америки';
        self::$venezuela = 'Венесуела';
        self::$haiti = 'Гаити';
    }
}

