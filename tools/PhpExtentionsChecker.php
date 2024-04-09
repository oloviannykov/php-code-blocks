<?php

class PhpExtentionsChecker
{
    private static $required = [];
    private static $loaded = [];

    public static function setDefaultRequired()
    {
        self::$required = array(
            'Core',
            'standard',
            'SPL', //spl_autoload_register, RecursiveDirectoryIterator, ...
            'mbstring', //mb_strlen, mb_substr, mb_internal_encoding, mb_http_output
            'mysqli', //DB queries
            'date', //date manipulations
            'json', //json_encode, json_decode
            'session', //session_start, session_...
            'curl', //cURL utility bindings
            'pcre', //preg_match, preg_replace, preg_filter, ...
            'filter', //filter_var(), ...
            'openssl', //openssl_random_pseudo_bytes, ...
            'fileinfo', //mime_content_type, ...
            'Phar', //tar-gzip and tar-bzip2 packer-unpacker + composer.phar executor
            'bcmath', //bcdiv, bccomp, ...
        );
    }

    public static function setRequired($list): void
    {
        self::$required = $list;
    }

    public static function getRequired(): array
    {
        return self::$required;
    }

    public static function getLoaded(): array
    {
        return self::$loaded;
    }

    public static function indexLoaded(): void
    {
        if (empty(self::$loaded)) {
            self::$loaded = array_flip(get_loaded_extensions());
        }
    }

    public static function dumpLoaded()
    {
        self::indexLoaded();
        echo "<pre>Loaded PHP extentions: " . var_export(self::$loaded, 1) . '</pre>';
    }

    public static function getMissing()
    {
        self::indexLoaded();
        if (empty(self::$required)) {
            self::setDefaultRequired();
        }
        $missing = [];
        foreach (self::$required as $ext) {
            if (!isset(self::$loaded[$ext])) {
                $missing[] = $ext;
            }
        }
        return $missing;
    }

}
