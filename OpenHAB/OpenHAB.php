<?php

use Path\To\OpenHABItem;
use Path\To\DeviceModel;

class OpenHAB
{
    const
        LOG_FILE = '/open_hab',
        RESPONSE__OK = 'ok',
        RESPONSE__ACCEPTED = 'accepted',
        RESPONSE__WRONG_REQUEST = 'wrong_request',
        RESPONSE__ITEM_NOT_FOUND = 'item_not_found',
        RESPONSE__UNSUPPORTED_MEDIA_TYPE = 'unsupported_media_type',
        SWITCHER_STATUS_ON = 'ON',
        SWITCHER_STATUS_OFF = 'OFF'
    ;

    private
    $endpoint = '',
    $login = '',
    $password = '',
    $log_path = '';

    public function __construct()
    {
        $this->log_path = LOG_PATH . self::LOG_FILE . '_' . date('YmdH');
        $settings = DeviceModel::get_openhab_settings();
        if (empty($settings[DeviceModel::SETTINGS__OH_SERVER_URL])) {
            $this->log('__construct', 'Server URL not found');
            return;
        }
        if (empty($settings[DeviceModel::SETTINGS__OH_LOGIN]) || empty($settings[DeviceModel::SETTINGS__OH_PASSWORD])) {
            $this->log('__construct', 'API login or password not found');
            return;
        }
        $this->endpoint = $settings[DeviceModel::SETTINGS__OH_SERVER_URL] . '/rest/';
        $this->login = $settings[DeviceModel::SETTINGS__OH_LOGIN];
        $this->password = $settings[DeviceModel::SETTINGS__OH_PASSWORD];
    }

    public function can_connect(): bool
    {
        return $this->endpoint && $this->login;
    }

    private function log($actor, $data): void
    {
        if (!is_string($data)) {
            $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $message = date('Y-m-d H:i:s') . " - " . $actor . "\n" . $data . "\n-----\n";
        $result = file_put_contents($this->log_path, $message, FILE_APPEND);
        if (!$result) {
            errors_collect(__METHOD__, ['failed to append to log' => $message]);
        }
    }

    private function parse_response_http_code($http_response_header): string
    {
        $code = 0;
        if (is_array($http_response_header)) {
            if (!isset($http_response_header[0])) {
                return self::RESPONSE__WRONG_REQUEST;
            }
            $parts = explode(' ', $http_response_header[0]);
            if (count($parts) > 1) //HTTP/1.0 <code> <text>
                $code = intval($parts[1]); //Get code
        }
        switch ($code) {
            case 200:
                return self::RESPONSE__OK;
            case 202:
                return self::RESPONSE__ACCEPTED;
            case 400:
                return self::RESPONSE__WRONG_REQUEST;
            case 404:
                return self::RESPONSE__ITEM_NOT_FOUND;
            case 415:
                return self::RESPONSE__UNSUPPORTED_MEDIA_TYPE;
        }
        return "$code";
    }

    private function get_response_description($code): string
    {
        switch ($code) {
            case self::RESPONSE__OK:
                return 'Ok';
            case self::RESPONSE__ACCEPTED:
                return 'Request is accepted';
            case self::RESPONSE__WRONG_REQUEST:
                return 'Wrong request';
            case self::RESPONSE__ITEM_NOT_FOUND:
                return 'Item not found';
            case self::RESPONSE__UNSUPPORTED_MEDIA_TYPE:
                return 'Unsupported Media Type';
        }
        return 'Unexpected response';
    }

    private function parse_response($response, $accept = "application/json"): array
    {
        if ($response === false) {
            return [
                "status" => false,
                "error" => 'empty response',
            ];
        }
        if ($accept === "application/json") {
            $resultJson = json_decode($response, 1);
            if ($resultJson === null) {
                return [
                    "status" => false,
                    "error" => 'json: ' . json_last_error_msg(),
                    'response' => $response,
                ];
            }
            return [
                'status' => true,
                'response' => $resultJson,
            ];
        } else {
            return [
                'status' => true,
                'response' => $response,
            ];
        }
    }

    private function send_get_request(
        $method,
        $path_params = [],
        $data = [],
        $accept = 'application/json'
    ): array {
        $url = $this->endpoint . $method . (empty($path_params) ? '' : '/' . implode('/', $path_params))
            . (empty($data) ? '' : '?' . http_build_query($data));

        $context = stream_context_create([
            'http' => array(
                'header' => implode("\r\n", [
                    $this->get_auth_header(),
                    //"Content-type: text/plain",
                    //"Content-type: application/json",
                    "Accept: " . (empty($accept) ? "application/json" : $accept)
                ]),
                'method' => 'GET',
                'content' => '',
                "ignore_errors" => true,
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            ),
        ]);

        $response = @file_get_contents($url, false, $context);
        $result = $this->parse_response_http_code(empty($http_response_header) ? [] : $http_response_header);

        if ($result !== self::RESPONSE__OK || empty($response)) {
            return ['error' => 'http code is not 200: ' . $result];
        } else {
            $result = $this->parse_response($response, $accept);
        }
        return $result;
    }


    private function get_auth_header(): string
    {
        return "Authorization: Basic " . base64_encode($this->login . ':' . $this->password);
    }

    //example https://xxx.com/rest/items/ESPX_000123_DHT/state
    private function send_put_request($method, $path_params = [], $message = ''): string
    {
        $url = $this->endpoint . $method . (empty($path_params) ? '' : '/' . implode('/', $path_params));
        $context = stream_context_create([
            'http' => array(
                'header' => implode("\r\n", [
                    $this->get_auth_header(),
                    "Content-type: text/plain",
                    "Accept: application/json"
                ]),
                'method' => 'PUT',
                'content' => $message,
                "ignore_errors" => true,
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            ),
        ]);
        $response = file_get_contents($url, false, $context);
        $result = $this->parse_response_http_code($http_response_header);
        $this->log(
            'send_post_request ' . $method,
            var_export([
                'url' => $url,
                'method' => 'PUT',
                'message' => $message,
                'response' => $response,
                'result' => $result,
            ], 1)
        );
        return $result;
    }

    private function send_post_request($method, $path_params = [], $message = ''): string
    {
        //$data = json_encode($data, JSON_UNESCAPED_SLASHES);
        $url = $this->endpoint . $method . (empty($path_params) ? '' : '/' . implode('/', $path_params));
        //$context  = $this->get_request_context('POST', $data);
        $context = stream_context_create([
            'http' => array(
                'header' => implode("\r\n", [
                    $this->get_auth_header(),
                    "Content-type: text/plain",
                    "Accept: application/json"
                ]),
                'method' => 'POST',
                'content' => $message,
                "ignore_errors" => true,
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            ),
        ]);
        $response = file_get_contents($url, false, $context);
        $result = $this->parse_response_http_code($http_response_header);
        //$result = $this->parse_response($response);
        $this->log(
            'send_post_request ' . $method,
            var_export([
                'url' => $url,
                'method' => 'POST',
                'message' => $message,
                'response' => $response,
                'result' => $result,
            ], 1)
        );
        return $result;
    }

    /*
    bindings request:
    curl -X GET --header "Accept: application/json" "http://localhost:8080/rest/bindings"

    response: [
      {
        "author": "Deutsche Telekom AG",
        "description": "The hue Binding integrates the Philips hue system. It allows to control hue bulbs.",
        "id": "hue",
        "name": "hue Binding"
      },
      {
        "author": "Karel Goderis",
        "description": "This is the binding for the Sonos multi-room audio system.",
        "id": "sonos",
        "name": "Sonos Binding",
        "configDescriptionURI": "binding:sonos"
      }, ...
    */
    public function getBindingsList(): array
    {
        $response = $this->send_get_request('bindings', []);
        if (empty($response['status'])) {
            return [
                'error' => !empty($response['curl_error']) ? 'Request fail (cURL error)' : 'Response is corrupted (JSON error)'
            ];
        }
        //parse response data
        return $response['response'];
    }

    /*
    request:
    curl -X GET --header "Accept: application/json" "https://xxx.com/rest/extensions/types"

    response format: [
      {
        "id": "action",
        "label": "Actions"
      },
      ...
    ]
    */
    public function getExtentionsTypes(): array
    {
        $response = $this->send_get_request('extensions', ['types']);
        if (empty($response['status'])) {
            return [
                'error' => !empty($response['curl_error']) ? $response['curl_error'] : $response['error']
            ];
        }

        //parse response data

        return $response['response'];

    }

    public function getItemsList($filter = [], $mutableStateOnly = false): array
    {
        $params = [];
        if (!empty($filter)) {
            if (!empty($filter['type'])) {
                $params['type'] = $filter['type'];
            }
        }
        $response = $this->send_get_request('items', [], $params);

        if (empty($response['status'])) {
            return [
                'error' => $response['error'] . (
                    empty($response['response']) ? '' : ': ' . $response['response']
                )
            ];
        }

        //parse response data
        /*[
           {
                "members": [],
                "link": "https://xxx.com/rest/items/Lighting",
                "state": "NULL",
                "type": "Group",
                "name": "Lighting",
                "tags": [
                  "Lighting"
                ],
                "groupNames": []
              },
              {
                "members": [],
                "groupType": "Dimmer",
                "function": {
                  "name": "MAX"
                },
                "link": "https://xxx.com/rest/items/WallsLights",
                "state": "100",
                "stateDescription": {
                  "pattern": "%d",
                  "readOnly": false,
                  "options": []
                },
                "type": "Group",
                "name": "WallsLights",
                "label": "Walls light",
                "tags": [
                  "Lighting"
                ],
                "groupNames": []
              },
        ]*/

        $result = [];
        foreach ($response['response'] as $item) {
            $tmp = new OpenHABItem(self::get_item_data($item));
            if (empty($tmp->get_type())) {
                errors_collect(__METHOD__, "Empty item type: " . var_export($item, 1));
                continue;
            }
            if (empty($tmp->get_name())) {
                errors_collect(__METHOD__, "Empty item name: " . var_export($item, 1));
                continue;
            }
            if ($mutableStateOnly && $tmp->get_state_is_read_only()) {
                continue;
            }
            $result[$tmp->get_name()] = $tmp;
        }

        return $result; //objects array
    }

    private function get_item_data($api_item_data): array
    {
        if (empty($api_item_data) || !is_array($api_item_data)) {
            return [];
        }
        $stdesc = empty($api_item_data['stateDescription']) ? [] : $api_item_data['stateDescription'];
        return [
            OpenHABItem::FIELD__NAME => empty($api_item_data['name']) ? '' : $api_item_data['name'],
            OpenHABItem::FIELD__TYPE => empty($api_item_data['type']) ? '' : $api_item_data['type'],
            OpenHABItem::FIELD__STATE => empty($api_item_data['state']) ? '' : $api_item_data['state'],
            OpenHABItem::FIELD__CATEGORY => empty($api_item_data['category']) ? '' : $api_item_data['category'],
            OpenHABItem::FIELD__LABEL => empty($api_item_data['label']) ? '' : $api_item_data['label'],
            OpenHABItem::FIELD__STATEISREADONLY => isset($stdesc['readOnly']) && $stdesc['readOnly'] ? true : false,
        ];
        /* API response:
         *  switch example: {
            "link": "https://xxx.com/rest/items/ESPTH_0012345_ADCSWITCH",
            "state": "OFF",
            "stateDescription": {
                "pattern": "%s",
                "readOnly": false,
                "options": []
            },
            "type": "Switch",
            "name": "ESPTH_0012345_ADCSWITCH",
            "label": "ESPTH_0012345_ADCSWITCH ADC on/off",
            "tags": [],
            "groupNames": [
                "Builtin",
                "Switches",
                "ADC",
                "ESPTH"
            ]
        }
        group example: {
            "members": [],
            "link": "https://xxx.com/rest/items/Lighting",
            "state": "NULL",
            "type": "Group",
            "name": "Lighting",
            "tags": [
              "Lighting"
            ],
            "groupNames": []
          },
          {
            "members": [],
            "groupType": "Dimmer",
            "function": {
              "name": "MAX"
            },
            "link": "https://xxx.com/rest/items/WallsLights",
            "state": "100",
            "stateDescription": {
              "pattern": "%d",
              "readOnly": false,
              "options": []
            },
            "type": "Group",
            "name": "WallsLights",
            "label": "Walls light",
            "tags": [
              "Lighting"
            ],
            "groupNames": []
          }
        */
    }

    //name examples = DeskLamp (items group), ESPX_00012345_LED_BLUE (single item)
    public function getItem($itemName, $mutableStateOnly = false)
    {
        if (empty($itemName)) {
            return ['error' => 'Item name not found'];
        }
        $response = $this->send_get_request('items', [$itemName]);
        if (empty($response['status'])) {
            return [
                'error' => $response['error'] . (
                    empty($response['response']) ? '' : ': ' . $response['response']
                )
            ];
        }
        $item = $response['response'];
        $instance = new OpenHABItem(self::get_item_data($item));
        if (empty($instance->get_type())) {
            errors_collect(__METHOD__, "Empty item type: " . var_export($item, 1));
            return ['error' => 'The item can not be used by system without type'];
        }
        if (empty($instance->get_name())) {
            errors_collect(__METHOD__, "Empty item name: " . var_export($item, 1));
            return ['error' => 'The item can not be used by system without name'];
        }
        if ($mutableStateOnly && $instance->get_state_is_read_only()) {
            errors_collect(__METHOD__, "Item is not mutable: " . var_export($item, 1));
            return ['error' => 'Immutable item can not be used'];
        }

        return $instance;
    }

    //name examples = DeskLamp (items group), ESPX_00012345_LED_BLUE (single item)
    public function getItemState($itemName): array
    {
        if (empty($itemName)) {
            return ['error' => 'Item name not found'];
        }
        $response = $this->send_get_request('items', [$itemName, 'state'], [], 'text/plain');
        if (empty($response['status'])) {
            return [
                'error' => $response['error'] . (
                    empty($response['response']) ? '' : ': ' . $response['response']
                )
            ];
        }
        //parse response data
        return $response['response'];
    }

    public function setItemState($itemName, $state_code = false)
    {
        if (empty($itemName)) {
            return ['error' => 'Item name not found'];
        }
        if ($state_code === false || $state_code === '' || (!is_string($state_code) && !is_numeric($state_code))) {
            return ['error' => 'Item state code is empty'];
        }
        //echo "Sending '$state_code' to $itemName: ";  return true;
        $response = $this->send_put_request('items', [$itemName, 'state'], $state_code);
        if ($response !== self::RESPONSE__ACCEPTED) {
            return ['error' => $this->get_response_description($response)];
        }

        return true;
    }

    public function sendItemCommand($itemName, $command = false)
    {
        $log_name = 'sendItemCommand';
        $log_data = ['item' => $itemName, 'cmd' => $command];
        if (empty($itemName)) {
            $log_data['error'] = 'Item name not found';
            $this->log($log_name, $log_data);
            return ['error' => 'Item name not found'];
        }
        if ($command === false || $command === '' || (!is_string($command) && !is_numeric($command))) {
            $log_data['error'] = 'Item command is empty';
            $this->log($log_name, $log_data);
            return ['error' => 'Item state code is empty'];
        }
        $response = $this->send_post_request('items', [$itemName], $command);
        $log_data['response'] = $response;
        $this->log($log_name, $log_data);
        if ($response !== self::RESPONSE__OK) {
            return ['error' => $this->get_response_description($response)];
        }
        return true;
    }

    public function getSitemaps(): array
    {
        //https://xxx.com/rest/sitemaps
        $response = $this->send_get_request('sitemaps');
        if (empty($response['status'])) {
            return [
                'error' => $response['error'] . (
                    empty($response['response']) ? '' : ': ' . $response['response']
                )
            ];
        }
        if (empty($response['response'])) {
            errors_collect(__METHOD__, "No sitemaps found");
            return [];
        }
        $result = [];
        foreach ($response['response'] as $m) {
            /* map example: {
                "name": "default",
                "label": "My home",
                "link": "https://xxx.com/rest/sitemaps/default",
                "homepage": {
                  "link": "https://xxx.com/rest/sitemaps/default/default",
                  "leaf": false,
                  "timeout": false,
                  "widgets": []
                }
              },
           */
            if (empty($m['name'])) {
                continue;
            }
            $result[$m['name']] = $m['name'] . ' - ' . $m['label'];
        }
        return $result;
    }

    public function getSitemapByName($name): array
    {
        //https://xxx.com/rest/sitemaps/default
        if (empty($name)) {
            return ['error' => 'sitemap name required'];
        }
        $response = $this->send_get_request('sitemaps', [$name]);

        if (empty($response['status'])) {
            return [
                'error' => $response['error'] . (
                    empty($response['response']) ? '' : ': ' . $response['response']
                )
            ];
        }
        if (empty($response['response'])) {
            return [];
        }
        /* header example: {
            "name": "default",
            "label": "My home",
            "link": "https://xxx.com/rest/sitemaps/default",
            "homepage": {
              "id": "default",
              "title": "My home",
              "link": "https://xxx.com/rest/sitemaps/default/default",
              "leaf": false,
              "timeout": false,
              "widgets": [
         *
         */
        if (empty($response['response']['homepage'])) {
            errors_collect(__METHOD__, "'homepage' not found");
            return [];
        }
        if (empty($response['response']['homepage']['widgets'])) {
            errors_collect(__METHOD__, "No widgets found");
            return [];
        }
        $result = [];
        foreach ($response['response']['homepage']['widgets'] as $w) {
            $this->loadSitemapParentWidget($w, $result);
        }
        return $result;
    }

    private function loadSitemapParentWidget(&$parent_widget, &$result, $parent_label = ''): bool
    {
        if (empty($parent_widget['widgets'])) {
            return true;
        }
        /* parent widget example:
            {
              "widgetId": "00",
              "type": "Frame",
              "visibility": true,
              "label": "Cinema Light control",
              "icon": "frame",
              "mappings": [],
              "widgets": [
                ...
              ]
            },
        */
        $widgetId = empty($parent_widget['widgetId']) ? '' : $parent_widget['widgetId'];
        $label = empty($parent_widget['label']) ? '' : $parent_widget['label'];
        if (empty($label) && $parent_label) {
            $label = $parent_label;
        } elseif (!empty($label) && $parent_label) {
            $label = "$parent_label / $label";
        }
        $type = empty($parent_widget['type']) ? '' : $parent_widget['type'];
        $mappings = empty($parent_widget['mappings']) ? '' : $parent_widget['mappings'];
        foreach ($parent_widget['widgets'] as $w) {
            if (!empty($w['linkedPage']) && !empty($w['linkedPage']['widgets'])) {
                $this->loadSitemapParentWidget(
                    $w['linkedPage'],
                    $result,
                    ($parent_label ? $parent_label . ' / ' : '') . $label
                );
                continue;
            }
            if (!empty($w['widgets'])) {
                $this->loadSitemapParentWidget($w, $result, $parent_label);
                continue;
            }
            $parsed = $this->getSitemapChildWidget($w, $label);
            if (!empty($parsed) && !empty($parsed['name'])) {
                $result[$parsed['name']] = $parsed;
            }
        }
        return true;
    }

    private function getSitemapChildWidget($widget, $parent_label): array
    {
        if (empty($widget)) {
            return [];
        }
        if (empty($widget['visibility'])) {
            return [];
        }
        /* child widgets example:
        {
            "widgetId": "0000",
            "type": "Switch",
            "visibility": true,
            "label": "Light [0]",
            "icon": "light",
            "mappings": [],
            "state": "OFF",
            "item": {
              "link": "https://xxx.com/rest/items/ESPX_001234_LED_RED",
              "state": "0",
              "stateDescription": {"pattern": "%s", "readOnly": false, "options": []},
              "type": "Dimmer",
              "name": "ESPX_001234_LED_RED",
              "label": "ESPX_001234_LED_RED Red LED",
              "tags": [],
              "groupNames": ["Dimmers", "LEDs", "Light", "ESPX"]
            },
            "widgets": []
          },
          {
            "widgetId": "040001",
            "type": "Switch",
            "visibility": true,
            "label": "Office panel gradually on/off [OFF]",
            "icon": "switch",
            "mappings": [],
            "item": {
              "link": "https://xxx.com/rest/items/PANEL123_GRADUALLY",
              "state": "OFF",
              "stateDescription": {
                "pattern": "%s",
                "readOnly": false,
                "options": []
              },
              "type": "Switch",
              "name": "PANEL123_GRADUALLY",
              "label": "Gradually turn on/off panel",
              "tags": [],
              "groupNames": [
                "Virtuals"
              ]
            },
            "widgets": []
          },
         {
            "widgetId": "05010002",
            "type": "Selection",
            "visibility": true,
            "label": "Operation Mode CINEMA [Auto]",
            "icon": "climate",
            "mappings": [
              {
                "command": "1",
                "label": "Auto"
              },
              {
                "command": "2",
                "label": "Cool"
              },
              {
                "command": "3",
                "label": "Heat"
              },
              {
                "command": "4",
                "label": "Fan"
              },
              {
                "command": "5",
                "label": "Dry"
              }
            ],
            "item": {
              "link": "https://xxx.com/rest/items/AC_Operation_Mode_110102",
              "state": "1.0",
              "transformedState": "Auto",
              "stateDescription": {
                "pattern": "MAP(ac_mode.map):%s",
                "readOnly": false,
                "options": []
              },
              "type": "Number",
              "name": "AC_Operation_Mode_110102",
              "label": "Operation Mode CINEMA",
              "category": "setting",
              "tags": [],
              "groupNames": [
                "AC",
                "ClimateControl"
              ]
            },
            "widgets": []
          },
          */

        if (empty($widget['item'])) {
            return [];
        }

        $options = [];
        $widget_label = empty($widget['label']) ? '' : $widget['label'];
        if (strpos($widget_label, ' [')) {
            $widget_label = substr($widget_label, 0, strpos($widget_label, ' ['));
        }
        $widget_type = empty($widget['type']) ? '' : $widget['type'];
        $min_value = empty($widget['minValue']) ? 0 : $widget['minValue'];
        $max_value = empty($widget['maxValue']) ? 0 : $widget['maxValue'];

        $i = $widget['item'];
        $item_name = empty($i['name']) ? '' : $i['name'];
        //$item_type = empty($i['type']) ? '' : $i['type'];
        $item_label = empty($i['label']) ? '' : $i['label'];
        $final_label = strlen($widget_label) > strlen($item_label)
            ? $widget_label
            : (
                $widget_label !== $item_label
                ? "$widget_label ($item_label)"
                : $item_label
            );
        $state_options = empty($i['stateDescription']['options']) ? [] : $i['stateDescription']['options'];
        $func_params = empty($i['function']['params']) ? [] : $i['function']['params'];

        if (!empty($widget['mappings'])) {
            foreach ($widget['mappings'] as $m) {
                $options[$m['command']] = $m['label'];
            }
        } elseif (!empty($state_options)) {
            $options = $state_options;
        } elseif (!empty($func_params)) {
            $options = $func_params;
        } else {
            switch ($widget_type) {
                case 'Switch':
                    $options = [
                        'ON' => 'On',
                        'OFF' => 'Off'
                    ];
                    break;
            }
        }

        return [
            'name' => $item_name,
            'type' => $widget_type,
            'min' => $min_value,
            'max' => $max_value,
            'opt' => $options,
            'label' => "$parent_label / $final_label"
        ];
    }
}