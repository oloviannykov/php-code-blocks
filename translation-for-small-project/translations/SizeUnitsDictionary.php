<?php
namespace App\Translations;

class SizeUnitsDictionary extends DictionaryStatic
{
    public static
    $grams,
    $kilo_grams,
    $libras,
    $liters,
    $mili_liters,
    $oz,
    $meters,
    $inches,
    $square_meters,
    $pieces;
    protected static function set_en()
    {
        self::$grams = 'Grams';
        self::$kilo_grams = 'Kg';
        self::$libras = 'Libras';
        self::$liters = 'Liters';
        self::$mili_liters = 'ml';
        self::$oz = 'Oz';
        self::$meters = 'Meters';
        self::$inches = 'Inches';
        self::$square_meters = 'Sq. Meters';
        self::$pieces = 'Pieces';
    }
    protected static function set_es()
    {
        self::$grams = 'Gramas';
        self::$kilo_grams = 'Kg';
        self::$libras = 'Libras';
        self::$liters = 'Litros';
        self::$mili_liters = 'ml';
        self::$oz = 'Onzas';
        self::$meters = 'Metros';
        self::$inches = 'Pulgadas';
        self::$square_meters = 'Metros Cuad.';
        self::$pieces = 'Cosas';
    }
    protected static function set_ru()
    {
        self::$grams = 'Граммы';
        self::$kilo_grams = 'Кг';
        self::$libras = 'Либры';
        self::$liters = 'Литры';
        self::$mili_liters = 'мл';
        self::$oz = 'Унции';
        self::$meters = 'Метры';
        self::$inches = 'Дюймы';
        self::$square_meters = 'Метры Кв.';
        self::$pieces = 'Штуки';
    }
}
