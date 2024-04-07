<?php
use \Google_Client;
use App\SettingsRegistry; //todo: implement

//todo: create constant STORAGE_PATH

class GoogleApiSettings
{
    const
        APP_NAME = 'Google API App',
        SCOPE_CALENDAR = \Google_Service_Calendar::CALENDAR,
        SCOPE_CALENDAR_EVENTS = \Google_Service_Calendar::CALENDAR_EVENTS,
        SCOPE_DOCUMENTS = \Google_Service_Docs::DOCUMENTS, //'https://www.googleapis.com/auth/documents',
        SCOPE_DRIVE = \Google_Service_Docs::DRIVE, //'https://www.googleapis.com/auth/drive',
        SCOPE_DRIVE_FILE = \Google_Service_Docs::DRIVE_FILE, //'https://www.googleapis.com/auth/drive.file',
        SCOPE_DOCUMENTS_READ = \Google_Service_Docs::DOCUMENTS_READONLY, //'https://www.googleapis.com/auth/documents.readonly',
        SCOPE_DRIVE_READ = \Google_Service_Docs::DRIVE_READONLY, //'https://www.googleapis.com/auth/drive.readonly'
        //SCOPE_DRIVE_APP_DATA = 'https://www.googleapis.com/auth/drive.appdata',
        //SCOPE_DRIVE_PHOTOS_READ = 'https://www.googleapis.com/auth/drive.photos.readonly',
        SCOPE_SHEETS = \Google_Service_Sheets::SPREADSHEETS, //"https://www.googleapis.com/auth/spreadsheets";
        SCOPE_SHEETS_READ = \Google_Service_Sheets::SPREADSHEETS_READONLY, //"https://www.googleapis.com/auth/spreadsheets.readonly";
        SCOPE_CHAT_BOT = 'https://www.googleapis.com/auth/chat.bot';
    const
        ERROR__FILE_NOT_FOUND = 'file_not_found',
        ERROR__BACKUP_NOT_FOUND = 'backup_not_found',
        ERROR__WRONG_FILE_FORMAT = 'wrong_file_format',
        ERROR__SERVICE_ACCOUNT_REQUIRED = 'service_account_required',
        CREDENTIALS_REQUIRED_TYPE = 'service_account',
        CALENDAR_SETTING_KEY = 'google_calendar',
        PLACES_API_KEY = 'google_places_apikey',
        DOCS_SETTINGS_KEY = 'google_docs',
        MAPS_APIKEY_SETTINGS_KEY = 'google_maps_apikey',
        REPORT__PRIV_KEY_ID = 'private key id',
        REPORT__CLIENT_EMAIL = 'client email',
        REPORT__PROJECT_ID = 'project id',
        REPORT__CLIENT_ID = 'client id',
        KEY_FILE_UPLOAD_FIELD = 'key_file_upload',
        MAPS_KEY_FIELD = 'maps_key',
        PLACES_API_KEY_FIELD = 'places_api_key',
        COPY_TASKS_TO_CALENDAR = 'copy_tasks_to_calendar',
        CLOUD_CONSOLE_APIS_LINK = 'https://console.cloud.google.com/apis',
        MAPS_EMBED_API_DOCS_LINK = 'https://developers.google.com/maps/documentation/embed/get-started',
        PLACES_API_DOCS_LINK = 'https://developers.google.com/maps/documentation/places/web-service/get-api-key',
        ACCESS_TYPE__SERVER_REQUEST = 'server_request',
        ACCESS_TYPE__EMBEDDED_ON_PAGE = 'embedded_on_page',
        ACCESS_TYPE__CALENDAR_SUBSCRIPTION = 'calendar_subscription',
        ACCESS_TYPE__PLACES_LIST_UPDATE = 'places_list_update';

    private static
    $credentials = [],
    $log = [],
    $client_by_scope = [];

    /*Maps Embed API
    Place an interactive map or Street View panorama on your site with a simple HTTP request
    using the Maps Embed API. Set the Embed API URL as the src attribute of an iframe to easily
    embed the map in your webpage or blog.
    https://developers.google.com/maps/documentation/embed/get-started?hl=en_US
     *      */
    public static function update_maps_api_key($key)
    {
        return (bool) SettingsRegistry::set_by_key(
            self::MAPS_APIKEY_SETTINGS_KEY,
            ['key' => $key]
        );
    }
    public static function get_maps_api_key()
    {
        $data = SettingsRegistry::get_by_key(self::MAPS_APIKEY_SETTINGS_KEY);
        return empty($data['key']) ? '' : $data['key'];
    }

    public static function get_calendar_settings()
    {
        return SettingsRegistry::get_by_key(self::CALENDAR_SETTING_KEY);
    }

    public static function update_places_api_key($key)
    {
        return SettingsRegistry::set_by_key(self::PLACES_API_KEY, ['key' => $key])
            ? true : false;
    }
    public static function get_places_api_key()
    {
        $data = SettingsRegistry::get_by_key(self::PLACES_API_KEY);
        return empty($data['key']) ? '' : $data['key'];
    }

    private static function log($subject, $message)
    {
        self::$log[] = $subject . ': '
            . (is_array($message) ? json_encode($message) : $message);
    }

    public static function flash_log()
    {
        if (empty(self::$log)) {
            return '';
        }
        $log = self::$log;
        self::$log = [];
        return implode("\n", $log);
    }

    /**
     * upload key file
     * @return string|boolean error text on fail or true on success
     */
    public static function upload_key_file()
    {
        if (empty($_FILES[self::KEY_FILE_UPLOAD_FIELD])) {
            return false;
        }
        $file_data = $_FILES[self::KEY_FILE_UPLOAD_FIELD];
        if (!empty($file_data['error'])) {
            return $file_data['error'] === UPLOAD_ERR_NO_FILE
                ? false
                : 'Uploading error: UPLOAD_ERR_NO_FILE';// . FilesHelper::parse_uploading_error($file_data);
        }
        if (empty($file_data['size'])) {
            return 'File is empty';
        }
        if ($file_data['size'] > 1024 * 1024) { //> 1 MB
            return "File is too big";
        }
        $allowed_mimetype = 'application/json';
        if (empty($file_data['type']) || $file_data['type'] !== $allowed_mimetype) {
            return "Only JSON files are allowed";
        }
        $directory = realpath(STORAGE_PATH); //todo: put your path to files storage
        $local_path = $directory . '/file_upload_' . time();
        if (is_file($local_path)) {
            unset($local_path);
        }
        //move_uploaded_file($file_data['tmp_name'], $local_path); // --> permission denied

        try {
            $copy_result = copy($file_data['tmp_name'], $local_path);
        } catch (\Exception $e) {
            return 'Coping error: ' . $e->getMessage();
        }
        if (!$copy_result) {
            return "Can not copy uploaded file";
        }
        chmod($local_path, 0766);
        $json = file_get_contents($local_path);
        $validation = self::validate_credentials_file_content($json);
        if ($validation !== true) {
            unlink($local_path);
            return $validation;
        }
        $current_path = self::get_service_account_credentials_path();
        $backup_path = self::get_service_account_credentials_backup_path();
        if (is_file($backup_path)) {
            unlink($backup_path);
        }
        if (is_file($current_path)) {
            rename($current_path, $backup_path);
            chmod($backup_path, 0766);
        }
        rename($local_path, $current_path);
        chmod($current_path, 0766);
        return true;
    }

    /**
     * restore key file from backup and save current file to backup
     * so next restoration will return prev. settings
     * @return string|boolean error text on fail or true on success
     */
    public static function restore_key_file()
    {
        $current_path = self::get_service_account_credentials_path();
        $tmp_path = $current_path . '.tmp';
        $backup_path = self::get_service_account_credentials_backup_path();
        if (!is_file($backup_path)) {
            return self::ERROR__BACKUP_NOT_FOUND;
        }
        // the file was uploaded so it can't be wrong and doesn't need check
        if (is_file($current_path)) {
            rename($current_path, $tmp_path);
            rename($backup_path, $current_path);
            rename($tmp_path, $backup_path);
        } else {
            rename($backup_path, $current_path);
        }
        return true;
    }

    public static function backup_exists()
    {
        return is_file(
            self::get_service_account_credentials_backup_path()
        );
    }

    public static function translate_error($error)
    {
        //IntegrationsDictionary::load();
        switch ($error) {
            case GoogleApiSettings::ERROR__FILE_NOT_FOUND:
                return 'file_not_found';//IntegrationsDictionary::$file_not_found;
            case GoogleApiSettings::ERROR__BACKUP_NOT_FOUND:
                return 'backup_not_found';//IntegrationsDictionary::$backup_not_found;
            case GoogleApiSettings::ERROR__WRONG_FILE_FORMAT:
                return 'wrong_file_format';//IntegrationsDictionary::$wrong_file_format;
            case GoogleApiSettings::ERROR__SERVICE_ACCOUNT_REQUIRED:
                return 'service_account_required';//IntegrationsDictionary::$service_account_required;
        }
        return $error;
    }

    public static function get_service_account_credentials_path()
    {
        return STORAGE_PATH . '/GoogleCloudServiceAccount.json';
    }
    public static function get_service_account_credentials_backup_path()
    {
        return self::get_service_account_credentials_path() . '.back';
    }

    private static function validate_credentials_file_content($content)
    {
        $data = empty($content) ? [] : json_decode($content, 1);
        if (empty($data)) {
            return self::ERROR__WRONG_FILE_FORMAT;
        }
        $type = empty($data['type']) ? '' : $data['type'];
        if ($type !== self::CREDENTIALS_REQUIRED_TYPE) {
            return self::ERROR__SERVICE_ACCOUNT_REQUIRED;
        }
        return true;
    }

    public static function load_service_account_credentials()
    {
        if (self::$credentials) {
            return true;
        }
        $path = self::get_service_account_credentials_path();
        if (!is_file($path)) {
            return self::ERROR__FILE_NOT_FOUND;
        }
        $json = file_get_contents($path);
        $validation = self::validate_credentials_file_content($json);
        if ($validation !== true) {
            return $validation;
        }
        $data = json_decode($json, 1);
        self::$credentials[self::REPORT__PROJECT_ID] = empty($data['project_id']) ? '' : $data['project_id'];
        self::$credentials[self::REPORT__PRIV_KEY_ID] = empty($data['private_key_id']) ? '' : $data['private_key_id'];
        self::$credentials[self::REPORT__CLIENT_EMAIL] = empty($data['client_email']) ? '' : $data['client_email'];
        self::$credentials[self::REPORT__CLIENT_ID] = empty($data['client_id']) ? '' : $data['client_id'];
        return true;
    }

    public static function get_credentials_report()
    {
        $result = self::load_service_account_credentials();
        if ($result !== true) {
            return ['error' => $result];
        }
        return self::$credentials;
    }

    public static function get_scope_key($scopes)
    {
        return empty($scopes) || !is_array($scopes) ? '' : implode(',', $scopes);
    }

    public static function get_client($scopes)
    {
        if (empty($scopes)) {
            return false;
        }
        $scope_key = self::get_scope_key($scopes);
        if (!empty(self::$client_by_scope[$scope_key])) {
            return self::$client_by_scope[$scope_key];
        }
        self::$log = [];
        $client = new \Google_Client();
        $client->setApplicationName(self::APP_NAME);
        $client->setScopes($scopes);
        $authFilePath = self::get_service_account_credentials_path();
        try {
            $client->setAuthConfig($authFilePath);
        } catch (\InvalidArgumentException $e) {
            self::log(
                $scope_key,
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            );
            return false;
        } catch (\Exception $e) {
            self::log(
                $scope_key,
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            );
            return false;
        }
        $client->setAccessType('offline');
        $client->setPrompt('');
        if (empty($client)) {
            self::log('Unknown error', 'Can not load client');
            return false;
        }
        self::$client_by_scope[$scope_key] = $client;
        return self::$client_by_scope[$scope_key];
    }

    /*
    Google Cloud APIs only accept requests from registered applications, which are uniquely identifiable
     applications that present a credential at the time of the request. Requests from anonymous
     applications are rejected.

    --- CREDENCIAL TYPES ---

    Application credentials provide the required information about the caller making a request to a
     Google Cloud API. Valid credential types include:
     * API keys - https://cloud.google.com/docs/authentication/api-keys,
     * OAuth 2.0 client credentials - https://cloud.google.com/docs/authentication/end-user,
     * or service account keys - https://cloud.google.com/docs/authentication/production#create_service_account.

    Service accounts are unique, because they can be used as both an application credential or a
     principal identity. See Understanding service accounts - https://cloud.google.com/iam/docs/understanding-service-accounts.

    Presenting application credentials in requests to Google Cloud APIs only identifies the caller
     as a registered application; if authentication is required, the client must also identify the
     principal running the application, such as a user account or service account.

    An API key is a simple encrypted string that identifies an application without any principal.
     They are useful for accessing public data anonymously, and are used to associate API requests
     with your project for quota and billing.
    To CREATE AN API KEY in a project, the user must be granted the Editor basic role (roles/editor)
     on the project. Navigate to the APIs & Services→Credentials panel in Cloud Console.
       https://console.cloud.google.com/apis/credentials
     */
}