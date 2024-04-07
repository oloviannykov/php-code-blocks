<?php

use Path\To\Class\GoogleApiSettings;

class GoogleDocsApi
{
    //read document structure: https://developers.google.com/docs/api/concepts/structure

    private
    $client = null,
    $service = null,
    $documentId = '',
    $scopes_group = null,
    $log = [],
    $activeSheetId = false,
    $activeTabPrefix = 'Sheet1!'
    ;

    const
        RESULT__WRONG_REQUEST = 'wrong request',
        RESULT__CONNECTION_FAILED = 'connection failed',
        RESULT__FILE_NOT_FOUND = 'file not found',
        RESULT__UNKNOWN_ERROR = 'unknown error',
        RESULT__SUCCESS = 'success';

    const
        SCOPE_DOCUMENTS = \Google_Service_Docs::DOCUMENTS, //'https://www.googleapis.com/auth/documents',
        SCOPE_DRIVE = \Google_Service_Docs::DRIVE, //'https://www.googleapis.com/auth/drive',
        SCOPE_DRIVE_FILE = \Google_Service_Docs::DRIVE_FILE, //'https://www.googleapis.com/auth/drive.file',
        SCOPE_DOCUMENTS_READ = \Google_Service_Docs::DOCUMENTS_READONLY, //'https://www.googleapis.com/auth/documents.readonly',
        SCOPE_DRIVE_READ = \Google_Service_Docs::DRIVE_READONLY, //'https://www.googleapis.com/auth/drive.readonly'
        SCOPE_DRIVE_APP_DATA = 'https://www.googleapis.com/auth/drive.appdata',
        SCOPE_DRIVE_PHOTOS_READ = 'https://www.googleapis.com/auth/drive.photos.readonly',
        SCOPE_SHEETS = \Google_Service_Sheets::SPREADSHEETS, //"https://www.googleapis.com/auth/spreadsheets";
        SCOPE_SHEETS_READ = \Google_Service_Sheets::SPREADSHEETS_READONLY, //"https://www.googleapis.com/auth/spreadsheets.readonly";

        SCOPES_GROUP_DOCS = 'docs',
        SCOPES_GROUP_DRIVE = 'drive',
        SCOPES_GROUP_SHEETS = 'sheets',
        /*
            The ValueInputOption controls whether input strings are parsed or not:
            * RAW - not parsed and is simply inserted as string ("=1+2" => string "=1+2", not formula)
            * USER_ENTERED - The input is parsed exactly as if it were entered into the Google Sheets UI,
            "Mar 1 2016" => date, "=1+2" => formula, "$100.15" => number with currency formatting.
        */
        SHEET_INPUT_OPT_RAW = 'RAW',
        SHEET_INPUT_OPT_PARSE = 'USER_ENTERED'
    ;

    /* from https://developers.google.com/docs/api/concepts/request-response
     *
    Request methods
    ---------------
    The Docs API supports the following request methods:
    * create — Creates a new, blank Google Docs document.
    * get — Returns a complete instance of the specified document. You can parse the returned JSON to extract the document content, formatting, and other features.
    * batchUpdate — Submits a list of editing requests to apply to the document, and returns a list of results.
    You can invoke these methods using a client library method, or directly as an HTTP request, with the response returned as appropriate for the invocation style. For details of these requests and responses, see the API reference.
    */

    public function __construct($scopes_group)
    {
        $groups = $this->get_scopes_groups_map();
        if (!isset($groups[$scopes_group])) {
            $this->log('connect', 'Unknown scopes group: ' . $scopes_group);
        } else {
            $this->scopes_group = $scopes_group;
        }
    }

    private function process_service_exception(
        $caller_name,
        \Google_Service_Exception $exception,
        $request = []
    ): void {
        errors_collect(
            $caller_name,
            [
                'Google_Service_Exception' => [
                    $exception->getMessage(),
                    $exception->getFile() . ':' . $exception->getLine()
                ],
                'request' => $request
            ]
        );
    }

    public function get_scopes_groups_map(): array
    {
        return array(
            self::SCOPES_GROUP_DOCS => [
                self::SCOPE_DOCUMENTS,
                //self::SCOPE_DOCUMENTS_READ
            ],
            self::SCOPES_GROUP_DRIVE => [
                self::SCOPE_DRIVE,
                self::SCOPE_DRIVE_APP_DATA,
                self::SCOPE_DRIVE_FILE,
                //self::SCOPE_DRIVE_READ, self::SCOPE_DRIVE_PHOTOS_READ
            ],
            self::SCOPES_GROUP_SHEETS => [
                self::SCOPE_SHEETS,
                //self::SCOPE_SHEETS_READ
            ],
        );
    }

    public function connect($scopes_group): bool
    {
        if (empty($this->scopes_group)) {
            $this->log('connect', 'Scopes group was not found');
            return false;
        }
        if ($this->scopes_group !== $scopes_group) {
            $this->log('connect', 'Scope collision');
            return false;
        }
        $groups = $this->get_scopes_groups_map();
        $scopes = $groups[$this->scopes_group];
        // \Google_Client;
        $this->client = GoogleApiSettings::get_client($scopes);
        if (empty($this->client)) {
            $this->log('connect', GoogleApiSettings::flash_log());
            return false;
        }
        try {
            switch ($this->scopes_group) {
                case self::SCOPES_GROUP_DOCS:
                    $this->service = new \Google_Service_Docs($this->client);
                    break;
                case self::SCOPES_GROUP_DRIVE:
                    $this->service = new \Google_Service_Drive($this->client);
                    break;
                case self::SCOPES_GROUP_SHEETS:
                    $this->service = new \Google_Service_Sheets($this->client);
                    break;
                default:
                    $this->log('unsupported scopes group', $this->scopes_group);
                    return false;
            }
        } catch (\Exception $e) {
            $this->process_service_exception(__METHOD__, $e, $this->scopes_group);
            return false;
        }
        return !empty($this->service);
    }

    private function log($title, $data): void
    {
        $this->log[] = [$title => $data];
    }

    public function flashLog(): array
    {
        $log = $this->log;
        $this->log = [];
        return $log;
    }

    public function isConnected(): bool
    {
        return !empty($this->service) || is_object($this->service);
    }

    public function create_document($title): string
    {
        if (empty($title) || !$this->connect(self::SCOPES_GROUP_DOCS)) {
            return '';
        }
        $document = new \Google_Service_Docs_Document([
            'title' => $title
        ]);
        try {
            $result = $this->service->documents->create($document);
        } catch (\Google_Service_Exception $e) {
            $this->process_service_exception(__METHOD__ . '::documents.create', $e, [$title, $document]);
            return '';
        }
        return $result->documentId ? $result->documentId : '';
    }

    /**
     *
     * @param String $fileId ID of the file to insert permission for.
     * @param String $email User or group e-mail address, domain name or NULL for "default" type.
     * @return Google_Servie_Drive_Permission|null The inserted permission or NULL on error
     */
    public function add_reader_permission($fileId, $email)
    {
        if (!$this->connect(self::SCOPES_GROUP_DRIVE)) {
            return null;
        }
        $p = new \Google_Service_Drive_Permission();
        $p->setEmailAddress($email);
        $p->setType('user'); //"user", "group", "domain" or "default".
        $p->setRole('reader'); //"owner", "writer" or "reader".
        try {
            $response = $this->service->permissions->create($fileId, $p);
        } catch (\Exception $e) {
            $this->process_service_exception(__METHOD__ . '::permissions.create', $e, [$fileId, $email, $p]);
            return null;
        }
        return $response;
    }

    public function copy_document($documentId, $copyTitle): string
    {
        //https://developers.google.com/drive/api/v3/reference/files/copy#auth

        if (
            empty($documentId) || empty($copyTitle)
            || !$this->connect(self::SCOPES_GROUP_DRIVE)
        ) {
            return '';
        }
        $copy = new \Google_Service_Drive_DriveFile(
            array(
                'name' => $copyTitle
            )
        );
        try {
            $driveResponse = $this->service->files->copy($documentId, $copy);
        } catch (\Exception $e) {
            $this->process_service_exception(
                __METHOD__ . '::files.copy',
                $e,
                [$documentId, $copyTitle, $copy]
            );
            return '';
        }
        return $driveResponse->id;
    }

    public function get_doc($documentId)
    {
        //GET https://docs.googleapis.com/v1/documents/{documentId}
        //The request body must be empty.
        //If successful, the response body contains an instance of Document.
        if (!$this->connect(self::SCOPES_GROUP_DOCS)) {
            return false;
        }
        try {
            $doc_instance = $this->service->documents->get($documentId);
        } catch (\Exception $e) {
            /* message example: {"error": {
                "code": 403, "message": "The caller does not have permission",
                "errors": [...],
                "status": "PERMISSION_DENIED"
            }} */
            $this->process_service_exception(__METHOD__ . '::service.documents.get', $e, $documentId);
            return false;
        }
        return $doc_instance;
    }

    public function get_file_permissions($fileId)
    {
        if (!$this->connect(self::SCOPES_GROUP_DRIVE)) {
            return false;
        }
        try {
            //(new \Google_Service_Drive_Resource_Permissions)->listPermissions($fileId);
            $response = $this->service->permissions->listPermissions($fileId);
            //--> Google_Service_Drive_PermissionList
        } catch (\Exception $e) {
            $this->process_service_exception(__METHOD__ . '::permissions.listPermissions', $e, $fileId);
            return false;
        }
        return $response;
    }
    /* --> ... 'permissions' => array ([
          'kind' => 'drive#permission',
          'id' => '06327196417118659122',
          'type' => 'user',
          'role' => 'owner',
        ]) */

    public function get_drive_info()
    {
        if (!$this->connect(self::SCOPES_GROUP_DRIVE)) {
            return false;
        }
        /*Required query parameters
            'fields' string
                The paths of the fields you want included in the response.
         *      For development you can use the special value * to return all fields,
         *      but you'll achieve greater performance by only selecting the fields you need.
         *      For more information, see https://developers.google.com/drive/api/v3/fields-parameter
         */
        $optParams = [
            'fields' => '*', //required, get all fields
        ];
        try {
            //'https://www.googleapis.com/drive/v3/about?fields=*&key=[YOUR_API_KEY]'
            //(new \Google_Service_Drive_Resource_About)->get($optParams);
            $response = $this->service->about->get($optParams);
            //--> Google_Service_Drive_About
        } catch (\Exception $e) {
            $this->process_service_exception(__METHOD__ . '::about.get', $e);
            return false;
        }
        return $response;
    }
    // 'Google_Service_Drive_Resource_About.get() exception:
    //  ... "message": "The 'fields' parameter is required for this method."

    /* success result example:
    Google_Service_Drive_About::__set_state(array(

       'collection_key' => 'teamDriveThemes',
       'appInstalled' => false,
       'canCreateDrives' => false,
       'canCreateTeamDrives' => false,
       'driveThemesType' => 'Google_Service_Drive_AboutDriveThemes',
       'driveThemesDataType' => 'array',
       'exportFormats' => [
            'application/vnd.google-apps.document' => [
              'text/html', 'application/pdf', 'text/plain', ...
            ],
            'application/vnd.google-apps.spreadsheet' => [
              'application/x-vnd.oasis.opendocument.spreadsheet',
              'text/tab-separated-values', 'text/csv',...
            ],
            ....
      ],
      'folderColorPalette' => ['#ac725e', ... '#8f8f8f'],
      'importFormats' => [
        'text/rtf' => ['application/vnd.google-apps.document'],
        'text/plain' => ['application/vnd.google-apps.document'],
        'image/png' => ['application/vnd.google-apps.document'],
        'application/msword' => ['application/vnd.google-apps.document'],
        'application/pdf' => ['application/vnd.google-apps.document'],
        'application/rtf' => ['application/vnd.google-apps.document'],
        'text/html' => ['application/vnd.google-apps.document'],
        'text/csv' => ['application/vnd.google-apps.spreadsheet'],
        'image/jpg' => ['application/vnd.google-apps.document'],
        .....
      ],
       'kind' => 'drive#about',
       'maxImportSizes' => ['application/vnd.google-apps.document' => '10485760', ...],
       'maxUploadSize' => '5242880000000',
       'storageQuotaType' => 'Google_Service_Drive_AboutStorageQuota',
       'storageQuotaDataType' => '',
       'teamDriveThemesType' => 'Google_Service_Drive_AboutTeamDriveThemes',
       'teamDriveThemesDataType' => 'array',
       'userType' => 'Google_Service_Drive_User',
       'userDataType' => '',
       'internal_gapi_mappings' => [],
       'modelData' => array (
            'user' => [
              'kind' => 'drive#user', 'me' => true,
              'displayName' => 'google-api-test@astral-...iam.gserviceaccount.com',
              'photoLink' => 'https://lh3.googleusercontent.com/a/default-user=s64',
              'permissionId' => '04952...094',
              'emailAddress' => 'google-api-test@astral-...iam.gserviceaccount.com',
            ],
            'storageQuota' => [
              'limit' => '16106127360', 'usage' => '0',
              'usageInDrive' => '0',    'usageInDriveTrash' => '0',
            ],
            'teamDriveThemes' => [[
                'id' => 'abacus', 'backgroundImageLink' => 'https://....jpg',
                'colorRgb' => '#ea6100',
              ], ...
            ],
            'driveThemes' => [[
                'id' => 'abacus', 'backgroundImageLink' => 'https://....jpg',
                'colorRgb' => '#ea6100',
              ], ...
            ],
      ),
       'processed' => [],

    ))
    */

    /*
    Document structure read at https://developers.google.com/docs/api/reference/rest/v1/documents#Document
    A StructuralElement describes content that provides structure to the document.
    JSON representation {
      "startIndex": integer, "endIndex": integer,
      // Union field content can be only one of the following:
      "paragraph": { object (Paragraph) },
      "sectionBreak": { object (SectionBreak) },
      "table": { object (Table) },
      "tableOfContents": { object (TableOfContents) }
    }*/

    //todo adjust params format
    public function insert_textRun($text, $style = []): array
    {
        if (empty($style)) { //default style
            $style = [];
        }
        return [
            'textRun' => array(
                'content' => $text,
                'textStyle' => $style,
            ),
        ];
    }

    public function insert_text($text, $index = 1)
    {
        $textRequest = new \Google_Service_Docs_InsertTextRequest;
        $textRequest->setText($text);
        $textLocation = new \Google_Service_Docs_Location;
        $textLocation->setIndex($index);
        $textRequest->setLocation($textLocation);
        $request = new \Google_Service_Docs_Request;
        try {
            $request->setInsertText($textRequest);
        } catch (\Exception $e) {
            $this->log(__METHOD__ . '::Google_Service_Docs_InsertTextRequest::setInsertText', $e, $textRequest);
            return null;
        }
        return $request;
    }

    //todo adjust params format
    public function insert_paragraph($startIndex, $textElements = [], $style = []): array
    {
        //\Google_Service_Docs_CreateHeaderRequest
        $endIndex = $startIndex + 1;
        foreach ($textElements as &$element) {
            $elementStart = $endIndex;
            if (isset($element['textRun'])) {
                $elementName = 'textRun';
                $contentField = 'content';
            } elseif (isset($element['insertText'])) {
                $elementName = 'insertText';
                $contentField = 'text';
            }
            $endIndex += empty($element[$elementName][$contentField])
                ? 0
                : strlen($element[$elementName][$contentField]);
            $element[$elementName]['location'] = ['startIndex' => $elementStart, 'endIndex' => $endIndex];
        }
        unset($element);
        $defaultStyle = array(
            //'headingId' => 'h.1jvmhowjlc1r',
            //'namedStyleType' => 'TITLE',
            'direction' => 'LEFT_TO_RIGHT',
        );
        if (empty($style)) {
            $style = $defaultStyle;
        }
        return [
            new \Google_Service_Docs_Request(
                array(
                    'paragraph' => [
                        'location' => ['startIndex' => $startIndex, 'endIndex' => $endIndex],
                        'elements' => $textElements,
                        'paragraphStyle' => array_merge($defaultStyle, $style),
                    ]
                )
            ),
            $endIndex
        ];
    }

    //todo adjust params format
    public function insert_sectionBreak($startIndex, $style = []): array
    {
        if (empty($style)) {
            $style = array(
                'columnSeparatorStyle' => 'NONE',
                'contentDirection' => 'LEFT_TO_RIGHT',
                'sectionType' => 'CONTINUOUS',
            );
        }
        $endIndex = $startIndex + 1;
        return [
            new \Google_Service_Docs_Request(
                array(
                    'sectionBreak' => [
                        'location' => ['startIndex' => $startIndex, 'endIndex' => $endIndex],
                        'sectionStyle' => $style,
                    ],
                )
            ),
            $endIndex
        ];
    }

    //todo adjust params format
    public function insert_table(&$requests, $index, $data = [])
    {
        $rowsQty = count($data);
        $colsQty = empty($data[0]) ? 0 : count($data[0]);
        $req = new \Google_Service_Docs_InsertTableRequest;

        $location = new \Google_Service_Docs_Location;
        $location->setIndex($index);
        $req->setLocation($location);

        foreach ($data as $row) {
            $rowReq = new \Google_Service_Docs_InsertTableRowRequest;
            //(new \Google_Service_Docs_TableRow)->
            $insertBelow = $row; //todo implement
            $rowReq->setInsertBelow($insertBelow);
        }
        //$req->setRows(1);

        $request = new \Google_Service_Docs_Request;
        $request->setInsertTable($req);
        return $request;
        /*
        $endIndex = $index + 1;
        return [
            new \Google_Service_Docs_Request(array(
                'insertTable' => [
                    'location' => ['index' => $index],
                    'columns' => $columnsQty,
                    'rows' => $rowsQty,
                ],
            )),
            $endIndex
        ];*/
    }

    /**
     *
     * @param string $doc_id
     * @param array $lines
     * @return boolean|Google_Service_Docs_BatchUpdateDocumentResponse
     */
    public function update_doc($doc_id, $lines)
    {
        if (!$this->connect(self::SCOPES_GROUP_DOCS)) {
            return false;
        }
        $requests = [];
        $index = 1;
        foreach ($lines as $line) {
            $len = mb_strlen($line);//, 'UTF-16');
            //echo "'$line' index: $index, len: $len, utf-8 len: ".mb_strlen($line, 'UTF-8')."\n";
            $requests[] = $this->insert_text($line . "\n", $index);
            $index += $len + 1;
        }

        $batchUpdateRequest = new \Google_Service_Docs_BatchUpdateDocumentRequest(
            array(
                'requests' => $requests
            )
        );
        try {
            $response = $this->service->documents->batchUpdate($doc_id, $batchUpdateRequest);
        } catch (\Exception $e) {
            $this->log(__METHOD__ . '::documents.batchUpdate', [$e, $doc_id, $batchUpdateRequest]);
            return false;
        }

        return $response;
    }

    public function get_drive_methods(): array
    {
        //https://developers.google.com/drive/api/v3/reference/files
        return [
            'copy' => 'copy file and apply updates with patch semantics',
            'create' => 'Creates a new file.',
            'delete' => 'Permanently delete file owned by the user without moving it to the trash.'
                . ' If the file belongs to a shared drive the user must be an organizer on the parent.'
                . ' If the target is a folder, all descendants owned by the user are also deleted.',
            'emptyTrash' => "Permanently delete all of the user's trashed files",
            'export' => 'Export file to the requested MIME type and get the exported content (limited to 10MB)',
            'generateIds' => 'Generates a set of file IDs which can be provided in create or copy requests.',
            'get' => "Gets a file's metadata or content by ID.",
            'list' => 'Lists or searches files.',
            'update' => "Updates a file's metadata and/or content with patch semantics.",
            'watch' => 'Subscribes to changes to a file',
        ];
    }

    //todo: implement in PHP
    public function fill_document_template()
    {
        //...
        /*python example:
        # "search & replace" API requests for mail merge substitutions
        reqs = [{'replaceAllText': {
                    'containsText': {
                        'text': '{{%s}}' % key.upper(), # {{VARS}} are uppercase
                        'matchCase': True,
                    },
                    'replaceText': value,
                }} for key, value in context]

        # send requests to Docs API to do actual merge
        DOCS.documents().batchUpdate(body={'requests': reqs},
                documentId=copy_id, fields='').execute()
        */
    }

    public function get_drive_files_list()
    {
        if (!$this->connect(self::SCOPES_GROUP_DRIVE)) {
            return [];
        }
        try {
            $list = $this->service->files->listFiles();
        } catch (\Exception $e) {
            $this->process_service_exception(__METHOD__ . '::files.listFiles', $e);
            return [];
        }
        if (empty($list)) {
            return [];
        }
        return $list;
    }
    /* ---> Google_Service_Drive_FileList::__set_state(array(
         'collection_key' => 'files',
         'filesType' => 'Google_Service_Drive_DriveFile',
         'filesDataType' => 'array',
         'incompleteSearch' => false,
         'kind' => 'drive#fileList',
         'nextPageToken' => NULL,
         'internal_gapi_mappings' => array ( ),
         'modelData' => array (
          'files' => array (
            0 => array (
              'kind' => 'drive#file',
              'id' => '1qn4H3...T2Q',
              'name' => 'created by tamarindo6',
              'mimeType' => 'application/vnd.google-apps.document',
            ),
            1 => array (
              'kind' => 'drive#file',
              'id' => '0Bzag...maWxl',
              'name' => 'Getting started',
              'mimeType' => 'application/pdf',
            ),
            2 => array (
              'kind' => 'drive#file',
              'id' => '1iZXa...jEu4',
              'name' => 'test document',
              'mimeType' => 'application/vnd.google-apps.document',
            ),
          ),
        ),
         'processed' =>
        array (
        ),
      )),
    */

    public function get_drives_list()
    {
        if (!$this->connect(self::SCOPES_GROUP_DRIVE)) {
            return [];
        }
        //(new \Google_Service_Drive_Resource_Drives())->listDrives();
        try {
            $list = $this->service->drives->listDrives();
        } catch (\Exception $e) {
            $this->process_service_exception(__METHOD__ . '::drives.listDrives', $e);
            return [];
        }
        if (empty($list)) {
            return [];
        }
        return $list;
    }
    /* ---> Google_Service_Drive_DriveList::__set_state(array(
     'collection_key' => 'drives',
     'drivesType' => 'Google_Service_Drive_Drive',
     'drivesDataType' => 'array',
     'kind' => 'drive#driveList',
     'nextPageToken' => NULL,
     'internal_gapi_mappings' => array ( ),
     'modelData' => array (
        'drives' => array ( ),
     ),
     'processed' => array ( ),
    )) */




    /*
     * S H E E T S
     *
     * https://developers.google.com/sheets/api/guides/authorizing
     * https://developers.google.com/sheets/api/samples/writing
     *
    1. If you just need to read or write cell values, the spreadsheets.values collection is a better choice
     * than the spreadsheets collection. The former's interface is easier to use for simple read/write operations.
    2. Wherever possible, use the batch methods (spreadsheet.batchUpdate, spreadsheet.values.batchGet,
     * and spreadsheet.values.batchUpdate) to bundle multiple requests into a single method call.
     * Using these batch methods improve efficiency; they reduce client HTTP overhead,
     * reduce the number of queries made, reduce the number of revisions on the doc, and ensure atomicity
     * of all the changes in the batch.
     */

    public function create_sheet($title): string
    {
        if (empty($title) || !$this->connect(self::SCOPES_GROUP_SHEETS)) {
            return '';
        }
        $sheet_params = [
            'properties' => [
                'title' => $title
            ]
        ];

        try {
            //(new \Google_Service_Sheets)->spreadsheets;
            //(new \Google_Service_Sheets_Resource_Spreadsheets)->create($postBody)
            $result = $this->service->spreadsheets->create(
                new \Google_Service_Sheets_Spreadsheet($sheet_params)
                ,
                ['fields' => 'spreadsheetId']
            );
        } catch (\Google_Service_Exception $e) {
            $this->process_service_exception(
                __METHOD__ . '::spreadsheets.create',
                $e,
                [$title, $sheet_params]
            );
            return '';
        }
        if (!$result->spreadsheetId) {
            $this->log(
                __METHOD__ . '::spreadsheets.create result',
                $result
            );
            return '';
        }
        $this->sheets_setSheetId($result->spreadsheetId);
        return $result->spreadsheetId;
    }

    public function get_sheet($sheetId)
    {
        //GET https://docs.googleapis.com/v1/spreadsheets/{sheetId}
        //The request body must be empty.
        //If successful, the response body contains an instance of Document.
        if (!$this->connect(self::SCOPES_GROUP_SHEETS)) {
            return false;
        }
        try {
            $doc_instance = $this->service->spreadsheets->get($sheetId);
        } catch (\Exception $e) {
            /* message example: {"error": {
                "code": 403, "message": "The caller does not have permission",
                "errors": [...],
                "status": "PERMISSION_DENIED"
            }} */
            $this->process_service_exception(__METHOD__ . '::service.spreadsheets.get', $e, $sheetId);
            return false;
        }
        return $doc_instance;
    }

    public function update_current_sheet($range, $rows, $inputOption = self::SHEET_INPUT_OPT_RAW)
    {/*
   The spreadsheets.values collection provides the following methods for reading and writing values:
       Range Access        Reading                         Writing
       Single range        spreadsheets.values.get         spreadsheets.values.update
       Multiple ranges     spreadsheets.values.batchGet    spreadsheets.values.batchUpdate
       Appending                                           spreadsheets.values.append
    *
    HTTP EXAMPLE:
    PUT https://sheets.googleapis.com/v4/spreadsheets/spreadsheetId/values/Sheet1!A1:D5?valueInputOption=USER_ENTERED
    {
       "range": "Sheet1!A1:D5",
       "majorDimension": "ROWS",
       "values": [
         ["Item", "Cost", "Stocked", "Ship Date"],
         ["Wheel", "$20.50", "4", "3/1/2016"],
         ["Door", "$15", "2", "3/15/2016"],
         ["Engine", "$100", "1", "3/20/2016"],
         ["Totals", "=SUM(B2:B4)", "=SUM(C2:C4)", "=MAX(D2:D4)"]
       ],
    }
   */
        if (empty($range) || empty($rows)) {
            $this->log(__METHOD__, ['wrong data format' => [$range, $rows]]);
            return false;
        }
        if (!$this->connect(self::SCOPES_GROUP_SHEETS)) {
            $this->log(__METHOD__, 'connection fail');
            return false;
        }
        return $this->sheets_updateRange($range, $rows, $inputOption);
    }

    public function sheets_setSheetId(string $activeSheetId): void
    {
        $this->activeSheetId = $activeSheetId;
    }
    public function sheets_getSheetId(): string
    {
        return $this->activeSheetId;
    }

    public function sheets_setActiveTabPrefix(string $activeTabName): void
    {
        $this->activeTabPrefix = $activeTabName . '!';
    }

    public function sheets_updateRange($range, $rows, $inputOption = self::SHEET_INPUT_OPT_RAW)
    {
        $updates = new \Google_Service_Sheets_ValueRange();
        $updates->setValues($rows);
        try {
            //(new \Google_Service_Sheets_Resource_SpreadsheetsValues)->update($sheetId, ...)
            $result = $this->service->spreadsheets_values->update(
                $this->activeSheetId,
                $this->activeTabPrefix . $range,
                $updates,
                ['valueInputOption' => $inputOption]
            );
        } catch (\Exception $e) {
            $this->process_service_exception(
                __METHOD__ . '::spreadsheets_values.update',
                $e,
                ['range' => $range, 'inputOption' => $inputOption, 'update' => $rows]
            );
            return [];
        }
        return $result;
    }

    public function sheets_updateRangeByCoordinates($startColumn, $startRow, $endColumn, $endRow, $rows)
    {
        $stringRange = $this->sheets_num2alpha($startColumn)
            . ($startRow + 1)
            . ':'
            . $this->sheets_num2alpha($endColumn)
            . ($endRow + 1);

        return $this->sheets_updateRange($stringRange, $rows);
    }

    public function sheets_clearRange($range)
    {
        return $this->service->spreadsheets_values->clear(
            $this->activeSheetId,
            $this->activeTabPrefix . $range,
            new \Google_Service_Sheets_ClearValuesRequest()
        );
    }

    public function sheets_getRange($range)
    {
        return $this->service->spreadsheets_values->get($this->activeSheetId, $this->activeTabPrefix . $range);
    }

    public function sheets_getByRangeList($ranges)
    {
        $params = ['ranges' => $ranges];
        try {
            $result = $this->service->spreadsheets_values->batchGet($this->activeSheetId, $params);
        } catch (\Exception $e) {
            $this->process_service_exception(
                __METHOD__ . '::spreadsheets_values.batchGet',
                $e,
                [$this->activeSheetId, $params]
            );
            return [];
        }
        return $result->getValueRanges();
    }

    public function sheets_mergeRange($startColumn, $startRow, $endColumn, $endRow)
    {
        $mergeCells = new \Google_Service_Sheets_MergeCellsRequest();
        $rangeMerge = new \Google_Service_Sheets_GridRange();
        $rangeMerge->setSheetId($this->activeSheetId);
        $rangeMerge->setStartRowIndex($startRow);
        $rangeMerge->setStartColumnIndex($startColumn);
        $rangeMerge->setEndRowIndex($endRow);
        $rangeMerge->setEndColumnIndex($endColumn);
        $mergeCells->setRange($rangeMerge);
        $mergeCells->setMergeType('MERGE_ALL');
        $request = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $request->setRequests([
            ['mergeCells' => $mergeCells],
        ]);
        return $this->service->spreadsheets->batchUpdate($this->activeSheetId, $request);
    }

    public function sheets_freezeSheetInfo($tabId, $column, $row): void
    {
        $google_Service_Sheets_BatchUpdateSpreadsheetRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $google_Service_Sheets_UpdateSheetPropertiesRequest = new \Google_Service_Sheets_UpdateSheetPropertiesRequest();
        $properties = new \Google_Service_Sheets_SheetProperties();
        $properties->setSheetId($tabId);
        $gridProperties = new \Google_Service_Sheets_GridProperties();
        $gridProperties->setFrozenColumnCount($column);
        $gridProperties->setFrozenRowCount($row);
        $properties->setGridProperties($gridProperties);
        $google_Service_Sheets_UpdateSheetPropertiesRequest->setProperties($properties);
        $google_Service_Sheets_UpdateSheetPropertiesRequest->setFields("gridProperties(frozenRowCount,frozenColumnCount)");
        $google_Service_Sheets_BatchUpdateSpreadsheetRequest->setRequests([
            ['updateSheetProperties' => $google_Service_Sheets_UpdateSheetPropertiesRequest],
        ]);
        $this->service->spreadsheets->batchUpdate($this->activeSheetId, $google_Service_Sheets_BatchUpdateSpreadsheetRequest);
    }

    /**
     * @param $tabName
     * @throws \Exception
     */
    public function sheets_getSheetIdByTabName($tabName, $create = false): string
    {
        $sheets = $this->service->spreadsheets->get($this->activeSheetId);
        /** @var \Google_Service_Sheets_Sheet $sheet */
        foreach ($sheets as $sheet) {
            $google_Service_Sheets_SheetProperties = $sheet->getProperties();
            $title = $google_Service_Sheets_SheetProperties->getTitle();
            if ($title == $tabName) {
                return $google_Service_Sheets_SheetProperties->getSheetId();//-->string
            }
        }
        if ($create) {
            return $this->sheets_createTab($tabName);//-->string
        }
        $this->log(__METHOD__, "Sheet Tab " . $tabName . ' on Sheet ' . $this->activeSheetId . " not found");
        return '';
    }

    public function sheets_createTab($tabName): string
    {
        $sheet = new \Google_Service_Sheets_Sheet();
        $properties = new \Google_Service_Sheets_SheetProperties();
        $properties->setTitle($tabName);
        $sheet->setProperties($properties);
        $request = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $request->setRequests([
            array(
                'addSheet' => [
                    'properties' => $properties,
                ],
            ),
        ]);
        try {
            $result = $this->service->spreadsheets->batchUpdate($this->activeSheetId, $request);
        } catch (\Exception $e) {
            $this->process_service_exception(__METHOD__ . '::spreadsheets.batchUpdate', $e, $request);
            return '';
        }
        $replies = $result->getReplies();
        return empty($replies[0]) ? '' : $replies[0]->getAddSheet()->getProperties()->sheetId;
    }

    //convert column numeric index to alphabetic (1, 2, 3, ... -> A, B, C, ..., Z, AA, ... )
    public function sheets_num2alpha($n): string
    {
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
            $r = chr($n % 26 + 0x41) . $r;
        }
        return $r;
    }

    public function drive_file_move_to_trash($fileId): bool
    {
        if (!$this->connect(self::SCOPES_GROUP_DRIVE)) {
            return false;
        }
        try {
            $moved_file = $this->service->files->trash($fileId);
        } catch (\Exception $e) {
            $this->process_service_exception(__METHOD__ . '::files.trash', $e);
            return false;
        }
        return !empty($moved_file);
    }


    public function drive_file_delete($fileId): string
    {
        if (!$this->connect(self::SCOPES_GROUP_DRIVE)) {
            return self::RESULT__CONNECTION_FAILED;
        }
        try {
            $removed_file = $this->service->files->delete($fileId);
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {//"File not found: 1JFEI...hoSg."
                return self::RESULT__FILE_NOT_FOUND;
            }
            $this->process_service_exception(__METHOD__ . '::files.delete', $e);
            return self::RESULT__WRONG_REQUEST;
        }
        return empty($removed_file) ? self::RESULT__UNKNOWN_ERROR : self::RESULT__SUCCESS;
    }
}
