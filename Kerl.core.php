<?php
/*
 * © Aleksej Martirosyan 2012
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);

class Kerl {
    static $_LESS_DIR_INPUT_FILES_ = '/var/www/server/';
    static $_LESS_DIR_OUTPUT_FILES_ = '/var/www/server/';
    static $_LESS_COMPRESS_ = false;
    static $_LESS_OUTPUT_CHANGED_FILES_ = true;
    static $_KERL_INFO_FILE_ = 'info.kerl';

    /**
     * md5 hash of the file info.kerl
     */
    static private $less_fileInfoMd5 = null;

    /*
     * array of options files
     */
    static private $less_filesInfoParamArray;

    static private function Less () {
        $newFileName = null;
        $compress = null;
        $tmp = null;
        $kerlFile = null;
        $filesParam = null;
        $basename = null;

        clearstatcache();

        if (!file_exists(self::$_LESS_DIR_INPUT_FILES_)) {
            exit('dir "'.self::$_LESS_DIR_INPUT_FILES_.'" not found');
        }

        if (!file_exists(self::$_LESS_DIR_OUTPUT_FILES_)) {
            exit('dir "'.self::$_LESS_DIR_OUTPUT_FILES_.'" not found');
        }

        if (!self::$less_filesInfoParamArray) {
            if (file_exists(self::$_LESS_DIR_INPUT_FILES_ . self::$_KERL_INFO_FILE_)) {
                $kerlFile = file_get_contents(self::$_LESS_DIR_INPUT_FILES_ . self::$_KERL_INFO_FILE_);
                $filesParam = unserialize($kerlFile);
                unset($kerlFile);
            } else {
                $tmp = fopen(self::$_LESS_DIR_INPUT_FILES_ . self::$_KERL_INFO_FILE_,'a+');
                fclose($tmp);
            }
        } else {
            $filesParam = self::$less_filesInfoParamArray;
        }

        $arrayFiles = glob(self::$_LESS_DIR_INPUT_FILES_.'/*\.less');

        foreach ($arrayFiles as $val) {
            $basename = basename($val);
            $newFileName = self::$_LESS_DIR_OUTPUT_FILES_.mb_substr($basename, 0, -5).'.css';
            if (self::$_LESS_COMPRESS_) {
                $compress = '-x';
            }

            if ($filesParam[$basename]) {
                if ($filesParam[$basename]['fileInfo']['mtime'] != filemtime($val)) {
                    $tmp = system("lessc $val > $newFileName $compress", $status);
                    if (!empty($tmp) || $status != 1) {
                        echo $tmp."\n";
                    }
                    $filesParam[$basename]['fileInfo']['mtime'] = filemtime($val);
                }
            } else {
                $tmp = system("lessc $val > $newFileName $compress", $status);
                if (!empty($tmp) || $status != 1) {
                    echo $tmp."\n";
                }
                $filesParam[$basename]['fileInfo']['mtime'] = filemtime($val);
            }

            self::$less_filesInfoParamArray = $filesParam;

            $serializeData = serialize($filesParam);
            $serializeDataMd5 = md5($serializeData);

            if ($serializeDataMd5 != self::$less_fileInfoMd5) {
                file_put_contents(self::$_LESS_DIR_INPUT_FILES_ . self::$_KERL_INFO_FILE_, $serializeData);

                if (self::$_LESS_OUTPUT_CHANGED_FILES_ && self::$less_fileInfoMd5 !== null) {
                    echo 'Сhanged the file "' . $basename . "\" - " . date('Y-m-d H:i:s') . " \n";
                }

                self::$less_fileInfoMd5 = $serializeDataMd5;
            }
        }
    }

    static public function startLess () {
        while (true) {
            self::Less();
            flush();
            usleep(300000);
        }
    }
}