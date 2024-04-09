<?php

/**
* Integration with egaug.es API to track solar panel power producing and selling to the power provider
* Made for project in Dominican Republic (Casa De Campo)
*/

/*read https://www.egauge.net/media/support/docs/egauge-xml-api.pdf
Unix time-stamps use U32 format, while integer register values use S64 format. Any decimal values use the IEEE-754
64-bit floating point format.
For additional information and examples, please visit http://egauge.net/support/kb/xmlapi

last request:
https://(device-name-here).egaug.es/cgi-bin/egauge-show?C&m&c&n=4325&Z=LST4&w=1607468340
alternative end-point: https://(device-name-here).d.egauge.net/
 *
*/
class EgaugeApiClient
{
    const
        ENDPOINT_CSV_REPORT = 'egauge-show',
        USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.83 Safari/537.36',
        MAX_CSV_RESPONSE_ROWS_QTY = 4325, //3 days +5 records
        SETTINGS_DEVICE = 'device',
        SETTINGS_USED_PRICE = 'used_price',
        SETTINGS_GENERATED_PRICE = 'generated_price'
    ;

    private
        $endpoint = '',
        $host = '',
        $referer = '',
        $used_price = 0,
        $generated_price = 0,
        $time_zone_code = 'LST4',
        $time_point = 0,
        $last_response = '',
        $log = [],
        $last_error = '';

    public function __construct($settings = [])
    {
        if (!empty($settings[self::SETTINGS_DEVICE])) {
            $this->endpoint = 'https://' . $settings[self::SETTINGS_DEVICE] . '.egaug.es/cgi-bin/';
            $this->host = $settings[self::SETTINGS_DEVICE] . '.egaug.es';
            $this->referer = 'https://' . $settings[self::SETTINGS_DEVICE] . '.egaug.es/5B857/';
            //I don't know what 5B857 means, but egaug.es redirects me for every device name
        } else {
            $this->log[] = 'Device name not found';
            return;
        }
        if (!empty($settings[self::SETTINGS_USED_PRICE])) {
            $this->used_price = $settings[self::SETTINGS_USED_PRICE];
        } else {
            $this->log[] = 'Price of used kWh not found';
            return;
        }
        if (!empty($settings[EgaugeSolarTraceModel::SETTINGS_GENERATED_PRICE])) {
            $this->generated_price = $settings[EgaugeSolarTraceModel::SETTINGS_GENERATED_PRICE];
        } else {
            $this->log[] = 'Price of generated kWh not found';
            return;
        }
        $this->time_point = time();
    }

    public function can_connect()
    {
        return $this->endpoint && $this->used_price && $this->generated_price;
    }

    public function get_last_error()
    {
        return $this->last_error;
    }

    public function flash_log()
    {
        $tmp = implode("\n", $this->log);
        $this->log = [];
        return $tmp;
    }

    private function request_contents($url)
    {
        $context = array(
            "ssl" => array(
                //without that gets Peer certificate CN=`d.egauge.net\' did not match expected CN=`tracesolar43433.egaug.es\'`
                "verify_peer" => false,
                "verify_peer_name" => false,
                //"allow_self_signed"=>true,
            ),
            'http' => array(
                'method' => "GET",
                'header' => [
                    //"Accept-Encoding: gzip, deflate, br",
                    "Accept-Encoding: br",
                    "Accept-Language: en,en-US;q=0.9,en;q=0.8",
                    "Host: " . $this->host,
                    "Referer: " . $this->referer,
                    "Accept: text/csv,application/csv,application/x-csv",
                    "User-Agent: " . self::USER_AGENT
                ],
                'timeout' => 300,  //5 Minutes
            ),
        );
        try {
            $this->last_response = file_get_contents($url, false, stream_context_create($context));
        } catch (\Exception $e) {
            $this->log[] = 'request_contents exception: ' . var_export($e, 1)
                . ', url ' . $url;
            $this->log_http_response_header();
            return false;
        }
        $this->log_http_response_header();
        return true;
    }

    private function log_http_response_header()
    {
        global $http_response_header;
        if (is_array($http_response_header)) {
            $this->log[] = "response headers:\n" . implode("\n", $http_response_header);
        }
    }

    /*
http://SERVER/cgi-bin/egauge-show?a&E&T=1514764800,1483228800
returns data for January 1 2018 00:00:00 UTC, and January 1 2017 00:00:00 UTC respectively,
using epoch-relative values and requesting total and virtual registers.

The T parameter - specific points in time. It expects a comma separated list of Unix time-stamps.

The E parameter requests the values be relative to the Date and Time when recording started.
This needs to be set correctly in Settings -> Date & time when recording started.
It effectively makes the reading start at zero when the date and time when recording started is set to,
otherwise the raw database value could be arbitrary. This requires firmware v3.02 or greater.

The a parameter requests total and virtual registers, such as "Usage" and "Generation".
     * This is optional.

     *  */

    private function request_csv_file($start_date, $end_date = false)
    {
        /*
a - Requests that the totals and virtual registers calculated from the physical register values be included
        as the first columns in each row. These values are
        calculated according to the Totaling and Virtual Register rules configured for the device.
         *
E - Requests that values are output relative to epoch. I.e., the value at ”Date & time when recording started” will be zero.
b - Requests the output be returned in the data backup format.
c - Requests the output be returned in CSV (comma-separated value) format.
         *
e - Requests the output of one extra data point beyond the requested range. This
        is similar, but not identical, to passing a value of N + 1 for parameter n. This
        reason the two are not identical is because the data-base granularity may be
        coarser than requested. For example, you may be requesting data at one minute
        granularity, but if the data-base has only 1-hour granularity available, passing
        N + 1 for parameter n has no effect, whereas e will ensure that the hourly datapoint just beyond the last requested data-point is also included in the output.
        This parameter has no effect when the T or w parameters are specified. This
        parameter was introduced with firmware v1.2.
         *
m - Specifies that n and s parameters are specified in units of minutes.
h - Specifies that n and s parameters are specified in units of hours.
d - Specifies that n and s parameters are specified in units of days.
S - Specifies that n and s parameters are specified in units of seconds
         *
C - Specifies that the returned data be delta-compressed. That is, after the first
    row of data, each subsequent row’s columns are expressed as a difference relative to the previous row’s column-values.
    When combined with the CSV (c) parameter, the first row is always omitted.
         *
n (Integer, U32) - Specifies the maximum number of rows to be returned.
         *
s (Integer, U32) - Specifies the number of rows to skip after outputting a row. For example,
    h&s=23 would skip 23 hours worth of data after a row is output, and would
    be equivalent to d.
         *
f (Integer, U32) - Specifies the timestamp of the first row to be returned.
         *
t (Integer, U32) - Specifies the timestamp of the last row to be returned.
         *
w (Integer, U32) - Requests that only data newer than the specified timestamp returned. If the
    timestamp lies in the future, the query will complete immediately returning an
    empty data element whose wait time attribute indicates how many seconds
    have to elapse before data younger than the specified timestamp will be available.
         *
T (Integer-list, U32) - Specifies a list of timestamps, ordered by decreasing value (younger to older)
    for which to return data rows.
         *
Z (string) - Specifies the time-zone to use when exporting CSV data. The format
    of this string is described at http://www.opengroup.org/onlinepubs/
    009695399/basedefs/xbd chap08.html under environment variable TZ.
    As of firmware v1.12, it is possible to omit the value for this parameter. In this
    case, the device converts time-stamps using the device-local time-zone
    (specified through setting “Time Zone” in the “Date & Time” dialog).
*/
        $params = [
            //'c' => '', //marks csv output
            //'m' => '', //one record per minute
            'n' => self::MAX_CSV_RESPONSE_ROWS_QTY,
            'Z' => $this->time_zone_code, //time zone, LST4 - Dom.Rep. (-4)
            //'a'=> '', //Requests that the totals and virtual registers
            //C - Specifies that the returned data be delta-compressed
        ];
        if ($end_date) { //records are ordered by date descending
            $params['f'] = $end_date; //fisrt date
            $params['t'] = $start_date; //last date
        } else {
            $params['w'] = $start_date; //newer than start date
        }
        $url = $this->endpoint . self::ENDPOINT_CSV_REPORT . '?C&m&c&' . http_build_query($params);
        //https://tracesolar43433.egaug.es/cgi-bin/egauge-show?m&c&n=500&w=1601929200&Z=LST4
        $attempts = 2;
        while ($attempts > 0) {
            $result = $this->request_contents($url);
            $attempts -= 1;
            if ($result) {
                return true;
            }
            sleep(5);
        }
        return false;
    }

    private function extract_data_array(&$csv_lines)
    {
        $lines = explode("\n", $csv_lines);
        $result = [];
        $errors = [];
        for ($i = 1; $i < count($lines); $i++) {
            if (empty($lines[$i])) {
                continue;
            }
            $row = str_getcsv($lines[$i], ',');

            if (empty($row) || count($row) < 3) {
                $errors[] = 'Fail to parse ' . $lines[$i];
                continue;
            }

            $result[] = [$row[0], $row[1], $row[2]]; //"Date & Time","Usage [kWh]","Generation [kWh]"
        }
        return ['extracted' => $result, 'errors' => $errors];
    }

    public function download_csv_report($start_date, $end_date = false)
    {
        $this->log[] = 'download_csv_report(' . var_export([$start_date, $end_date], 1) . ')';
        $error = '';
        $this->last_response = '';
        if (!$this->can_connect()) {
            $this->last_error = __METHOD__ . ": Trying to use API without connection settings";
            return [];
        }

        if (empty($start_date) || !is_numeric($start_date)) {
            $error = 'wrong start_date format';
        } elseif (!$this->request_csv_file($start_date, $end_date)) {
            $error = 'file request failed';
        } elseif (empty($this->last_response)) {
            $error = "response is empty";
        } else {
            $result = $this->extract_data_array($this->last_response);
            if (!empty($result['errors']) || empty($result['extracted'])) {
                $error = "CSV parser failed: " . var_export($result['errors'], 1);
            }
        }
        $this->last_response = '';
        if ($error) {
            $this->last_error = __METHOD__ . ": request_csv_file failed - " . $error;
            return [];
        }

        return $result['extracted'];
    }

    public function test_csv_report()
    {
        if (!$this->can_connect()) {
            return [
                'log' => $this->flash_log(),
                'error' => 'Trying to use API without connection settings',
            ];
        }
        $start_date = strtotime('-30 minutes');
        $this->last_response = '';
        $params = [
            'n' => 3,
            'Z' => $this->time_zone_code, //time zone, LST4 - Dom.Rep. (-4)
        ];
        $params['w'] = $start_date; //newer than start date
        $url = $this->endpoint . self::ENDPOINT_CSV_REPORT . '?C&m&c&' . http_build_query($params);
        //https://(your-device-id-here).egaug.es/cgi-bin/egauge-show?m&c&n=500&w=1601929200&Z=LST4
        $result = $this->request_contents($url);
        return [
            'start date' => date('c', $start_date),
            'request_csv_file result' => $result,
            'response' => $this->last_response,
            'error_get_last' => error_get_last(),
            'log' => $this->flash_log(),
            'error' => $this->get_last_error(),
        ];
    }

}
