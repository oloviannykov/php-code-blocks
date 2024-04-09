<?php

//todo: replace errors_collect(...) with your own error reporting function
//todo: move this functions outside:

function get_language_domain()
{
    return [
        'en' => 'English',
        'es' => 'Spanish',
        'ru' => 'Russian',
    ];
}

function started_from_console()
{
    $has_argv = ! empty($_SERVER['argv']);
    $has_host = ! empty($_SERVER['HTTP_HOST']);
    $has_stdin = defined('STDIN');
    return $has_stdin && $has_argv && ! $has_host;
    /* You can make your own condition:
    console: array (
        'php_sapi' => 'cli', //from php_sapi_name()
        'REMOTE_ADDR' => NULL, //from $_SERVER['REMOTE_ADDR']
        'argv' => [0 => 'index.php'], 'argc' => 1,
        'host' => NULL, 'uri' => NULL,
        'STDIN is defined' => true,
      ),
      web browser: array (
        'php_sapi' => 'apache2handler',
        'REMOTE_ADDR' => '::1',
        'argv' => NULL, 'argc' => NULL,
        'host' => 'localhost', 'uri' => '/test/...',
        'STDIN is defined' => false,
      )
     */
}

//============

use App\Models\tools\CSRFTool;

class RequestParser
{
    public static
        $preserveHeaders = false,
        $lastHeader = '';

    //USER AGENT

    public static function get_user_agent($escape_html = true): string
    {
        $result = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
        return $escape_html && $result ? htmlspecialchars($result) : $result;
    }

    public static function detect_remote_device($user_agent = ''): array
    {
        $server_value = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
        $ua = empty($user_agent) ? $server_value : $user_agent;
        if (empty($ua)) {
            return [];
        }
        static $result = [];
        if (empty($user_agent) && $result) {
            return $result;
        }
        $result = self::parse_user_agent($ua);
        return $result;
    }

    public static function parse_user_agent($user_agent): array
    {
        if (empty($user_agent)) {
            return [];
        }
        $device_type = $soft = '';
        //look at https://podnews.net/article/podcast-app-useragents
        $map = [
            'bot' => array(
                '/googlebot/i' => 'Google',
                '/amazonnewscontentservice/i' => 'Amazon Alexa service',
                '/apache-httpclient/i' => 'Apache-HttpClient',
                '/bot/i' => 'Unknown',
                '/curl/i' => 'Unknown',
                '/facebookbot/i' => 'Facebook',
                '/wget/i' => 'Wget',
            ),
            'tablet' => array(
                '/ipad/i' => 'iPad',
            ),
            'watch' => array(
                '/apple watch/i' => 'Apple Watch',
            ),
            'Pod' => array(
                '/antennapod/i' => 'AntennaPod',
                '/homepod/i' => 'HomePod',
                '/ipod/i' => 'iPod',
            ),
            'TV' => array(
                '/apple tv/i' => 'Apple TV',
            ),
            'smart phone' => array(
                '/android/i' => 'Android',
                '/iphone/i' => 'iPhone',
                '/ios/i' => 'iOS',
                '/windows phone/i' => 'Windows Phone',
                '/phone/i' => 'unknown',
            ),
            'mobile device' => array(
                '/mobile/i' => 'unknown',
                '/raspberry/i' => 'Raspberry Pi',
            ),
            'PC' => array(
                '/windows nt 5\.0/i' => 'Windows 2000',
                '/windows nt 5/i' => 'Windows XP/Server 2003',
                '/windows nt 6\.(0|1)/i' => 'Windows Vista/7/Server 2008',
                '/windows nt 6\.(2|3)/i' => 'Windows 8/Server 2012',
                '/windows nt 10/i' => 'Windows 10',
                '/windows (10|11)/i' => 'Windows 11',
                '/windows/i' => 'Windows',
                '/(macintosh|mac os x)/i' => 'Mac OS X',
                '/mac_powerpc/i' => 'Mac OS 9',
                '/mac os/i' => 'Mac OS',
                '/ubuntu/i' => 'Ubuntu Linux',
                '/centos/i' => 'CentOS Linux',
                '/(red hat|rhel)/i' => 'Red Hat Linux',
                '/debian/i' => 'Debian Linux',
                '/opensuse/i' => 'openSUSE Linux',
                '/fedora/i' => 'Fedora Linux',
                '/freebsd/i' => 'FreeBSD',
                '/mint/i' => 'Linux Mint',
                '/manjaro/i' => 'Manjaro Linux',
                '/(linux_x86_64|linux x86_64)/i' => 'Linux x64',
                '/(linux_x86|linux x86)/i' => 'Linux x86',
                '/cros/i' => 'ChromeOS',
                '/linux/i' => 'Linux',
            ),
        ];
        $ua_lowcase = strtolower($user_agent);

        foreach ($map as $devtype => $patterns) {
            foreach ($patterns as $regex => $value) {
                if (preg_match($regex, $ua_lowcase)) {
                    $soft = $value;
                    $device_type = $devtype;
                    break;
                }
            }
            if ($soft) {
                break;
            }
        }

        $start = strpos($user_agent, '(');
        $end = strpos($user_agent, ')');
        $details = '';
        if ($start && $end) {
            $details = self::filter_device_detailes(substr($user_agent, $start + 1, $end - $start - 1));
        } else {
            $details = htmlspecialchars($user_agent);
        }
        $result = array(
            'type' => $device_type ? $device_type : 'unknown',
            'soft' => $soft ? $soft : 'unknown',
            'details' => $details
        );
        return $result;
    }

    public static function filter_device_detailes($details): string
    {
        $details = preg_replace('/(; rv\:[0-9\.]*| like Mac OS X)$/i', '', $details);
        $details = preg_replace('/iPhone OS [0-9\_]*/i', 'iPhone OS', $details);
        $details = preg_replace('/(\.[0-9]*|\_[0-9]*){1,2}$/i', '', $details);
        $details = preg_replace('/Android [0-9\.]*/i', 'Android', $details);
        $details = preg_replace('/OS X [0-9\.]*/i', 'OS X', $details);
        $details = preg_replace('/Windows NT [0-9\.]*/i', 'Windows NT', $details);
        $details = preg_replace('/^compatible; MSIE [0-9\.]*; /i', '', $details);
        return $details;
    }

    //GET

    public static function get_url_hidden_variables(): array
    {
        return [
            'apikey',
            'key',
            'cmarker',
            'force_cookie_key',
            'token'
        ];
    }

    public static function censor_get(): array
    {
        $query = $_GET;
        if ($query) {
            $hidden_variables = self::get_url_hidden_variables();
            foreach ($hidden_variables as $var_name) {
                if (isset($query[$var_name])) {
                    $query[$var_name] = '...';
                }
            }
        }
        return $query;
    }

    //URL

    public static function get_host(): string
    {
        if (empty($_SERVER['HTTP_HOST'])) {
            return empty($_SERVER['SERVER_NAME']) ? '' : $_SERVER['SERVER_NAME'];
        }
        return $_SERVER['HTTP_HOST'];
    }

    public static function is_https_mode(): bool
    {
        if (isset($_SERVER['HTTPS'])) {
            return !empty($_SERVER['HTTPS']);
        }
        return isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https';
        //http://10.0.1.127:8123
        //http://181.0.111.222
        //http://xxx.qwerty.org
        //echo 'SERVER: <pre>' . var_export($_SERVER, 1) . '</pre>';
        /*
            return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
              || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=="https")
              || (!empty($_SERVER['HTTP_HTTPS']) && strtolower($_SERVER['HTTP_HTTPS']) !== 'off')
              || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
              || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
              || (isset($_SERVER["HTTP_X_FORWARDED_SSL"]) && 'on' === $_SERVER["HTTP_X_FORWARDED_SSL"]);
        */
    }

    public static function get_protocol(): string
    {
        return self::is_https_mode() ? 'https' : 'http';
    }

    public static function get_url(): string
    {
        if (empty(self::get_host())) {
            return '';
        }
        $uri = empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'];
        return self::get_protocol() . '://' . self::get_host() . $uri;
    }

    public static function get_uri(): string
    {
        return empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'];
    }

    public static function censor_url($url_string, $hidden_variables = []): string
    {
        $query_start = strpos($url_string, '?');
        if ($query_start && strlen($url_string) > $query_start + 1) {
            $hidden_variables = $hidden_variables
                ? $hidden_variables : self::get_url_hidden_variables();
            $query = [];
            parse_str(substr($url_string, $query_start + 1), $query);
            foreach ($hidden_variables as $var_name) {
                if (isset($query[$var_name])) {
                    $query[$var_name] = '...';
                }
            }
            $url_string = substr($url_string, 0, $query_start) . '?' . http_build_query($query);
        }
        return $url_string;
    }

    public static function get_censored_url(): string
    {
        return self::censor_url(self::get_url());
    }

    //POST

    public static function get_post_hidden_variables(): array
    {
        return [
            CSRFTool::CSRF_FIELD_NAME,
            'password',
        ];
    }

    public static function censor_post(): array
    {
        $query = $_POST;
        if ($query) {
            $hidden_variables = self::get_post_hidden_variables();
            foreach ($hidden_variables as $var_name) {
                if (isset($query[$var_name])) {
                    $query[$var_name] = '...';
                }
            }
        }
        return $query;
    }

    public static function session_start_if_not_started($duration_in_days = 0): void
    {
        if (!session_id()) {
            if ($duration_in_days) {
                self::set_session_cookie_lifetime_in_days($duration_in_days);
                ini_set('session.gc_maxlifetime', $duration_in_days * 86400);
            }
            session_start();
            if (empty($_COOKIE[session_name()])) {
                self::set_cookie(session_name(), session_id(), $duration_in_days);
            }
        }
        /* php.ini recommended session settings:

        //http://php.net/session.cookie-lifetime
        session.cookie_lifetime = 2592000 //seconds, if 0 then until browser closing

        //seconds, After this number of seconds session can be removed by garbage collector.
        //http://php.net/session.gc-maxlifetime
        session.gc_maxlifetime = 432000
        //время в секундах с последнего использования сессии после которого сессию можно удалить.
        //Это может произойти в течение старта сессии в зависимости от session.gc_probability и session.gc_divisor.
        //Если разные скрипты имеют разные значения session.gc_maxlifetime, но при этом одни
        //и те же места для хранения данных сессии, то скрипт с минимальным значением уничтожит все данные.
        //В таком случае следует использовать эту директиву вместе с session.save_path.
         */
    }

    //CSRF
    public static function csrf_field(): string
    {
        return CSRFTool::csrf_field();
    }

    //HEADERS

    public static function get_headers()
    {
        return getallheaders();
        //todo: censor and filter headers
    }

    public static function set_response_code($code, $description = ''): void
    {
        self::$lastHeader = "HTTP/1.0 $code $description";
        if (!self::$preserveHeaders) {
            header(self::$lastHeader);
        }
        self::response_code_log($code);
    }

    public static function set_response_success(): void
    {
        self::set_response_code(200, "Success");
    }

    public static function set_response_bad_request(): void
    {
        self::set_response_code(400, "Bad Request");
    }

    public static function set_response_unauthorized(): void
    {
        self::set_response_code(401, "Unauthorized");
    }

    public static function set_response_forbidden(): void
    {
        self::set_response_code(403, "Forbidden");
    }

    public static function set_response_not_found(): void
    {
        self::set_response_code(404, "Not Found");
    }

    public static function set_response_server_error(): void
    {
        self::set_response_code(500, "Internal Server Error");
    }

    public static function set_response_location($link): void
    {
        self::$lastHeader = "Location: $link";
        if (!self::$preserveHeaders) {
            header(self::$lastHeader);
        }
        self::redirection_log($link);
    }

    public static function redirection_log($location = '')
    {
        static $x = '';
        if ($location) {
            $x = $location;
        } else {
            return $x;
        }
    }

    public static function response_code_log($code = '')
    {
        static $x = '';
        if ($code) {
            $x = $code;
        } else {
            return $x;
        }
    }


    //LANGUAGE

    public static function detect_language($lang_list_str = ''): string
    {
        $lang = 'en';
        $server_value = empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])
            ? '' : $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $lang_list_str = $lang_list_str ? $lang_list_str : $server_value;
        if (empty($lang_list_str)) {
            return $lang;
        }
        $result = [];
        $list = strtolower($lang_list_str);
        if (preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/', $list, $list)) {
            $codes = array_combine($list[1], $list[2]);
            foreach ($codes as $lang_code => $priority) {
                $short_lang_code = substr($lang_code, 0, 2);
                if (isset($result[$short_lang_code])) {
                    continue;
                }
                $result[$short_lang_code] = (bool) $priority ? $priority : 1;
            }
            arsort($result, SORT_NUMERIC);
        }
        $result = array_keys($result);
        $lang_domain = get_language_domain();
        foreach ($result as $lang_code) {
            if (isset($lang_domain[$lang_code])) {
                $lang = $lang_code;
                break;
            }
        }
        return $lang;
    }

    //COOKIE

    public static function set_cookie($name, $value, $days = 365, $minutes = 0): bool
    {
        $days_offset = $days ? strtotime('+' . (int) $days . ' days') : time();
        $minutes_offset = $minutes ? $minutes * 60 : 0;
        //string $name, string $value = "", int $expire = 0,
        //string $path = "", string $domain = "", bool $secure = false, bool $httponly = false
        return setcookie(
            $name,
            $value,
            $days_offset + $minutes_offset,
            '/' //valid for whole web-site
            //,''
            //,self::is_https_mode() //,SERVER_PROTOCOL==='https' //$secure
            //, true //http only, no javascript access
        );
    }

    public static function set_session_cookie_lifetime_in_days($days): void
    {
        session_set_cookie_params($days * 86400, '/');
        //session_cache_limiter('private_no_expire');
    }

    //REPORT FOR LOG

    public static function get_report($include_session = false, $include_headers = false): string
    {
        $result = [];
        if (started_from_console()) {
            $result[] = 'argv: ' . json_encode($_SERVER['argv'], JSON_UNESCAPED_UNICODE);
        } else {
            $result[] = 'uri: ' . self::get_censored_url();
            if (!empty($_GET)) {
                $result[] = 'get: ' . htmlentities(
                    json_encode(self::censor_get(), JSON_UNESCAPED_UNICODE)
                );
            }
            if (self::redirection_log()) {
                $result[] = 'redirecting to ' . self::redirection_log();
            }
            if (self::response_code_log()) {
                $result[] = 'response code: ' . self::response_code_log();
            }
            if (!empty($_POST)) {
                $result[] = 'post: ' . htmlentities(var_export(self::censor_post(), 1));
            }
            if (!empty($_FILES)) {
                $result[] = 'files: ' . var_export($_FILES, 1);
            }
            if ($include_session && !empty($_SESSION)) {
                $result[] = 'session: ' . var_export($_SESSION, 1);
            }
            if ($include_headers && function_exists('getallheaders')) {
                $result[] = 'headers: ' . htmlentities(var_export(self::get_headers(), 1));
            }
        }
        return implode("\n", $result);
    }

    //TIME PROFILER

    public static function time_profiler_log($url, $started, $marker, $duration = 0.0): void
    {
        $path = LOG_PATH . DIRECTORY_SEPARATOR . 'time_prof_' . date('Ymd') . '.log';
        if (!is_file($path)) {
            file_put_contents($path, '');
            chmod($path, 0777);
        }
        if (started_from_console()) {
            $user = 'console SAPI ' . php_sapi_name() . '; user ' . get_current_user();
        } else {
            $user = self::get_user_agent();
        }
        $record = date('H:i:s') . " [$marker] "
            . ($started ? "START $url; $user" : "END " . sprintf('%.04f', $duration) . "s") . "\n";
        @file_put_contents($path, $record, FILE_APPEND);
    }

    public static function time_profiler_income($started = true): void
    {
        static $start_time = 0.0;
        static $marker = '';
        if ($started) {
            $start_time = microtime(true);
            $duration = 0.0;
            $marker = $start_time;
        } elseif ($start_time > 0) {
            $duration = microtime(true) - $start_time;
            $start_time = 0.0;
        } else {
            //errors_collect(__FUNCTION__ . ' end without start time', debug_backtrace());
            $start_time = 0.0;
            $duration = 0.0;
        }
        if (self::get_url() && !started_from_console()) {
            $url = self::get_censored_url();
            if ($duration > 10.0) {
                //errors_collect(__FUNCTION__, ['too long incoming request duration' => $duration]);
            }
        } else {
            $args = $_SERVER['argv'];
            $url = 'command ' . implode(' ', $args);
        }
        self::time_profiler_log($url, $started, $marker, $duration);
    }

    public static function time_profiler_outgoing($url, $started = true): void
    {
        static $start_time = 0.0;
        static $marker = '';
        if ($started) {
            $start_time = microtime(true);
            $duration = 0.0;
            $marker = microtime();
        } else {
            $duration = microtime(true) - $start_time;
            $start_time = 0.0;
        }
        $url = self::censor_url($url);
        if (started_from_console()) {
            $args = $_SERVER['argv'];
            $url = 'command ' . implode(' ', $args) . '; ' . $url;
        }
        self::time_profiler_log($url, $started, $marker, $duration);
        if ($duration > 30.0) {
            /*
            errors_collect(__FUNCTION__, [
                'request' => $url,
                'too long outgoing request duration' => $duration
            ]);*/
        }
    }

    //IP

    public static function get_remote_ip(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        if (!empty($_SERVER['REMOTE_ADDR']))
            return $_SERVER['REMOTE_ADDR'];
        return '';
    }
}
