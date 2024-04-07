<?php
class Telegrama
{
    const BASE_BOT_URL = 'https://api.telegram.org/bot';
    const LIMIT__SYMBOLS_PER_MESSAGE = 4096;
    const BOT_LIMIT__MESSAGES_PER_SECOND = 30;
    const BOT_LIMIT__API_REQUESTS_PER_SECOND = 30;
    const LIMIT_ERROR_CODE = 429;
    const FORBIDDEN_ERROR_CODE = 403;

    private
    $error = '',
    $api_token = 'put-your-api-token-here',
    $admin_chat_id = 'put-your-chat-id-here',
    $endpoint = '';
    //$bot_name = 'your-bot-name-here';
    //invite link - t.me/(your-bot-name-here)_bot
    //For a description of the Bot API, see this page: https://core.telegram.org/bots/api

    //use @myidbot command /getid to get chat id

    public function __construct()
    {
        if (empty($this->api_token)) {
            $this->error = 'API token is required';
            return;
        }
        if (empty($this->admin_chat_id)) {
            $this->error = 'admin chat ID is required';
            return;
        }
        $this->endpoint = self::BASE_BOT_URL . $this->api_token . "/";
    }

    public function get_last_error()
    {
        return $this->error;
    }

    public function send_post_request($method, $data)
    {

        $curl = curl_init();
        curl_setopt_array($curl, [
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
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);
        $url = $this->endpoint . $method;
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $data,
        ]);
        $attempts_limit = 3;
        $attemts_left = $attempts_limit;
        $result = [];
        while ($attemts_left > 0) {
            $result = [
                "ok" => false,
                "request" => [$method, $data],
                "error" => 'unknown error',
            ];
            $attempt_no = $attempts_limit - $attemts_left + 1;

            if ($attempt_no > 1) {
                sleep(5);
            }
            $resultCurl = curl_exec($curl);

            if ($resultCurl === false) {
                $result["error"] = curl_errno($curl) . ' ' . curl_error($curl);
                $result["curl_error"] = true;
                $attemts_left--;
                continue;
            }
            $resultJson = json_decode($resultCurl, 1);
            if ($resultJson === null) {
                $result["raw_response"] = $resultCurl;
                $result["error"] = json_last_error() . ' ' . json_last_error_msg();
                $result["json_error"] = true;
                $attemts_left--;
                continue;
            }
            $error_code = empty($resultJson['error_code']) ? 0 : (int) $resultJson['error_code'];
            if ($error_code === self::LIMIT_ERROR_CODE) {
                $attemts_left--;
                continue;
            }
            if ($error_code === self::FORBIDDEN_ERROR_CODE) {
                $result['error'] = $resultJson['description'];
                break;
            }
            if ($error_code > 0) {
                $result["raw_response"] = $resultCurl;
                $result['error'] = $resultJson['description'];
                break;
            }
            $result = [
                "ok" => true,
                "request" => [$method, $data],
                "response" => $resultJson,
            ];
            $result = $resultJson;
            break;
        }
        curl_close($curl);
        return $result;
    }

    public function sendMessageToAdmin($text, $parse_mode = 'HTML')
    {
        if (empty($this->admin_chat_id)) {
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
        $disable_web_page_preview = true
    ) {
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
        Look at:
            https://core.telegram.org/bots/api#html-style
            */
        $args = [
            'chat_id' => $chat_id,
            'text' => $text,
        ];
        if (mb_strlen($text) > self::LIMIT__SYMBOLS_PER_MESSAGE) {
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

    public function getChat(
        $chat_id
    ) {
        if (empty($chat_id)) {
            return ['error' => 'invalid chat id ' . var_export($chat_id, 1)];
        }
        $args = [
            'chat_id' => $chat_id
        ];
        return $this->send_post_request('getChat', $args);
    }

    public function testConnection()
    {
        return $this->send_post_request('getMe', []);
    }

}
/*
usage example:

$t = new Telegrama();
if($t->get_last_error()) {
    echo 'error: ' . $t->get_last_error() . "\n";
    die();
}
var_export(
    //$t->testConnection()
    $t->sendMessageToAdmin('test message')
);
*/