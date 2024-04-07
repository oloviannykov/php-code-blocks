<?php
namespace App\Integrations;

//todo: create constant LOG_PATH

class ValueOrError
{
    public $value = NULL, $trigger = '', $notFound = false, $error = false, $errorText = '', $errorCode = '';
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

class TelegramBot
{
    const BASE_BOT_URL = 'https://api.telegram.org/bot';
    const LOG_FILE = '/telegram_bot';

    const LIMIT__SYMBOLS_PER_MESSAGE = 4096;
    const BOT_LIMIT__SENDED_FILE_SIZE_MB = 50;
    const BOT_LIMIT__MESSAGES_PER_SECOND = 30;
    const BOT_LIMIT__API_REQUESTS_PER_SECOND = 30;
    const LIMIT_ERROR_CODE = 429;
    const FORBIDDEN_ERROR_CODE = 403;

    const BOT_CREATION_TUTORIAL_LINK = 'https://core.telegram.org/bots/features#creating-a-new-bot';
    const BOT_FATHER_LINK = 'https://t.me/botfather';
    const MY_ID_BOT_LINK = 'https://t.me/myidbot';

    private
    $admin_chat_id = '',
    $endpoint = '',
    $endpoint_censored = '',
    $curl = null,
    $log_path = '',
    $api_token = '',
    $bot_name = '',
    $callback_key = '',
    $callback_url = '';

    /* fragments from telegramm-bot-core:
     *
    preg_match('/(\d+):[\w\-]+/', $api_key, $matches);
    if (!isset($matches[1])) {
        throw new TelegramException('Invalid API KEY defined!');
    }
    $this->bot_id  = (int) $matches[1];
     *
    //temp stream open
    $temp_stream_handle = fopen('php://temp', 'wb+');
     *
    //temp stream end
    if (is_resource(self::$debug_log_temp_stream_handle)) {
        rewind($temp_stream_handle);
        $stream_contents = stream_get_contents($temp_stream_handle);
        self::debug(sprintf($message, $stream_contents));
        fclose($temp_stream_handle);
        $temp_stream_handle = null;
    }
     *  */

    public function __construct()
    {
        $this->log_path = LOG_PATH . self::LOG_FILE . '_' . date('YmdH') . '.txt';
        $s = []; //TODO: load settings here
        if (empty($s['TELEGRAM_API_TOKEN'])) {
            $this->log('init', 'API token was not found in ' . json_encode($s));
            return;
        }
        $this->api_token = $s['TELEGRAM_API_TOKEN'];
        $this->bot_name = empty($s['TELEGRAM_BOT_NAME']) ? '' : $s['TELEGRAM_BOT_NAME'];
        $this->callback_key = empty($s['TELEGRAM_CALLBACK_KEY']) ? '' : $s['TELEGRAM_CALLBACK_KEY'];
        $this->callback_url = empty($s['TELEGRAM_CALLBACK_URL']) ? '' : $s['TELEGRAM_CALLBACK_URL'];
        $this->endpoint = self::BASE_BOT_URL . $this->api_token . "/";
        $this->endpoint_censored = self::BASE_BOT_URL . substr($this->api_token, 0, 5) . "_/";
        $this->admin_chat_id = empty($s['REPORTS_TELEGRAM_CHAT_ID']) ? '' : $s['REPORTS_TELEGRAM_CHAT_ID'];
    }

    private function curl_init(): void
    {
        $this->curl = curl_init();
        curl_setopt_array($this->curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 480, //seconds = 8 minutes
            CURLOPT_CONNECTTIMEOUT => 480, //seconds = 8 minutes
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => [
                "Connection: Keep-Alive",
                "Keep-Alive: timeout=480", //minimum seconds = 8 minutes
                "Content-type: application/json",
                //"Content-type: application/x-www-form-urlencoded"
            ],
        ]);
    }

    private function log($actor, $data): void
    {
        $message = date('Y-m-d H:i:s') . " - " . $actor . "\n" . $data . "\n-----\n";
        file_put_contents($this->log_path, $message, FILE_APPEND);
    }

    public function generate_callback_url($url, $key): string
    {
        if (empty($url) || empty($key)) {
            return '';
        }
        $parts = @parse_url($url);
        $valid = $parts !== false && !empty($parts['host'])
            && !empty($parts['scheme']) && $parts['scheme'] === 'https'
            && empty($parts['query']) && empty($parts['fragment']);
        return $valid ? $url . '?' . http_build_query(['key' => $key]) : '';
    }

    private function get_callback_url(): ValueString
    {
        $r = new ValueString();
        $url = $this->callback_url;
        if (empty($url) || empty($this->callback_key)) {
            return $r->error('URL or key was not found', 'url_or_key_required');
        }
        $fullUrl = $this->generate_callback_url($url, $this->callback_key);
        if (empty($fullUrl)) {
            return $r->error('Invalid HTTPS URL', 'invalid_https_url');
        }
        return $r($fullUrl);
    }

    public function set_bot_listener_if_not_found($botToken, $botName, $listenerUrl, $listenerKey): ValueBoolean
    {
        $r = new ValueBoolean;

        //get current callback, return error if is set
        if ($this->endpoint) {
            $response = @file_get_contents($this->endpoint . "getWebhookInfo");
            if (empty($response)) {
                return $r->error('getWebhookInfo request error', 'info_request_error');
            }
            $response_data = json_decode($response, 1);
            if (empty($response_data['ok'])) {
                return $r->error(
                    'getWebhookInfo response: ' . json_encode($response_data['result'], JSON_UNESCAPED_UNICODE),
                    'info_error_response'
                );
            }
            if (!empty($response_data['result']['url'])) {
                return $r->error('Webhook is already set', 'listener_already_set');
            }
        }


        //set new callback
        $this->api_token = $botToken;
        $this->bot_name = $botName;
        $this->callback_url = $listenerUrl;
        $this->callback_key = $listenerKey;
        $this->endpoint = self::BASE_BOT_URL . $this->api_token . "/";
        $this->endpoint_censored = self::BASE_BOT_URL . substr($this->api_token, 0, 5) . "_/";

        $call_back = self::generate_callback_url($listenerUrl, $listenerKey);
        if (empty($call_back)) {
            return $r->error('Can not create HTTPS callback URL', 'callback_url_error');
        }
        //attach the bot to requests listener (api.telegram.org/bot<Токен>/setWebhook?url=<https to the listener>)
        $get_request = $this->endpoint . "setWebhook?url=" . urlencode($call_back);
        $response = @file_get_contents($get_request);
        if (empty($response)) {
            return $r->error('setWebhook request error', 'webhook_request_error');
        }
        $response_data = json_decode($response, 1); //'{"ok":true,"result":true,"description":"Webhook was set"}'
        if (empty($response_data['ok'])) {
            return $r->error(
                'setWebhook response: ' . json_encode($response_data, JSON_UNESCAPED_UNICODE),
                'webhook_error_response'
            );
        }

        return $r->success();
    }

    public function set_bot_listener(): ValueBoolean
    {
        $r = new ValueBoolean();
        $call_back = self::get_callback_url();
        if ($call_back->error) {
            return $r->error($call_back->errorText, $call_back->errorCode, $call_back->trigger);
        }

        /*
        1. You will not be able to receive updates using getUpdates for as long as an outgoing webhook is set up.
        2. To use a self-signed certificate, you need to upload your public key certificate using certificate parameter. Please upload as InputFile, sending a String will not work.
        3. Ports currently supported for Webhooks: 443, 80, 88, 8443.
        If you're having any trouble setting up webhooks, please check out https://core.telegram.org/bots/webhooks.

        You'll need a server that:
        Supports IPv4, IPv6 is currently not supported for webhooks.
        Accepts incoming POSTs from subnets 149.154.160.0/20 and 91.108.4.0/22 on port 443, 80, 88, or 8443.
        Is able to handle TLS1.2(+) HTTPS-traffic.
        Provides a supported, non-wildcard, verified or self-signed certificate.
        Uses a CN or SAN that matches the domain you’ve supplied on setup.
        Supplies all intermediate certificates to complete a verification chain.
        That’s almost all there’s to it.
        If you decide to limit traffic to our specific range of addresses, keep an eye on this https://core.telegram.org/bots/webhooks.
        Our IP-range might change in the future.
        */
        $get_request = $this->endpoint . "setWebhook?url=" . urlencode($call_back->stringResult);
        $response = file_get_contents($get_request);
        $this->log(
            'set_bot_listener',
            var_export(['request' => $get_request, 'response' => $response], 1)
        );
        if (empty($response)) {
            return $r->error('Empty response', 'empty_response');
        }
        $response_data = json_decode($response, 1);
        //'{"ok":true,"result":true,"description":"Webhook was set"}'
        if (empty($response_data['ok'])) {
            return $r->error('Error response: ' . $response, 'error_response');
        }

        return $r->success();
    }

    public function unset_bot_listener(): ValueBoolean
    {
        $r = new ValueBoolean();
        $get_request = $this->endpoint . "deleteWebhook";
        $response = file_get_contents($get_request);
        $this->log(
            'unset_bot_listener',
            var_export(['request' => $get_request, 'response' => $response], 1)
        );
        if (empty($response)) {
            return $r->error('Empty response', 'empty_response');
        }
        $response_data = json_decode($response, 1);
        //'{"ok":true,"result":true,"description":"..."}'
        if (empty($response_data['ok'])) {
            return $r->error('Error response: ' . $response, 'error_response');
        }

        return $r->success();
    }

    public function get_bot_listener_info(): ValueArray
    {
        $r = new ValueArray();
        $get_request = $this->endpoint . "getWebhookInfo";
        $response = file_get_contents($get_request);
        $this->log(
            'get_bot_listener_info',
            var_export(['request' => $get_request, 'response' => $response], 1)
        );
        if (empty($response)) {
            return $r->error('Empty response', 'empty_response');
        }
        $response_data = json_decode($response, 1);
        if (empty($response_data['ok'])) {
            return $r->error('Error response: ' . $response, 'error_response');
        }
        if (empty($response_data['result']['url'])) {
            return $r->notFound();
        }
        //on callback exist:
        //{response:{ok:true, result:{url:"https://...com/..._listener.php",
        //  has_custom_certificate:false, pending_update_count:2, last_error_date:1568131809,
        //  last_error_message:"Connection refused", max_connections:40}}}

        //on no callback:
        //{response:{ok:true, result:{url:"", has_custom_certificate:false, pending_update_count:2}}}

        /* response example: {ok:true, result:{
            url:'https://(server)/api/telegram_listener?key=(key)', has_custom_certificate:false,
            pending_update_count:0, max_connections:40, ip_address:'181.36.xxx.xxx',
          }
        } */
        return $r->setValue($response_data);
    }

    //deprecated, replaced with webhoock
    public function get_last_commands_report($last_records_qty = 100): array
    {
        $updates = $this->get_last_updates($last_records_qty);
        if ($updates->error) {
            errors_collect('TelegramBot.get_last_updates', $updates->getDump());
            return [];
        }
        $result = [];
        foreach ($updates->value as $message) {
            // message example:
            //{ok:true, result:[
            //  {update_id:98743, message:{
            //      message_id:135, date:1573507220, text:"/start",
            //      from:{id:12345, is_bot:false, first_name:"Gena", last_name:"Dominicus", language_code:"en"},
            //      chat:{id:479345, first_name:"Gena", last_name:"Dominicus", type:"private"},
            //      entities:[{offset:0, length:6, type:"bot_command"}]}
            //  }
            //]}
            if (empty($message['message']['entities'])) {
                continue;
            }
            $msg = $message['message'];
            $is_command = false;
            foreach ($msg['entities'] as $entity) {
                if (!empty($entity['type']) && $entity['type'] === 'bot_command') {
                    $is_command = true;
                    break;
                }
            }
            if (empty($msg['text']) || !$is_command) {
                continue;
            }
            $first_name = empty($msg['from']['first_name']) ? '' : $msg['from']['first_name'];
            $last_name = empty($msg['from']['last_name']) ? '' : $msg['from']['last_name'];
            $result[] = [
                'date' => empty($msg['date']) ? false : intval($msg['date']),
                'from' => $first_name . ' ' . $last_name,
                'chat_id' => (empty($msg['chat']['id']) ? 'unknown' : $msg['chat']['id']),
                'full_data' => $message,
            ];
        }

        return $result; //lines array

    }

    public function get_last_updates($end_offset = 100): ValueArray
    {
        $r = new ValueArray();
        /* The negative offset can be specified to retrieve updates starting from -offset
        update from the end of the updates queue. */
        $get_request = $this->endpoint . "getUpdates?offset=-" . $end_offset;
        $response = file_get_contents($get_request);
        $this->log(
            'get_last_updates',
            var_export(['request' => $get_request, 'response' => $response], 1)
        );
        if (empty($response)) {
            return $r->error('Empty response', 'empty_response');
        }
        $response_data = json_decode($response, 1);
        if (empty($response_data['ok']) || !isset($response_data['result'])) {
            return $r->error('Error response: ' . $response, 'error_response');
        }

        return $r->setValue($response_data['result']);
        /* --> {
        {
          update_id:65478696,
          message:{
            message_id:9, date:1568130565, text:'test',
            from:{id:53.., is_bot:false, first_name:'Xxxx', last_name:'xxx', language_code:'en'},
            chat:{id:87.., first_name:'Xxxx', last_name:'xxx', type:'private'},
          },
        },
        {
          update_id:4758565,
          message:{
            message_id:10,
            from:{id:..., is_bot:false, first_name:'Xxx', last_name:'xxx', language_code:'en'},
            chat:{id:5..., first_name:'Xxx', last_name:'xxx', type:private'},
            date:1568130682,
            text:'/start',
            entities:{
              {offset:0, length:6, type:'bot_command'},
            },
          },
        },
        ...
        */
    }

    public function send_post_request($method, $data): array
    {

        $this->curl_init();
        $log_data = $data;
        if (!empty($log_data['reply_markup'])) {
            $log_data['reply_markup'] = '...';
        }
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);
        $url = $this->endpoint . $method;
        $url_censored = $this->endpoint_censored . $method;
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $data,
        ]);
        $attempts_limit = 3;
        $attemts_left = $attempts_limit;
        $result = [];
        while ($attemts_left > 0) {
            $result = [
                "ok" => false,
                "request" => [$method, $log_data],
                "error" => 'unknown error',
            ];
            $attempt_no = $attempts_limit - $attemts_left + 1;
            $this->log('send_post_request starting attempt #' . $attempt_no, $method);

            if ($attempt_no > 1) {
                sleep(5);
            }
            $resultCurl = curl_exec($this->curl);

            if ($resultCurl === false) {
                $result["error"] = curl_errno($this->curl) . ' ' . curl_error($this->curl);
                $result["curl_error"] = true;
                $this->log('send_post_request attempt #' . $attempt_no . ' result', var_export($result, 1));
                $attemts_left--;
                continue;
            }
            $resultJson = json_decode($resultCurl, 1);
            if ($resultJson === null) {
                $result["raw_response"] = $resultCurl;
                $result["error"] = json_last_error() . ' ' . json_last_error_msg();
                $result["json_error"] = true;
                $this->log('send_post_request attempt #' . $attempt_no . ' result', var_export($result, 1));
                $attemts_left--;
                continue;
            }
            // error response: {ok:false, error_code:400, description:'Bad Request: chat not found'}
            $error_code = empty($resultJson['error_code']) ? 0 : (int) $resultJson['error_code'];
            if ($error_code === self::LIMIT_ERROR_CODE) {
                $this->log('send_post_request attempt #' . $attempt_no . ' limit error', var_export($result, 1));
                $attemts_left--;
                continue;
            }
            if ($error_code === self::FORBIDDEN_ERROR_CODE) {
                $result['error'] = $resultJson['description'];
                $this->log('send_post_request bot forbidden', var_export($result, 1));
                errors_collect(__METHOD__, $result);
                break;
            }
            if ($error_code > 0) {
                $result["raw_response"] = $resultCurl;
                $result['error'] = $resultJson['description'];
                $this->log('send_post_request attempt #' . $attempt_no . ' error', var_export($result, 1));
                errors_collect(__METHOD__, $result);
                break;
            }
            $log_response = $resultJson;
            if (!empty($log_response['result']['from'])) {
                $log_response['result']['from'] = '...';
            }
            if (!empty($log_response['result']['reply_markup'])) {
                $log_response['result']['reply_markup'] = '...';
            }
            if (!empty($log_response['result']['entities'])) {
                $log_response['result']['entities'] = '...';
            }
            if (!empty($log_response['result']['text'])) {
                $log_response['result']['text'] = '...';
            }
            $result = [
                "ok" => true,
                "request" => [$method, $log_data],
                "response" => $log_response,
            ];
            $this->log('send_post_request attempt #' . $attempt_no . ' result', var_export($result, 1));
            $result = $resultJson;
            break;
        }
        curl_close($this->curl);
        return $result;
    }

    private function upload_file($method, $get_params, $file_field, $path)
    {
        $this->curl_init();
        $real_path = realpath($path);
        $query = '?' . http_build_query($get_params);
        $url = $this->endpoint . $method . $query;
        $url_censored = $this->endpoint_censored . $method . $query;
        $post = [
            $file_field => new \CURLFile($real_path, mime_content_type($real_path)),
        ];
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                "Content-Type:multipart/form-data"
            ),
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_INFILESIZE => filesize($real_path)
        ]);
        $resultCurl = curl_exec($this->curl);
        if ($resultCurl === false) {
            return [
                "ok" => false,
                "request" => [$url, $post],
                "error" => curl_errno($this->curl) . ' ' . curl_error($this->curl),
                "curl_error" => true
            ];
        }
        $resultJson = json_decode($resultCurl, 1);
        if ($resultJson === null) {
            return [
                "ok" => false,
                "request" => [$url, $post],
                "raw_response" => $resultCurl,
                "error" => json_last_error() . ' ' . json_last_error_msg(),
                "json_error" => true
            ];
        }
        $error_code = empty($resultJson['error_code']) ? 0 : (int) $resultJson['error_code'];
        if ($error_code > 0) {
            errors_collect(__METHOD__, [
                "request" => [$url, $post],
                "raw_response" => $resultCurl,
            ]);
        }
        curl_close($this->curl);
        return $resultJson;
    }

    public function injectKeyboard(&$request, $options)
    {
        $keyboard = [];
        foreach ($options as $caption => $text_data) {
            $keyboard[] = array(
                'text' => $caption,
                'callback_data' => $text_data,
                //Optional. Data to be sent in a callback query to the bot when button is pressed, 1-64 bytes
            );
        }
        $request['reply_markup'] = json_encode(
            array(
                'inline_keyboard' => [$keyboard] //all buttons on one line
                //'keyboard' => $keyboard,
            )

        );
    }

    public function sendMessageToAdmin($text, $parse_mode = 'HTML')
    {
        if (empty($this->admin_chat_id)) {
            errors_collect(__METHOD__, ['Admin chat id not found' => $text]);
            return [
                "ok" => false,
                "request" => $text,
                "error" => 'admin chat ID required',
            ];
        }
        return $this->sendMessage($this->admin_chat_id, $text, $parse_mode);
    }

    /*
    $parse_mode = [(null)|HTML|Markdown]
    */
    public function sendMessage(
        $chat_id,
        $text,
        $parse_mode = 'HTML',
        $disable_web_page_preview = true,
        $reply_options = []
    ): array {
        /*
        For $parse_mode='HTML' the following tags are currently supported:
            <b>bold</b>, <strong>bold</strong>
            <i>italic</i>, <em>italic</em>
            <a href="http://www.example.com/">inline URL</a>
            <a href="tg://user?id=123456789">inline mention of a user</a>
            <code>inline fixed-width code</code>
            <pre>pre-formatted fixed-width code block</pre>
        Please note:
            Only the tags mentioned above are currently supported.
            Tags must not be nested.
            All <, > and & symbols that are not a part of a tag or an HTML entity must be replaced with the corresponding HTML entities (< with &lt;, > with &gt; and & with &amp;).
            All numerical HTML entities are supported.
            The API currently supports only the following named HTML entities: &lt;, &gt;, &amp; and &quot;.
        Look at: https://core.telegram.org/bots/api#html-style
            */
        $args = ['chat_id' => $chat_id, 'text' => $text];
        if ($reply_options && is_array($reply_options)) {
            $this->injectKeyboard($args, $reply_options);
        }
        if (mb_strlen($text) > self::LIMIT__SYMBOLS_PER_MESSAGE) {
            errors_collect(__METHOD__, [
                'error' => 'maximum message length limit reached. cutting the end',
                'limit' => self::LIMIT__SYMBOLS_PER_MESSAGE,
                'text' => $text,
            ]);
            $text = mb_substr($text, 0, self::LIMIT__SYMBOLS_PER_MESSAGE);
        }
        if ($parse_mode !== null) {
            $args['parse_mode'] = $parse_mode;
        }
        if ($disable_web_page_preview) {
            $args['disable_web_page_preview'] = true;
        }

        return $this->send_post_request('sendMessage', $args);
    }

    public function sendPhotoFile($chat_id, $path, $caption = '')
    {
        if (empty($chat_id)) {
            return ['error' => 'invalid chat id ' . var_export($chat_id, 1)];
        }
        if (empty($path) || !is_file($path)) {
            return ['error' => 'invalid path ' . json_encode($path)];
        }
        //svg file got error 400 'Bad Request: IMAGE_PROCESS_FAILED'
        if (!empty($caption) && !is_string($caption)) {
            return ['error' => 'invalid caption ' . json_encode($caption)];
        }
        $args = ['chat_id' => $chat_id, 'caption' => $caption];

        return $this->upload_file('sendPhoto', $args, 'photo', $path);
    }

    public function sendLocation($chat_id, $coordinates): array
    {
        if (empty($chat_id)) {
            return ['error' => 'invalid chat id ' . var_export($chat_id, 1)];
        }
        if (!isset($coordinates['latitude']) || !is_numeric($coordinates['latitude'])) {
            return ['error' => 'invalid latitude ' . json_encode($coordinates)];
        }
        if (!isset($coordinates['longitude']) || !is_numeric($coordinates['longitude'])) {
            return ['error' => 'invalid longitude ' . json_encode($coordinates)];
        }
        $args = [
            'chat_id' => $chat_id,
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
        ];
        return $this->send_post_request('sendLocation', $args);
    }

    public function setChatPermissions($chat_id, array $permissions): array
    {
        if (empty($chat_id)) {
            return ['error' => 'invalid chat id ' . var_export($chat_id, 1)];
        }
        $args = [
            'chat_id' => $chat_id,
            'permissions' => json_encode($permissions)
        ];
        return $this->send_post_request('setChatPermissions', $args);
    }

    public function getChat($chat_id): array
    {
        if (empty($chat_id)) {
            return ['error' => 'invalid chat id ' . var_export($chat_id, 1)];
        }
        $args = ['chat_id' => $chat_id];
        return $this->send_post_request('getChat', $args);
    }

    public function getChatMembersCount($chat_id): array
    {
        $args = ['chat_id' => $chat_id];
        return $this->send_post_request('getChatMembersCount', $args);
    }
    public function getChatMember($chat_id, int $user_id): array
    {
        $args = ['chat_id' => $chat_id, 'user_id' => $user_id];
        return $this->send_post_request('getChatMember', $args);
    }

    /**
     * Broadcast a Chat Action.
     * @link https://core.telegram.org/bots/api#sendchataction
     * @param int|string    $chat_id
     * @param string    $action
     */
    public function sendChatAction($chat_id, $action): array
    {
        $validActions = explode(
            ',',
            'typing,upload_photo,record_video,upload_video,record_audio,upload_audio,upload_document,find_location'
        );

        if (empty($chat_id)) {
            return ['error' => 'invalid chat id ' . var_export($chat_id, 1)];
        }
        if (empty($action) && !in_array($action, $validActions)) {
            return ['error' => 'invalid action code ' . var_export($action, 1)];
        }
        $request = ['chat_id' => $chat_id, 'action' => $action];

        return $this->send_post_request('sendChatAction', $request);
    }

    public function getMyCommands(): array
    {
        return $this->send_post_request('getMyCommands', []);
    }

    public function testConnection(): array
    {
        return $this->send_post_request('getMe', []);
    }

    public function webhookRequestParse(): array
    {
        if (empty($this->callback_key)) {
            return ['key is not set', ''];
        }
        if (empty($_GET['key']) || $_GET['key'] !== $this->callback_key) {
            errors_collect(__METHOD__, ['wrong key' => $_GET]);
            return ['wrong key', ''];
        }
        $request = file_get_contents('php://input');
        if (empty($request)) {
            errors_collect(__METHOD__, 'empty request');
            return ['empty request', ''];
        }

        $result = self::webhookRequestParseJSON($request);
        if (!empty($result['error'])) {
            errors_collect(__METHOD__, [$result['error'] => $request]);
            return [$result['error'], $request];
        }
        return ['', $result];
    }


    public function webhookRequestParseJSON($json): array
    {
        if (empty($json)) {
            return ['error' => 'empty json'];
        }
        $data = json_decode($json, true);
        if ($data === false) {
            return ['error' => 'malformed json'];
        }
        $chat_id = '';
        $from = [];
        $text = '';
        $callback = '';
        $user_new_status = '';
        $until_date = 0;

        if (isset($data['message'])) { //bot command or text message
            /* {
                update_id:9876553,
                message:{
                  message_id:1627, date:1608813594, text:'/hello',
                  from:{id:66.., is_bot:false, first_name:Xxx', last_name:'xxx', language_code:'en'},
                  chat:{id:54.., first_name:'...', last_name:'xxx', type:'private'},
                }
            }*/
            $msg = $data['message'];
            $chat_id = empty($msg['chat']['id']) ? '' : $msg['chat']['id'];
            $text = empty($msg['text']) ? '' : $msg['text'];
            if (!empty($msg['chat']['first_name'])) {
                $from[] = $msg['chat']['first_name'];
            }
            if (!empty($msg['chat']['last_name'])) {
                $from[] = $msg['chat']['last_name'];
            }

        } elseif (isset($data['callback_query'])) { //button callback query
            /* {
             update_id:248254417,
             callback_query:{
                id:"234...79",
                from:{id:69..., is_bot:false, first_name:"Xxx", last_name:"xxx", language_code:"en"},
                message:{
                 message_id:1639,
                 from:{id:67.., is_bot:true, first_name:"suren_home", username:"suren_home_bot"},
                 chat:{"id":57.., first_name:"Gena", last_name:"xxx", type:"private"},
                 date:1608834554,
                 text:"Test buttons callback",
                 reply_markup:{inline_keyboard:[[{text:"button 1", callback_data:"{\"emp_id\":1,\"action\":\"action1\"}"}}]]}
                },
                chat_instance:"-1948409...",
                data:"{\"emp_id\":1,\"action\":\"action2\"}"
             }}
            */
            $query = $data['callback_query'];
            $msg = $query['message'];
            $callback = $query['data'];
            $chat_id = empty($msg['chat']['id']) ? '' : $msg['chat']['id'];
            $text = empty($msg['text']) ? '' : $msg['text'];
            if (!empty($msg['chat']['first_name'])) {
                $from[] = $msg['chat']['first_name'];
            }
            if (!empty($msg['chat']['last_name'])) {
                $from[] = $msg['chat']['last_name'];
            }

        } elseif (isset($data['my_chat_member'])) { //user state update
            /* {
             update_id:248254554,
             my_chat_member:{
              chat:{id:145..., first_name:"chef cambero", type:"private"},
              from:{id:145..., is_bot:false, first_name:"chef cambero"},
              date:1627079677,
              old_chat_member:{
                user:{id:70.., is_bot:true, first_name:"...", username:".._bot"},
                status:"member"},
              new_chat_member:{
                user:{id:70..., is_bot:true, first_name:"...", username:".._bot"},
                status:"kicked", until_date:0}
             }
            }
            */
            $member = $data['my_chat_member'];
            $chat_id = empty($member['chat']['id']) ? '' : $member['chat']['id'];
            if (!empty($member['chat']['first_name'])) {
                $from[] = $member['chat']['first_name'];
            }
            if (!empty($member['chat']['last_name'])) {
                $from[] = $member['chat']['last_name'];
            }
            $user_new_status = empty($member['new_chat_member']['status'])
                ? '' : $member['new_chat_member']['status'];
            $until_date = empty($member['new_chat_member']['until_date'])
                ? 0 : (int) $member['new_chat_member']['until_date'];
        } else {
            return [
                'unsupported_request_type' => $data
            ];
        }

        $result = array(
            'from' => empty($from) ? 'no name' : implode(' ', $from),
            'chat_id' => $chat_id,
            'text' => $text,
            'user_new_status' => $user_new_status,
            'until_date' => $until_date,
            'callback' => $callback,
        );

        return $result;
    }

    public function webhookResponseSuccess(): void
    {
        http_response_code(200);
        exit('ok');
    }

    public function webhookResponseError($error_text): void
    {
        http_response_code(400); //bad request
        echo $error_text;
        exit();
    }
}
