<?php

define('DEFAULT_LANG', 'en'); //todo: move outside

//from 'translation-for-small-project' directory
use App\Translations\DateDictionary;
use App\Translations\TimeDictionary;

class ValueOrError
{
    public $value = NULL;
    public $trigger = '';
    public $notFound = false;
    public $error = false;
    public $errorText = '';
    public $errorCode = '';

    const ERR__DB_ERROR = 'db_error';

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function __invoke($value)
    {
        return $this->setValue($value);
    } //$r = new ValueOrError; $r($value);

    public function trigger($str)
    {
        $this->trigger = $str;
        return $this;
    }

    public function notFound()
    {
        $this->notFound = true;
        return $this;
    }

    public function getDump(): string
    {
        return implode("; ", array_filter([
            $this->error ? 'FAIL: ' . $this->errorText . ($this->errorCode === '' ? '' : ' (' . $this->errorCode . ')') : 'SUCCESS',
            $this->notFound ? 'not found' : false,
            is_null($this->value) ? false : json_encode($this->value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $this->error && $this->trigger ? $this->trigger : false,
        ]));
    }

    public function __toString()
    {
        return $this->getDump();
    } //echo (new ValueOrError);

    public function dbError($errorText, $trigger = '')
    {
        $this->error($errorText, self::ERR__DB_ERROR, $trigger);
        return $this;
    }

    public function error($errorText, $errorCode = '', $trigger = '')
    {
        $this->error = true;
        $this->errorText = $errorText;
        $this->errorCode = $errorCode;
        if ($trigger) {
            $this->trigger = $trigger;
        } else {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            //$this->trigger = json_encode($bt[0], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $class = empty($bt[1]['class']) ? '' : $bt[1]['class'] . '::';
            $func = empty($bt[1]['function']) ? '' : $bt[1]['function'];
            $line = empty($bt[0]['line']) ? '' : ':' . $bt[0]['line'];
            $this->trigger = "$class$func$line";
        }
        return $this;
    }
}

class ValueArray extends ValueOrError
{
    public function notFound()
    {
        $this->notFound = true;
        $this->value = [];
        return $this;
    }
}

class ValueBoolean extends ValueOrError
{
    public function setValue($success)
    {
        $this->value = !empty($success);
        return $this;
    }
    public function success()
    {
        $this->value = true;
        return $this;
    }
}

class ValueNumber extends ValueOrError
{
    public function setInteger($n)
    {
        $this->value = intval($n);
        return $this;
    }
    public function setFloat($n)
    {
        $this->value = floatval($n);
        return $this;
    }
    public function __invoke($value)
    {
        if (is_float($value) || is_double($value)) {
            return $this->setFloat($value);
        }
        return $this->setInteger($value);
    }
}

class ValueString extends ValueOrError
{
    public function setValue($string)
    {
        $this->value = (string) $string;
        return $this;
    }
}

class Calendar
{
    const
        DATE_PERIOD_START_FIELD = 'period_start',
        DATE_PERIOD_END_FIELD = 'period_end',
        DATEPICKER_START_WEEK_FROM_SUNDAY = true; //todo: implement Mo 1st in js plugIn

    public static function get_date_period_filter($start, $end, $dateToString = true)
    {
        $start_value = $start
            ? ($dateToString ? date('Y-m-d', $start) : $start)
            : '';
        $end_value = $end
            ? ($dateToString ? date('Y-m-d', $end) : $end)
            : '';
        return [
            self::DATE_PERIOD_START_FIELD => $start_value,
            self::DATE_PERIOD_END_FIELD => $end_value,
        ];
    }

    public static function extract_date_period_filter(&$filter)
    {
        if (empty($filter) || !is_array($filter)) {
            return ['', ''];
        }
        return [
            empty($filter[self::DATE_PERIOD_START_FIELD]) ? '' : $filter[self::DATE_PERIOD_START_FIELD],
            empty($filter[self::DATE_PERIOD_END_FIELD]) ? '' : $filter[self::DATE_PERIOD_END_FIELD],
        ];
    }

    /**
     *
     * @param int $start_ts
     * @param int $end_ts
     * @param string $language
     */
    public static function get_calendar($start_ts, $end_ts, $language = ''): array
    {
        if (empty($start_ts) || !is_numeric($start_ts)) {
            //...['wrong start date' => $start_ts]);
            return [];
        }
        if (empty($end_ts) || !is_numeric($end_ts)) {
            //...['wrong end date' => $end_ts]);
            return [];
        }
        $lang = empty($language) ? DEFAULT_LANG : $language;
        $week_day_domain = self::weekDay_domain($lang);
        $month_domain = self::month_domain($lang);
        $result = [];
        $date = new \DateTime;
        $date->setTimestamp($start_ts);
        $day_interval = new \DateInterval('P1D');
        while ($date->getTimestamp() <= $end_ts) {
            $date_str = $date->format("Y-m-d");
            $year = $date->format("Y");
            $month_key = mb_strtolower($date->format("M")); //--> jan - dec
            $month_name = $month_domain[$month_key];
            $day = $date->format("j"); //1 - 31
            $week_day_key = substr(strtolower($date->format('D')), 0, 2); //mo - su
            if (empty($result[$year])) {
                $result[$year] = [];
            }
            if (empty($result[$year][$month_name])) {
                $result[$year][$month_name] = [];
            }
            $week_n = $date->format('W');

            if (!isset($result[$year][$month_name][$week_n])) {
                $week = [];
                foreach ($week_day_domain as $wd_key => $wd_name) {
                    $week[$wd_key] = [];
                }
                $result[$year][$month_name][$week_n] = $week;
            }
            $result[$year][$month_name][$week_n][$week_day_key] = [
                'day' => $day,
                'date' => $date_str,
            ];
            $date->add($day_interval);
        }
        //$result['week_day_domain'] = $week_day_domain;
        return $result;
    }

    public static function get_month_number_by_code_map()
    {
        return array(
            'jan' => 1,
            'feb' => 2,
            'mar' => 3,
            'apr' => 4,
            'may' => 5,
            'jun' => 6,
            'jul' => 7,
            'aug' => 8,
            'sep' => 9,
            'oct' => 10,
            'nov' => 11,
            'dec' => 12,
        );
    }

    public static function get_month_code_by_number_map()
    {
        return array(
            1 => 'jan',
            2 => 'feb',
            3 => 'mar',
            4 => 'apr',
            5 => 'may',
            6 => 'jun',
            7 => 'jul',
            8 => 'aug',
            9 => 'sep',
            10 => 'oct',
            11 => 'nov',
            12 => 'dec',
        );
    }

    public static function month_domain($lang = '')
    {
        DateDictionary::load($lang);
        return array(
            'jan' => DateDictionary::$january,
            'feb' => DateDictionary::$february,
            'mar' => DateDictionary::$march,
            'apr' => DateDictionary::$april,
            'may' => DateDictionary::$may,
            'jun' => DateDictionary::$june,
            'jul' => DateDictionary::$july,
            'aug' => DateDictionary::$august,
            'sep' => DateDictionary::$september,
            'oct' => DateDictionary::$october,
            'nov' => DateDictionary::$november,
            'dec' => DateDictionary::$december,
        );
    }

    public static function month_numeric_domain()
    {
        $abc_domain = self::month_domain();
        $num_domain = [];
        $index = 1; //start from 1
        foreach ($abc_domain as $name) {
            $num_domain[$index++] = $name;
        }
        return $num_domain;
    }

    public static function weekDay_domain($lang = '', $short = false)
    {
        DateDictionary::load($lang);
        return array(
            'mo' => $short ? DateDictionary::$monday_2s : DateDictionary::$monday,
            'tu' => $short ? DateDictionary::$tuesday_2s : DateDictionary::$tuesday,
            'we' => $short ? DateDictionary::$wednesday_2s : DateDictionary::$wednesday,
            'th' => $short ? DateDictionary::$thursday_2s : DateDictionary::$thursday,
            'fr' => $short ? DateDictionary::$friday_2s : DateDictionary::$friday,
            'sa' => $short ? DateDictionary::$saturday_2s : DateDictionary::$saturday,
            'su' => $short ? DateDictionary::$sunday_2s : DateDictionary::$sunday,
        );
    }

    public static function weekday_domain_from_date($from_date = '', $short = true): ValueArray
    {
        $r = new ValueArray;
        if (empty($from_date)) {
            return $r->error('date required', 'date_required');
        }
        if (is_string($from_date)) {
            $tmp = self::parse_date_string($from_date);
            if (empty($tmp)) {
                return $r->error('invalid date', 'invalid_date');
            }
            $from_date = $tmp;
        }
        $domain = self::weekDay_domain('', $short);
        $wd = self::currentWeekDayCode($from_date);
        $wd_order = array_keys($domain); //0 mo, 1 tu,...
        $wd_index = array_flip($wd_order); //mo 0, tu 1,...
        $result = [];
        for ($i = $wd_index[$wd]; $i <= 6; $i++) {
            $code = $wd_order[$i];
            $result[$code] = $domain[$code];
        }
        for ($i = 0; $i < $wd_index[$wd]; $i++) {
            $code = $wd_order[$i];
            $result[$code] = $domain[$code];
        }
        return $r($result);
    }

    public static function weekDay_numeric_domain()
    {
        $abc_domain = self::weekDay_domain();
        $num_domain = [];
        $index = 1; //start from 1
        foreach ($abc_domain as $name) {
            $num_domain[$index++] = $name;
        }
        return $num_domain;
    }

    public static function weekDayOrder_domain()
    {
        DateDictionary::load();
        //0-none, 1-first, 2-second, 10-last
        //first sunday of March - year 0 / month 3 / day 0 / wd 7 / wdo 1
        return [
            1 => DateDictionary::$first,
            2 => DateDictionary::$second,
            10 => DateDictionary::$last,
        ];
    }

    public static function monthDay_domain()
    {
        $list = [];
        for ($i = 1; $i <= 31; $i++) {
            $list[$i] = $i;
        }
        return $list;
    }

    public static function currentWeekDayCode($date = 0)
    {
        $week_days = array_keys(self::weekDay_domain());
        $current_wd_no = $date ? date('N', $date) : date('N');
        //Порядковый номер дня недели (ISO-8601 c PHP 5.1.0): 1 (понедельник) - 7 (воскресенье)
        if (empty($week_days[$current_wd_no - 1])) {
            return false;
        }
        return $week_days[$current_wd_no - 1];
    }

    public static function get_last_weekday_date($weekday_code)
    {
        $current_wd = substr(strtolower(date('D')), 0, 2);
        $wd_index = array_keys(self::weekDay_domain()); //i => code
        $wd_order = array_flip($wd_index); //code => i
        if ($weekday_code === $current_wd) {
            return mktime(0, 0, 0);
        }
        $current_wd_index = $wd_order[$current_wd];
        $days_diff = 0;
        $result_days = 0;
        for ($i = $current_wd_index; $i >= 0; $i--) {
            $wd_code = $wd_index[$i];
            if ($wd_code === $weekday_code) {
                $result_days = $days_diff;
                break;
            }
            $days_diff++;
        }
        if ($result_days === 0) {
            for ($i = count($wd_order) - 1; $i > $current_wd_index; $i--) {
                $wd_code = $wd_index[$i];
                if ($wd_code === $weekday_code) {
                    $result_days = $days_diff;
                    break;
                }
                $days_diff++;
            }
        }
        $d = $result_days === 1 ? strtotime('-1 day') : strtotime('-' . $result_days . ' days');
        return mktime(0, 0, 0, date('n', $d), date('j', $d), date('Y', $d));
    }

    public static function get_next_weekday_date($weekday_code)
    {
        $current_wd = substr(strtolower(date('D')), 0, 2);
        $wd_index = array_keys(self::weekDay_domain()); //i => code
        $wd_order = array_flip($wd_index); //code => i
        $current_wd_index = $wd_order[$current_wd];
        $days_diff = 0;
        $result_days = 0;
        for ($i = $current_wd_index + 1; $i < count($wd_order); $i++) {
            $days_diff++;
            $wd_code = $wd_index[$i];
            if ($wd_code === $weekday_code) {
                $result_days = $days_diff;
                break;
            }
        }
        if ($result_days === 0) {
            for ($i = 0; $i <= $current_wd_index; $i++) {
                $days_diff++;
                $wd_code = $wd_index[$i];
                if ($wd_code === $weekday_code) {
                    $result_days = $days_diff;
                    break;
                }
            }
        }
        $d = $result_days === 1 ? strtotime('+1 day') : strtotime('+' . $result_days . ' days');
        return mktime(0, 0, 0, date('n', $d), date('j', $d), date('Y', $d));
    }

    /**
     * @param $point string
     * @return array
     */
    public static function hoursPointParse($point)
    {
        if (empty($point) || !is_string($point)) {
            return [];
        }
        $parts = array_map('trim', explode(':', $point));
        $hours = preg_replace('/\D/i', '', $parts[0]);
        if (!is_numeric($hours)) {
            return [];
        }
        $hours = intval($hours);
        if (count($parts) < 2) {
            $minutes = 0;
        } else {
            $minutes = preg_replace('/\D/i', '', $parts[1]);
            if (!is_numeric($minutes)) {
                return [];
            }
            $minutes = intval($minutes);
        }
        if ($hours > 23 || $minutes > 59) {
            return [];
        }
        return [$hours, $minutes];
        //use list($hours, $minutes) = hoursPointParse('15:25')
    }

    public static function currentHoursPoint()
    {
        return [intval(date('G')), intval(date('i'))]; //--> [hours, minutes]
        //use list($hours, $minutes) = currentHoursPoint()
    }

    /**
     * @param $point array
     * @return bool
     */
    public static function hoursPointValidate($point): bool
    {
        if (empty($point) || !is_array($point) || count($point) !== 2) {
            return false;
        }
        if ($point[0] < 0 || $point[0] > 23 || $point[1] < 0 || $point[1] > 59) {
            return false;
        }
        return true;
    }

    public static function hoursRangeToMunutesRange($hours_range): ValueArray
    {
        $r = (new ValueArray)->trigger(__METHOD__);
        if (empty($hours_range) || !isset($hours_range['from']) || !isset($hours_range['until'])) {
            return $r->error('Wrong hours range format', 'wrong_hours_range');
        }
        if (!in_array(strlen($hours_range['from']), [4, 5])) {
            return $r->error('Wrong hours range start format', 'wrong_start');
        }
        if (!in_array(strlen($hours_range['until']), [4, 5])) {
            return $r->error('Wrong hours range end format', 'wrong_end');
        }
        $from = self::hoursPointParse($hours_range['from']);
        if (!self::hoursPointValidate($from)) {
            return $r->error("Wrong range begin", 'wrong_start');
        }
        $from_min = $from[0] * 60 + (int) $from[1];
        $until = self::hoursPointParse($hours_range['until']);
        if (!self::hoursPointValidate($until)) {
            return $r->error('Wrong hours range end', 'wrong_end');
        }
        $until_min = $until[0] * 60 + (int) $until[1];

        if ($from_min >= $until_min) {
            return $r->error('Hours range end must be after begin', 'start_is_after_end');
        }
        return $r(['from' => $from_min, 'until' => $until_min]);
    }

    public static function hoursRangeListValidate($hours_range_list): ValueBoolean
    {
        $r = new ValueBoolean;
        TimeDictionary::load();
        if (empty($hours_range_list) || !is_array($hours_range_list)) {
            return $r->error(TimeDictionary::$wrong_hours_range_list, 'wrong_hours_range_list');
        }
        $last_range_end = 0;
        foreach ($hours_range_list as $hours_range) {
            if (!isset($hours_range['from']) || !isset($hours_range['until'])) {
                return $r->error(TimeDictionary::$wrong_hours_range, 'wrong_hours_range');
            }
            $range_str = $hours_range['from'] . '-' . $hours_range['until'];
            $min_range = self::hoursRangeToMunutesRange($hours_range);
            if (!$min_range->error) {
                if ($last_range_end > $min_range->value['from']) {
                    return $r->error(
                        $range_str . ': ' . TimeDictionary::$begin_of_range_before_ending_of_prev_range,
                        'range_begin_before_prev_range_end'
                    );
                }
                $last_range_end = $min_range->value['until'];
            } else {
                return $r->error($range_str . ': ' . $min_range->errorText, 'hours_to_minutes_conv_error');
            }
        }

        return $r->success();
    }

    /**
     * @param $hours_range array
     */
    public static function fitHoursRange($hours_range): ValueArray
    {
        $r = (new ValueArray)->trigger(__METHOD__);
        if (empty($hours_range) || empty($hours_range['from']) || empty($hours_range['until'])) {
            return $r->error("Wrong hours range format", 'wrong_range_format');
        }

        $from = self::hoursPointParse($hours_range['from']);
        if (!self::hoursPointValidate($from)) {
            return $r->error("Wrong hours range begin point", 'wrong_start');
        }
        $from_min = $from[0] * 60 + (int) $from[1];

        $until = self::hoursPointParse($hours_range['until']);
        if (!self::hoursPointValidate($until)) {
            return $r->error("Wrong hours range end point", 'wrong_end');
        }
        $until_min = $until[0] * 60 + (int) $until[1];

        $current = self::currentHoursPoint();
        $current_min = $current[0] * 60 + $current[1];

        $result = [
            'to_begin' => $from_min - $current_min,
            'working' => $current_min - $from_min,
            'to_end' => $until_min - $current_min,
            'finished' => $current_min - $until_min,
        ];

        $result['fit'] = $result['working'] > 0 && $result['to_end'] > 0;
        return $r($result);
    }

    private static function date_range_to_datetime_range($start, $end)
    {
        if ($start === 0) {
            $start = mktime(0, 0, 0);
        } else {
            $start = mktime(0, 0, 0, date('n', $start), date('j', $start), date('Y', $start));
        }
        if ($end === 0) {
            $end = mktime(23, 59, 59);
        } else {
            $end = mktime(23, 59, 59, date('n', $end), date('j', $end), date('Y', $end));
        }
        return [$start, $end];
        //use list($start, $end) = date_range_to_datetime_range('2023-02-15', '2024-02-15');
    }

    public static function parse_date_period_filter($filter, $lang = '')
    {
        DateDictionary::load();
        if (!is_array($filter)) {
            return [0, 0, DateDictionary::$wrong_date_range];
        }
        $start_date = empty($filter[self::DATE_PERIOD_START_FIELD])
            ? '' : $filter[self::DATE_PERIOD_START_FIELD];
        $end_date = empty($filter[self::DATE_PERIOD_END_FIELD])
            ? '' : $filter[self::DATE_PERIOD_END_FIELD];
        if (empty($start_date) && empty($end_date)) {
            return [0, 0, '']; //no filter
        }
        if (!is_string($start_date)) {
            return [0, 0, DateDictionary::$wrong_start_date];
        }
        if (empty($start_date)) {
            $start = 0;
        } else {
            $start = @strtotime($start_date);
            if ($start === false) {
                return [0, 0, DateDictionary::$wrong_start_date];
            }
        }

        if (!is_string($end_date)) {
            return [0, 0, DateDictionary::$wrong_end_date];
        }
        if (empty($end_date)) {
            $end = time();
        } else {
            $end = @strtotime($end_date);
            if ($end === false) {
                return [0, 0, DateDictionary::$wrong_end_date];
            }
        }
        list($start, $end) = self::date_range_to_datetime_range($start, $end);

        if ($start >= $end) {
            return [0, 0, DateDictionary::$dates_conflict];
        }
        $days_diff = ($end - $start) / (60 * 60 * 24);
        if ($days_diff > 100) {
            return [0, 0, DateDictionary::$maximum_dates_range_exceeded];
        }

        return [$start, $end, ''];
        //use list($start, $end, $errorText) = parse_date_period_filter([...], 'en');
    }

    public static function parse_date_period($start_date, $end_date, $as_string = true, $max_days_difference = 100)
    {
        DateDictionary::load();
        //for example if $as_string: 2020-03-01 + 2020-03-07 is Ok, 2020-03-11 + 2020-03-07 is wrong, 202056546 + oioiuoiu is wrong
        if (empty($start_date) && empty($end_date)) {
            return [0, 0, ''];
        }
        if ($as_string) {
            if (!is_string($start_date)) {
                return [0, 0, DateDictionary::$wrong_start_date];
            }
            if (empty($start_date)) {
                $start = 0;
            } else {
                $start = @strtotime($start_date);
                if ($start === false) {
                    return [0, 0, DateDictionary::$wrong_start_date];
                }
            }

            if (!is_string($end_date)) {
                return [0, 0, DateDictionary::$wrong_end_date];
            }
            if (empty($end_date)) {
                $end = 0;
            } else {
                $end = @strtotime($end_date);
                if ($end === false) {
                    return [0, 0, DateDictionary::$wrong_end_date];
                }
            }
        } elseif ($start_date && (!is_numeric($start_date) || $start_date < 0)) {
            return [0, 0, DateDictionary::$wrong_start_date];
        } elseif ($end_date && (!is_numeric($end_date) || $end_date < 0)) {
            return [0, 0, DateDictionary::$wrong_end_date];
        } else {
            $start = (int) $start_date;
            $end = (int) $end_date;
        }

        list($start, $end) = self::date_range_to_datetime_range($start, $end);

        if ($start >= $end) {
            return [0, 0, 'dates conflict: ' . date('Y-m-d H:i', $start) . ' - ' . date('Y-m-d H:i', $end)];
        }
        $days_diff = ceil(($end - $start) / (60 * 60 * 24));
        if ($days_diff > $max_days_difference) {
            return [0, 0, DateDictionary::maximum_period_length_n_days($max_days_difference)];
        }

        return [$start, $end, ''];
        //use list($start, $end, $errorText) = parse_date_period(...);
    }

    public static function get_date_range_by_month_index($month_index)
    {
        if (
            empty($month_index) || !is_numeric($month_index)
            || $month_index < 1 || $month_index > 12
        ) {
            return [false, false];
        }
        $current_month = date('n');
        $current_year = date('Y');
        //if month = 12 and current = 1 then we use prev. year
        $year = $current_month < $month_index ? $current_year - 1 : $current_year;
        $date_start = mktime(0, 0, 0, $month_index, 1, $year);
        $date_end = mktime(23, 59, 59, $month_index, self::get_month_length($month_index, $year), $year);
        return [$date_start, $date_end];
    }

    public static function get_month_length($month_index, $year)
    {
        return (int) date('t', mktime(0, 0, 0, $month_index, 1, $year));
        //t - Количество дней в указанном месяце (28 - 31)
        //cal_days_in_month(CAL_GREGORIAN, $month_index, $yaer); //requires to compile php with calendar support
    }

    /**
     *
     * @param int $date_start
     * @param int $date_end
     * @param bool $include_1st_day 2020-01-01 - 2020-01-05 = 5 if true, 4 if false
     * @return int
     */
    public static function dates_diff_in_days($date_start, $date_end, $include_1st_day = true)
    {
        return (new \DateTime())
            ->setTimestamp($date_start)
            ->diff((new \DateTime())->setTimestamp($date_end))
            ->days
            + ($include_1st_day ? 1 : 0);
    }

    public static function parse_date_string($date)
    {
        if (empty($date) || !is_string($date)) {
            return 0;
        }
        $timestamp = @strtotime($date);
        if ($timestamp === false) {
            return 0;
        }
        return mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
    }
}
