<?php

class CSRFTool
{
    const
        CSRF_TOKEN_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=`~,.[]: |',
        CSRF_TOKEN_LENGTH = 32,
        CSRF_FIELD_NAME = 'spice';

    public static function generate_csrf($replace = false): void
    {
        $empty = empty($_SESSION[self::CSRF_FIELD_NAME]);
        if ($replace || $empty) {
            if (function_exists('openssl_random_pseudo_bytes')) {
                $bytes = openssl_random_pseudo_bytes(self::CSRF_TOKEN_LENGTH);
                $token = base64_encode($bytes);
            } else {
                for ($i = 0; $i < self::CSRF_TOKEN_LENGTH; $i++) {
                    $rand = rand(0, strlen(self::CSRF_TOKEN_CHARS) - 1);
                    $token .= substr(self::CSRF_TOKEN_CHARS, $rand, 1);
                }
                $token = base64_encode($token);
            }
            $value = str_shuffle(trim($token, '='));
            $_SESSION[self::CSRF_FIELD_NAME] = $value;
        }
    }

    public static function clear_csrf(): void
    {
        if (!empty($_SESSION[self::CSRF_FIELD_NAME])) {
            unset($_SESSION[self::CSRF_FIELD_NAME]);
        }
    }

    public static function check_csrf(): bool
    {
        $field = self::CSRF_FIELD_NAME;
        if (!empty($_POST) && session_id()) {
            if (!isset($_POST[$field]) || $_POST[$field] !== $_SESSION[$field]) {
                return false;
            }
            unset($_POST[$field]);
        }
        return true;
    }

    public static function csrf_value(): string
    {
        return empty($_SESSION[self::CSRF_FIELD_NAME]) ? '' : $_SESSION[self::CSRF_FIELD_NAME];
    }

    public static function csrf_field(): string
    {
        $v = '';
        if (!empty($_SESSION[self::CSRF_FIELD_NAME])) {
            $v = $_SESSION[self::CSRF_FIELD_NAME];
        }
        if (!empty($v)) {
            return sprintf(
                '<input type="hidden" name="%s" value="%s" />',
                htmlentities(self::CSRF_FIELD_NAME),
                htmlentities($v)
            );
        }
        return '';
    }
}
