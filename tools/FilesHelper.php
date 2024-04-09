<?php
namespace App\Models\tools;

use ValueOrError, ValueBoolean, ValueNumber, ValueString, ValueArray;

class FilesHelper
{
    const
        UPLOAD_FILE_MAX_SIZE_MB = 30;

    public static function clear_path_from_dots($path): string
    {
        $sys_sep = DIRECTORY_SEPARATOR;
        if ($sys_sep === '\\') {
            $path = str_replace('/', $sys_sep, $path);
        } elseif ($sys_sep === '/') {
            $path = str_replace('\\', $sys_sep, $path);
        }
        $dirs = array_filter(explode($sys_sep, $path));
        $result = [];
        foreach ($dirs as $dir) {
            if ($dir === '..') {
                array_pop($result);
            } else if ($dir === '.') {
                continue;
            } else {
                $result[] = $dir;
            }
        }
        return (substr($path, 0, 1) === $sys_sep ? $sys_sep : '') . implode($sys_sep, $result);
    }

    public static function create_dirs($dir, $chmod = 0777): bool
    {
        $sys_sep = DIRECTORY_SEPARATOR;
        if ($sys_sep === '/') {
            $dir = str_replace('\\', '/', $dir);
        } else {
            $dir = str_replace('/', '\\', $dir);
        }
        if (is_dir($dir)) {
            return true;
        }
        $dir = self::clear_path_from_dots($dir); // test/abc/../qwerty ==> test/qwerty

        $root = realpath(ROOT_PATH);
        // /qwerty/zxc/../123 --> \qwerty\zxc\..\123 --> C:\qwerty\123 --> \qwerty\123

        if (substr($dir, 0, 1) === $sys_sep || substr($dir, 1, 2) === ':\\') {
            if (strpos($dir, $root) === false) { //if strpos(\logs\secret, \qwerty\123) === false --> return false
                return false;
            }
            $path_from_root = substr($dir, strlen($root) + 1);
        } else {
            $path_from_root = $dir;
        }

        $dirs = array_filter(explode($sys_sep, $path_from_root));
        $result_dir = $root;
        foreach ($dirs as $one) {
            if (substr($one, -1) === ':') {
                continue;
            }
            $result_dir .= $sys_sep . $one;
            if (!is_dir($result_dir)) {
                try {
                    mkdir($result_dir);
                    chmod($result_dir, $chmod);
                } catch (\ErrorException $e) {
                    /*
                    errors_collect(__METHOD__, [
                        'param' => $dir,
                        'mkdir' => $result_dir,
                        'chmod' => $chmod,
                        'error' => $e,
                    ]);*/
                    return false;
                }
            }
        }
        return true;
    }

    public static function load_json_file($path, $use_cache = true, $skip_empty_data = false)
    {
        static $cache = [];
        if (!empty($cache[$path]) && $use_cache) {
            return $cache[$path];
        }
        $title = __METHOD__;
        if (!is_file($path)) {
            if ($skip_empty_data) {
                return [];
            }
            //errors_collect($title, 'file not found ' . $path);
            return false;
        }
        $content = file_get_contents($path);
        if (empty($content)) {
            if ($skip_empty_data) {
                return [];
            }
            //errors_collect($title, 'empty file ' . $path);
            return false;
        }
        $data = json_decode($content, 1);
        if (is_null($data)) {
            //errors_collect($title, 'wrong json ' . $content);
            return false;
        }
        if ($use_cache) {
            $cache[$path] = $data;
            return $cache[$path];
        } else {
            return $data;
        }
    }

    public static function save_json_file($path, $data): bool
    {
        if (!is_dir(dirname($path)) && !self::create_dirs($path)) {
            return false;
        }
        $chmod = !is_file($path);
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $saved = file_put_contents($path, $content);
        if (!$saved) {
            /*
            errors_collect(__METHOD__, [
                'path' => $path,
                'error' => 'file_put_contents fail',
                'last error' => error_get_last(),
                'content' => mb_strlen($content)>200
                    ? mb_substr($content, 0, 90) . '.....' . mb_substr($content, -100)
                    : $content,
            ]);*/
            return false;
        }
        if ($chmod && !chmod($path, 0766)) {
            /*
            errors_collect(__METHOD__, [
                'path' => $path,
                'error' => 'chmod 0766 fail',
                'last error' => error_get_last(),
            ]);*/
            return false;
        }
        return true;
    }

    public static function get_uploads_list(): array
    {
        return $_FILES;
    }

    public static function process_uploading($file_data, $allowed_types, $target_directory): ValueArray
    {
        $r = new ValueArray();
        if (empty($file_data)) {
            return $r->error("File data not found", 'file_data_not_found');
        }
        if (!empty($file_data['error'])) {
            return $r->error(self::parse_uploading_error($file_data), 'uploading_error');
        }
        if (empty($target_directory)) {
            return $r->error("Target directory not set", 'target_directory_not_set');
        }
        if (!self::match_file_type($file_data['tmp_name'], $file_data['name'], $allowed_types)) {
            return $r->error("File type is not allowed", 'file_type_not_allowed');
        }
        //check size
        if ($file_data['size'] > self::UPLOAD_FILE_MAX_SIZE_MB * 1024 * 1024) { //1 MB = 1 048 576 bytes
            return $r->error(
                "File size exceeds limit. Maximum MB" . self::UPLOAD_FILE_MAX_SIZE_MB . " MB",
                'file_size_exceeds_limit'
            );
        }
        if (!is_dir($target_directory) && !self::create_dirs($target_directory, 0766)) {
            //errors_collect(__METHOD__, ["Can't create target directory "=>$target_directory]);
            return $r->error("Can't create target directory", 'cant_create_target_directory');
        }
        //filter name
        $file_data['name'] = str_replace(['/', '..', '\\'], '', $file_data['name']);
        $original_name = mb_strlen($file_data['name']) > 40 ? mb_substr($file_data['name'], -40) : $file_data['name'];
        $target_directory = realpath($target_directory) . '/';
        $local_name = time() . '-' . $original_name;
        //find not used name
        while (is_file($target_directory . $local_name)) {
            $local_name = uniqid('', true) . '-' . $original_name;
        }
        $local_path = $target_directory . $local_name;
        //move_uploaded_file($file_data['tmp_name'], $local_path); // --> permission denied

        try {
            $copy_result = copy($file_data['tmp_name'], $local_path);
        } catch (\Exception $e) {
            /*
            errors_collect(__METHOD__.' copy exception', [
                'source'=> $file_data['tmp_name'],
                'target' => $local_path,
                'exception' => $e,
            ]);*/
            return $r->error("Got exception while copying uploaded file", 'file_copying_exception');
        }
        if ($copy_result && chmod($local_path, 0766)) {
            return $r->setValue(['original_name' => $original_name, 'file' => $local_name]);
        }
        /*
        errors_collect(__METHOD__.' copy', [
            'source'=> $file_data['tmp_name'],
            'target' => $local_path,
            'result' => $copy_result,
        ]);*/
        return $r->error("Fail to copy uploaded file", 'file_copying_failed');
    }

    public static function parse_uploading_error($file_data): string
    {
        if (!empty($file_data['error'])) {
            switch ($file_data['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    return 'No file sent';
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    return 'Exceeded filesize limit';
                default:
                    return 'Unknown errors';
            }
        }
        return '';
    }

    /**
     * check if given file has allowed format
     * @param string $file_path
     * @param string $file_name
     * @param array $allowed_types
     */
    public static function match_file_type($file_path, $file_name, $allowed_types): bool
    {
        foreach ($allowed_types as $type_ext => $type_mime) {
            unset($allowed_types[$type_ext]);
            $allowed_types[strtolower($type_ext)] = strtolower($type_mime);
        }

        $mime_type = self::get_file_mime_type($file_path, $file_name);
        return $mime_type && false !== array_search($mime_type, $allowed_types, true);
    }

    /**
     * get mime type by contents or by name extention
     * @param string $file_path
     * @param string $file_name
     */
    public static function get_file_mime_type($file_path, $file_name = ''): string
    {
        if (class_exists('finfo') && !empty($file_path) && is_file($file_path)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            return $finfo->file($file_path);
        }
        $type_by_ext = [
            'pjpeg' => 'image/pjpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'doc' => 'application/msword',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'rar' => 'application/x-rar-compressed',
            'odt' => 'application/vnd.oasis.opendocument.tex',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        if (empty($file_name)) {
            $file_name = basename($file_path);
        }
        /*
        errors_collect(__METHOD__, [
            "error" => "'finfo' class is not available",
            "file_name" => $file_name,
        ]);*/
        $extension = strtolower(array_reverse(explode('.', $file_name))[0]);
        return isset($type_by_ext[$extension]) ? $type_by_ext[$extension] : '';
    }

    /**
     * starts file downloading or returns ['error'=>'...'] on error
     * @param string $file_path
     * @param string $content_type
     */
    public static function start_downloading($file_path, $content_type = ''): ValueBoolean
    {
        $r = new ValueBoolean();
        if (empty($file_path)) {
            $error = "File path is empty";
            //errors_collect(__METHOD__, [$error=>$file_path]);
            return $r->error($error, 'file_path_required');
        }
        if (!is_file($file_path)) {
            $error = "File not found";
            //errors_collect(__METHOD__, [$error=>$file_path]);
            return $r->error($error, 'file_not_found');
        }
        if (empty($content_type)) {
            $content_type = self::get_file_mime_type($file_path);
        }
        header('Content-Encoding: UTF-8');
        if (!empty($content_type)) {
            header('Content-Type: ' . $content_type . '; charset=UTF-8');
        }
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($file_path));
        header('Accept-Ranges: bytes');
        readfile($file_path);
        return $r->success();
    }

    public static function create_text_file($path, $content, $chmode = 0766): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !self::create_dirs($dir)) {
            return false;
        }
        if (file_put_contents($path, $content) === false) {
            /*
            errors_collect(__METHOD__, [
                "fail to create file"=>$path,
                "last error" => error_get_last(),
                "content to save" => $content,
            ]);*/
            return false;
        }
        if ($chmode < 0766) {
            $chmode = 0766;
        }
        if ($chmode && !chmod($path, $chmode)) {
            /*
            errors_collect(__METHOD__, [
                "fail to chmode file"=>$path,
                "last error" => error_get_last(),
                "content to save" => $content,
                "chmode" => sprintf('0%o', $chmode),
            ]);*/
            return false;
        }
        return true;
    }

    /**
     * append text to file content
     * @param string $path
     * @param string $content
     * @param int $chmode 0rwx access settings
     */
    public static function append($path, $content, $chmode = 0766): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !self::create_dirs($dir)) {
            return false;
        }
        if (!file_exists($path)) {
            return self::create_text_file($path, $content, $chmode);
        }
        if (file_put_contents($path, $content, FILE_APPEND) === false) {
            /*
            errors_collect(__METHOD__, [
                "fail to append content to file"=>$path,
                "last error" => error_get_last(),
                "content" => $content,
            ]);*/
            return false;
        }
        return true;
    }

    public static function remove_files_by_mask($directory, $mask_array, $max_date = false): ValueArray
    {
        $r = new ValueArray();
        if (empty($mask_array)) {
            return $r->error('mask array required', 'mask_array_required');
        }
        if (is_string($mask_array)) {
            $mask_array = [$mask_array];
        }
        if (!class_exists('finfo')) {
            return $r->error('finfo class not found', 'finfo_class_not_found');
        }
        $removed = [];
        foreach ($mask_array as $mask) {
            $mask_path = $directory . DIRECTORY_SEPARATOR . $mask;
            foreach (glob($mask_path) as $filepath) {
                if ($max_date) {
                    $moddate = filemtime($filepath);
                    if ($moddate === false || $moddate >= $max_date) {
                        continue;
                    }
                }
                unlink($filepath);
                $removed[] = basename($filepath);
            }
        }
        return $r->setValue($removed);
    }
}
