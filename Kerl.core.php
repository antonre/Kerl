<?php
/*
 * © Aleksej Martirosyan 2012
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);

define('_KERL_INFO_FILE_PATH_', dirname(__FILE__) . '/');

class Kerl {
    static $_KERL_INFO_FILE_PATH_ = _KERL_INFO_FILE_PATH_;

    static $_LESS_DIR_INPUT_FILES_ = '/var/www/server/';
    static $_LESS_DIR_OUTPUT_FILES_ = '/var/www/server/';
    static $_LESS_COMPRESS_ = false;
    static $_LESS_OUTPUT_CHANGED_FILES_ = true;
    static $_LESS_INFO_FILE_ = 'infoLess.kerl';

    static $_CSSJOIN_INPUT_FILES_BASE_PATH_ = '';
    static $_CSSJOIN_OUTPUT_FILES_BASE_PATH_ = '';
    static $_CSSJOIN_INPUT_FILES_ = array();
    static $_CSSJOIN_OUTPUT_FILE_DEFAULT_ = '/var/www/server/defauly.css';
    static $_CSSJOIN_INFO_FILE_ = 'infoCssJoin.kerl';

    /**
     * md5 hash of the file info.kerl
     */
    static private $less_fileInfoMd5 = null;
    static private $cssJoin_fileInfoMd5 = null;

    /*
     * array of options files
     */
    static private $less_filesInfoParamArray;
    static private $cssJoin_filesInfoParamArray;

    static private function less () {
        $pathKerlFile = self::$_KERL_INFO_FILE_PATH_ . self::$_LESS_INFO_FILE_;
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
            if (file_exists($pathKerlFile)) {
                $kerlFile = file_get_contents($pathKerlFile);
                $filesParam = unserialize($kerlFile);
                unset($kerlFile);
            } else {
                $tmp = fopen($pathKerlFile, 'a+');
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
                file_put_contents(self::$_LESS_DIR_INPUT_FILES_ . self::$_LESS_INFO_FILE_, $serializeData);

                if (self::$_LESS_OUTPUT_CHANGED_FILES_ && self::$less_fileInfoMd5 !== null) {
                    echo 'Сhanged the file "' . $basename . "\" - " . date('Y-m-d H:i:s') . " \n";
                }

                self::$less_fileInfoMd5 = $serializeDataMd5;
            }
        }
    }

    static public function startLess () {
        while (true) {
            self::less();
            flush();
            usleep(300000);
        }
    }

    static public function cssJoin () {
        $pathKerlFile = self::$_KERL_INFO_FILE_PATH_ . self::$_CSSJOIN_INFO_FILE_;
        $content = array();
        $flag = false;

        if (!self::$cssJoin_filesInfoParamArray) {
            if (file_exists($pathKerlFile)) {
                $kerlFile = file_get_contents($pathKerlFile);
                $filesParam = unserialize($kerlFile);
                unset($kerlFile);
            } else {
                $tmp = fopen($pathKerlFile, 'a+');
                fclose($tmp);
            }
        } else {
            $filesParam = self::$cssJoin_filesInfoParamArray;
        }

        foreach (self::$_CSSJOIN_INPUT_FILES_ as $key => $val) {
            $file = self::$_CSSJOIN_INPUT_FILES_BASE_PATH_ . $key;

            if (!file_exists($file)) {
                echo 'File "' . $file . '" not found';
                exit;
            }

            if ($filesParam[$file]) {
                if ($filesParam[$file]['fileInfo']['mtime'] != filemtime($file) || $filesParam[$file]['inputFile'] != $val) {
                    $flag = true;
                    $filesParam[$file]['fileInfo']['mtime'] = filemtime($file);
                    $filesParam[$file]['inputFile'] = $val;
                }
            } else {
                $flag = true;
                $filesParam[$file]['fileInfo']['mtime'] = filemtime($file);
                $filesParam[$file]['inputFile'] = $val;
            }
        }

        if ($flag) {
            foreach (self::$_CSSJOIN_INPUT_FILES_ as $key => $val) {
                $input = self::$_CSSJOIN_INPUT_FILES_BASE_PATH_ . $key;
                $output = self::$_CSSJOIN_OUTPUT_FILES_BASE_PATH_ . $val;

                $text = file_get_contents($input);

                if ($text !== false) {
                    $content[$output] .= $text;
                }
            }

            foreach ($content as $key => $val) {
                file_put_contents($key, $val);
            }

            $serializeData = serialize($filesParam);
            $serializeDataMd5 = md5($serializeData);

            if ($serializeDataMd5 != self::$cssJoin_fileInfoMd5) {
                file_put_contents($pathKerlFile, $serializeData);
                self::$cssJoin_fileInfoMd5 = $serializeDataMd5;
            }
        }
    }

    static public function startCssJoin () {
        $kerlFileDir = dirname(self::$_KERL_INFO_FILE_PATH_ . self::$_CSSJOIN_INFO_FILE_);
        $flag = true;
        $file = '';
        $dir = '';

        if (!is_dir($kerlFileDir)) {
            echo 'Directory Kerl-file "' . $kerlFileDir . '" not available' . "\n";
            $flag = false;
        }

        foreach (self::$_CSSJOIN_INPUT_FILES_ as $key => $val) {
            $file = self::$_CSSJOIN_INPUT_FILES_BASE_PATH_ . $key;

            if (!file_exists($file)) {
                echo 'File "' . $file . '" not found' . "\n";
                $flag = false;
            }
        }

        foreach (self::$_CSSJOIN_INPUT_FILES_ as $key => $val) {
            $dir = dirname(self::$_CSSJOIN_OUTPUT_FILES_BASE_PATH_ . $val);

            if (!is_dir($dir)) {
                echo 'Dir "' . $dir . '" not found' . "\n";
                $flag = false;
            }
        }

        if ($flag === false) {
            exit;
        }

        while (true) {
            self::cssJoin();
            flush();
            usleep(300000);
        }
    }
}