<?php

define('ROOT_PATH', __DIR__); //todo: move constant outside

 //todo: move Value... classes outside
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
class ValueString extends ValueOrError
{
    public function setValue($string)
    {
        $this->value = (string) $string;
        return $this;
    }
}

class Archiver
{
    const
        FORMAT_ZIP = 'zip',
        FORMAT_TARGZ = 'tar-gzip',
        FORMAT_TARBZ2 = 'tar-bzip2',

        ERRCODE__WRONG_ARCHIVE_PATH = 'wrong_archive_path',
        ERRCODE__WRONG_FILES_LIST = 'wrong_files_list',
        ERRCODE__FORMAT_NOT_SUPPORTED = 'format_not_supported',
        ERRCODE__ARCHIVE_NOT_FOUND = 'archive_not_found',
        ERRCODE__FORMAT_UNSUPPORTED = 'format_unsupported',
        ERRCODE__CANT_OPEN_FOR_WRITING = 'cant_open_for_writing',
        ERRCODE__CANT_ADD_DIRECTORY = 'cant_add_directory',
        ERRCODE__CANT_READ_FILE = 'cant_read_file',
        ERRCODE__CANT_ADD_FILE = 'cant_add_file',
        ERRCODE__CANT_OPEN = 'cant_open',
        ERRCODE__NO_FILES = 'no_files',
        ERRCODE__CANT_EXTRACT = 'cant_extract',
        ERRCODE__WRONG_FORMAT = 'wrong_format',
        ERRCODE__PACKED_FILE_NOT_FOUND = 'packed_file_not_found',
        ERRCODE__UNEXPECTED_ERROR = 'unexpected_error';

    public static function get_formats_support(): array
    {
        static $result = [];
        if (empty($result)) {
            $result = [
                self::FORMAT_ZIP => class_exists('ZipArchive'),
                self::FORMAT_TARGZ => false,
                self::FORMAT_TARBZ2 => false,
            ];
            if (class_exists('Phar') && class_exists('PharData')) {
                $result[self::FORMAT_TARGZ] = \PharData::canCompress(\Phar::GZ);
                $result[self::FORMAT_TARBZ2] = \PharData::canCompress(\Phar::BZ2);
            }
        }
        return $result;
    }

    public static function get_extension_by_format($format): string
    {
        switch ($format) {
            case self::FORMAT_ZIP:
                return 'zip';
            case self::FORMAT_TARGZ:
                return 'tar.gz';
            case self::FORMAT_TARBZ2:
                return 'tar.bz2';
            default:
                return '';
        }
    }

    public static function get_format_by_extension($extension): string
    {
        switch ($extension) {
            case 'zip':
                return self::FORMAT_ZIP;
            case 'tar.gz':
                return self::FORMAT_TARGZ;
            case 'tar.bz2':
                return self::FORMAT_TARBZ2;
            default:
                return '';
        }
    }

    /* $za = new ZipArchive();
    echo "Files qty: " . $za->numFiles . "\n";
    echo "Status: " . $za->status  . "\n";
    echo "System status: " . $za->statusSys . "\n";
    echo "File name: " . $za->filename . "\n";
    echo "Comment: " . $za->comment . "\n";
    $za->statIndex($i) -->
        [name] => data/x.luac
        [index] => 451
        [crc] => -1266183263
        [size] => 2936
        [mtime] => 1464790482
        [comp_size] => 976
        [comp_method] => 8

    $za->setArchiveComment('comment-text');
    $za->addFromString('file-name.txt', 'file-content');
    */


    public static function pack_files($archive_path, $files, $format = ''): ValueBoolean
    {
        $r = new ValueBoolean();
        if (empty($archive_path) || !is_string($archive_path)) {
            return $r->error('Wrong archive path', self::ERRCODE__WRONG_ARCHIVE_PATH);
        }
        if (empty($files) || !is_array($files)) {
            return $r->error('Wrong files list: ' . var_export($files, 1), self::ERRCODE__WRONG_FILES_LIST);
        }
        $format = empty($format) ? self::FORMAT_ZIP : $format;
        $support = self::get_formats_support();
        if (empty($support[$format])) {
            return $r->error('Used format is not supported', self::ERRCODE__FORMAT_NOT_SUPPORTED);
        }
        switch ($format) {
            case self::FORMAT_ZIP:
                $result = self::zip_files($archive_path, $files);
                break;
            case self::FORMAT_TARGZ:
            case self::FORMAT_TARBZ2:
                $result = self::phar_files($archive_path, $files, $format);
                break;
        }
        if ($result->error) {
            return $r->error($result->errorText, $result->errorCode, $result->trigger);
        }
        return $r->success();
    }

    public static function unpack_archive($archive_path, $format = ''): ValueBoolean
    {
        $r = new ValueBoolean();
        if (empty($archive_path) || !is_string($archive_path)) {
            return $r->error('Wrong archive path', self::ERRCODE__WRONG_ARCHIVE_PATH);
        }
        if (!is_file($archive_path) || filesize($archive_path) === 0) {
            return $r->error('Archive was not found or is empty', self::ERRCODE__ARCHIVE_NOT_FOUND);
        }
        $format = empty($format) ? self::FORMAT_ZIP : $format;
        $support = self::get_formats_support();
        if (empty($support[$format])) {
            return $r->error('Used format is not supported', self::ERRCODE__FORMAT_UNSUPPORTED);
        }
        switch ($format) {
            case self::FORMAT_ZIP:
                $result = self::unzip_archive($archive_path);
                break;
            case self::FORMAT_TARGZ:
            case self::FORMAT_TARBZ2:
                $result = self::unphar_archive($archive_path, $format);
                break;
        }
        if ($result->error) {
            return $r->error($result->errorText, $result->errorCode, $result->trigger);
        }
        return $r->success();
    }

    private static function zip_files($archive_path, $files): ValueString
    {
        $r = new ValueString();
        $z = new \ZipArchive();
        if ($z->open($archive_path, \ZipArchive::CREATE) !== TRUE) { //ZipArchive::OVERWRITE
            return $r->error(
                'Failed to open archive for writing. Status: ' . $z->getStatusString(),
                self::ERRCODE__CANT_OPEN_FOR_WRITING
            );
        }
        $added_dirs = [];
        foreach ($files as $file_relative_path) {
            $path_parts = explode('/', $file_relative_path);
            $n = count($path_parts);
            if ($n > 1) {
                unset($path_parts[$n - 1]); //cut file name
                $dir = '';
                foreach ($path_parts as $part) {
                    if (empty($dir)) {
                        $dir = $part;
                    } else {
                        $dir .= '/' . $part;
                    }
                    if (!isset($added_dirs[$dir])) {
                        if (!$z->addEmptyDir($dir)) {
                            $status = $z->getStatusString();
                            $z->close();
                            return $r->error(
                                "Failed to add directory $dir. Status: $status",
                                self::ERRCODE__CANT_ADD_DIRECTORY
                            );
                        }
                        $added_dirs[$dir] = 1;
                    }
                }
            }
            $full_path = ROOT_PATH . '/' . $file_relative_path;
            if (!is_file($full_path) || !is_readable($full_path)) {
                $z->close();
                return $r->error(
                    'file not found or is not readable',
                    self::ERRCODE__CANT_READ_FILE
                );
            }
            if (!$z->addFile($full_path, $file_relative_path)) {
                $status = $z->getStatusString();
                $z->close();
                return $r->error(
                    "ZipArchive status: $status",
                    self::ERRCODE__CANT_ADD_FILE
                );
            }

            /*
             It seems that addFile() will return TRUE if the file stat command returns correctly,
             * but the operation itself will not happen yet. Deleting a file is always possible.
             * Using a temporary file and deleting it immediately after addFile(...)===TRUE
             * results with no archive created and no file added - The operation silenty fails.

             When adding over 1024 files (depending on your open files limit) the server stops adding files,
             * resulting in a status 11 ZIPARCHIVE::ER_OPEN in archive. There is no warning when exceeding
             * this open files limit with addFiles. ZipArchive internally stores the file descriptors of
             * all the added files and only on close writes the contents to archive
             *
             */
        }
        $status = $z->getStatusString();
        $z->close();
        return $r($status);
    }

    private static function unzip_archive($archive_path): ValueBoolean
    {
        $r = new ValueBoolean();
        $extract_to_dir = ROOT_PATH;
        if (empty($archive_path) || !is_file($archive_path)) {
            return $r->error('Wrong archive path', self::ERRCODE__WRONG_ARCHIVE_PATH);
        }
        $z = new \ZipArchive;
        if ($z->open($archive_path) !== TRUE) {
            return $r->error(
                'ZipArchive status: ' . $z->getStatusString(),
                self::ERRCODE__CANT_OPEN
            );
        }
        if (empty($z->numFiles)) {
            $z->close();
            return $r->error('no files to extract', self::ERRCODE__NO_FILES);
        }
        for ($i = 0; $i < $z->numFiles; $i++) {
            $path_in_archive = array($z->getNameIndex($i));
            if (!$z->extractTo($extract_to_dir, $path_in_archive)) {
                $status = $z->getStatusString();
                $z->close();
                return $r->error(
                    "Failed to extract with status $status",
                    self::ERRCODE__CANT_EXTRACT
                );
            }
        }
        $z->close();
        return $r->success();
    }

    private static function phar_files($archive_path, $files, $format): ValueBoolean
    {
        $r = new ValueBoolean();
        if (empty($format) || !in_array($format, [self::FORMAT_TARGZ, self::FORMAT_TARBZ2])) {
            return $r->error('Use tar.gz or tar.bz2 format', self::ERRCODE__WRONG_FORMAT);
        }
        if ($format === self::FORMAT_TARGZ) {
            $ext = '.gz';
            $method = \Phar::GZ;
            if (substr($archive_path, -7) === '.tar.gz') {
                $tar_path = substr($archive_path, 0, strlen($archive_path) - 7) . '.tar';
            } else {
                $tar_path = $archive_path . '.tar';
            }
        } else {
            $ext = '.bz2';
            $method = \Phar::BZ2;
            if (substr($archive_path, -8) === '.tar.bz2') {
                $tar_path = substr($archive_path, 0, strlen($archive_path) - 8) . '.tar';
            } else {
                $tar_path = $archive_path . '.tar';
            }
        }
        $packed_path = $tar_path . $ext;
        chdir(ROOT_PATH);
        try {
            $pd = new \PharData($tar_path);
            //$pd[$source_file1] = 'test1'; //setting file content manually
            foreach ($files as $file_rel_path) {
                $pd->addFile($file_rel_path);
            }
            //$a->buildFromDirectory('path-to-directory');
            $pd->compress($method); //pack with tar and then pack tar-file with gz
            //! destroys everything after '.' in filename and replaces it with .tar.xxx extension
            //$a->compressFiles($method); //compress every file apartly, tar does not support it
            if (!file_exists($packed_path)) {
                return $r->error(
                    'Archive was not created. Unknown error.',
                    self::ERRCODE__PACKED_FILE_NOT_FOUND
                );
            }
            unlink($tar_path);
            //foreach ($pd as $file) { echo $file->getFileName(); }
            if ($packed_path !== $archive_path) {
                rename($packed_path, $archive_path);
            }
        } catch (Exception $e) {
            return $r->error('PharData exception: ' . $e, self::ERRCODE__UNEXPECTED_ERROR);
        }
        return $r->success();
    }


    private static function unphar_archive($archive_path): ValueBoolean
    {
        $r = new ValueBoolean();
        try {
            $pd = new \PharData($archive_path, \RecursiveDirectoryIterator::SKIP_DOTS);
            /*if(! $pd->isFileFormat(...)) {
                return $r->error('Wrong archive format');
            }*/
            $pd->extractTo(ROOT_PATH, NULL, true); //true - replace existing files
        } catch (Exception $e) {
            return $r->error('PharData exception: ' . $e, self::ERRCODE__UNEXPECTED_ERROR);
        }
        return $r->success();
    }
}
