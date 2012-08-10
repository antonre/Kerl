<?php
/*
 * © Aleksej Martirosyan 2012
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);

define('_KERL_INFO_FILE_PATH_', dirname(__FILE__) . '/');

class Kerl {
    static $_KERL_INFO_FILE_PATH_ = _KERL_INFO_FILE_PATH_;

    static $_LESS_DIR_INPUT_FILES_ = '/';
    static $_LESS_DIR_OUTPUT_FILES_ = '/';
    static $_LESS_COMPRESS_ = false;
    static $_LESS_OUTPUT_CHANGED_FILES_ = true;
    static $_LESS_INFO_FILE_ = 'infoLess.kerl';

    static $_CSSJOIN_INPUT_FILES_BASE_PATH_ = '/';
    static $_CSSJOIN_OUTPUT_FILES_BASE_PATH_ = '/';
    static $_CSSJOIN_INPUT_FILES_ = array();
    static $_CSSJOIN_INFO_FILE_ = 'infoCssJoin.kerl';

    static private $_MD_KERL_INFO_FILE = null;

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

        clearstatcache();

        if (!self::$less_filesInfoParamArray) {
            if (file_exists($pathKerlFile)) {
                $kerlFile = file_get_contents($pathKerlFile);
                $filesParam = unserialize($kerlFile);
                self::$less_filesInfoParamArray = $filesParam;
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
                if ($filesParam[$basename]['fileInfo']['mtime'] != filemtime($val) || $filesParam[$basename]['outputFile'] !==  $newFileName) {
                    $tmp = system("lessc $val > $newFileName $compress", $status);

                    if (!empty($tmp) || $status != 1) {
                        echo $tmp."\n";
                    }

                    $filesParam[$basename]['fileInfo']['mtime'] = filemtime($val);
                    $filesParam[$basename]['outputFile'] = $newFileName;
                }
            } else {
                $tmp = system("lessc $val > $newFileName $compress", $status);

                if (!empty($tmp) || $status != 1) {
                    echo $tmp."\n";
                }

                $filesParam[$basename]['fileInfo']['mtime'] = filemtime($val);
                $filesParam[$basename]['outputFile'] = $newFileName;
            }

            self::$less_filesInfoParamArray = $filesParam;

            $serializeData = serialize($filesParam);
            $serializeDataMd5 = md5($serializeData);

            if ($serializeDataMd5 != self::$less_fileInfoMd5) {
                file_put_contents($pathKerlFile, $serializeData);

                if (self::$_LESS_OUTPUT_CHANGED_FILES_ && self::$less_fileInfoMd5 !== null) {
                    echo 'Сhanged the file "' . $basename . "\" - " . date('Y-m-d H:i:s') . " \n";
                }

                self::$less_fileInfoMd5 = $serializeDataMd5;
            }
        }
    }

    static private function startLess () {
        $kerlFileDir = dirname(self::$_KERL_INFO_FILE_PATH_ . self::$_LESS_INFO_FILE_);
        $flag = true;

        if (!is_dir($kerlFileDir)) {
            echo 'Directory Kerl-file "' . $kerlFileDir . '" not available' . "\n";
            $flag = false;
        }

        if (!is_dir(self::$_LESS_DIR_INPUT_FILES_)) {
            echo 'Dir "'.self::$_LESS_DIR_INPUT_FILES_.'" not found' . "\n";
            $flag = false;
        }

        if (!is_dir(self::$_LESS_DIR_OUTPUT_FILES_)) {
            echo 'Dir "'.self::$_LESS_DIR_OUTPUT_FILES_.'" not found' . "\n";
            $flag = false;
        }

        if ($flag === false) {
            exit;
        }
    }

    static private function cssJoin () {
        $pathKerlFile = self::$_KERL_INFO_FILE_PATH_ . self::$_CSSJOIN_INFO_FILE_;
        $content = array();
        $flag = false;

        clearstatcache();

        if (!self::$cssJoin_filesInfoParamArray) {
            if (file_exists($pathKerlFile)) {
                $kerlFile = file_get_contents($pathKerlFile);
                $filesParam = unserialize($kerlFile);
                self::$cssJoin_filesInfoParamArray = $filesParam;
                unset($kerlFile);
            } else {
                $tmp = fopen($pathKerlFile, 'a+');
                fclose($tmp);
            }
        } else {
            $filesParam = self::$cssJoin_filesInfoParamArray;
        }

        foreach (self::$_CSSJOIN_INPUT_FILES_ as $key => $val) {
            $file = $key;

            if (!file_exists($file)) {
                echo 'File "' . $file . '" not found';
                exit;
            }

            if ($filesParam[$file] && !in_array($filesParam[$file]['outputFile'], self::$_CSSJOIN_INPUT_FILES_)) {
                unlink($filesParam[$file]['outputFile']);
            }

            if ($filesParam[$file]) {
                if ($filesParam[$file]['fileInfo']['mtime'] !== filemtime($file) || $filesParam[$file]['outputFile'] !== $val) {
                    $filesParam[$file]['fileInfo']['mtime'] = filemtime($file);
                    $filesParam[$file]['outputFile'] = $val;
                    $flag = true;
                }
            } else {
                $flag = true;
                $filesParam[$file]['fileInfo']['mtime'] = filemtime($file);
                $filesParam[$file]['outputFile'] = $val;
            }
        }

        if ($flag) {
            foreach (self::$_CSSJOIN_INPUT_FILES_ as $key => $val) {
                $input = $key;
                $output = $val;

                $text = file_get_contents($input);

                if ($text !== false) {
                    $content[$output] .= $text;
                }
            }

            foreach ($content as $key => $val) {
                file_put_contents($key, $val);
            }

            self::$cssJoin_filesInfoParamArray = $filesParam;

            $serializeData = serialize($filesParam);
            $serializeDataMd5 = md5($serializeData);

            if ($serializeDataMd5 != self::$cssJoin_fileInfoMd5) {
                file_put_contents($pathKerlFile, $serializeData);
                self::$cssJoin_fileInfoMd5 = $serializeDataMd5;
            }

            echo 'Сombined files - ' . date('Y-m-d H:i:s') . " \n";
        }
    }

    static private function startCssJoin () {
        $kerlFileDir = dirname(self::$_KERL_INFO_FILE_PATH_ . self::$_CSSJOIN_INFO_FILE_);
        $flag = true;

        if (!is_dir($kerlFileDir)) {
            echo 'Directory Kerl-file "' . $kerlFileDir . '" not available' . "\n";
            $flag = false;
        }

        foreach (self::$_CSSJOIN_INPUT_FILES_ as $key => $val) {
            $file = self::$_CSSJOIN_INPUT_FILES_BASE_PATH_ . $key;
            unset(self::$_CSSJOIN_INPUT_FILES_[$key]);
            self::$_CSSJOIN_INPUT_FILES_[$file] = $val;

            if (!file_exists($file) && !is_dir($file)) {
                echo 'File or dir "' . $file . '" not found' . "\n";
                $flag = false;
            } else if (is_dir($file)) {
                $arrayFiles = glob($file . '*');
                $tmpArray = array();

                foreach ($arrayFiles as $val2) {
                    if (!is_dir($val2)) {
                        $tmpArray[$val2] = $val;
                    }
                }

                unset(self::$_CSSJOIN_INPUT_FILES_[$file]);
                self::$_CSSJOIN_INPUT_FILES_ = array_merge(self::$_CSSJOIN_INPUT_FILES_, $tmpArray);
            }
        }

        foreach (self::$_CSSJOIN_INPUT_FILES_ as $key => $val) {
            self::$_CSSJOIN_INPUT_FILES_[$key] =  self::$_CSSJOIN_OUTPUT_FILES_BASE_PATH_ . $val;
            $dir = dirname(self::$_CSSJOIN_INPUT_FILES_[$key]);

            if (!is_dir($dir)) {
                echo 'Dir "' . $dir . '" not found' . "\n";
                $flag = false;
            }
        }

        if ($flag === false) {
            exit;
        }
    }

    static private function parseIni ($tip) {
        $kerlFileParam = self::$_KERL_INFO_FILE_PATH_ . 'conf.ini';
        $flag = true;

        if (!file_exists($kerlFileParam)) {
            echo 'File Kerl-config "' . $kerlFileParam . '" not available' . "\n";
            $flag = false;
        } else {

            $arrayParam = parse_ini_file($kerlFileParam, true);

            switch ($tip) {
                case 'cssJoin': {

                    if (!$arrayParam['cssJoin']) {
                        echo 'Section of "cssJoin" is not found in the file "' . $kerlFileParam . '"';
                        $flag = false;
                    }

                    if (!$arrayParam['cssJoin']['files']) {
                        echo 'Param of "files" is not found in the section "cssJoin" ("' . $kerlFileParam . '")';
                        $flag = false;
                    }

                    if (!$flag) exit;

                    if ($arrayParam['cssJoin']['basePathInput']) {
                        self::$_CSSJOIN_INPUT_FILES_BASE_PATH_ = trim($arrayParam['cssJoin']['basePathInput']);
                    }

                    if ($arrayParam['cssJoin']['basePathInput']) {
                        self::$_CSSJOIN_OUTPUT_FILES_BASE_PATH_ = trim($arrayParam['cssJoin']['basePathOutput']);
                    }

                    self::$_CSSJOIN_INPUT_FILES_ = null;

                    $tmpArray = explode("\n", $arrayParam['cssJoin']['files']);
                    $tmpArraySizeof = sizeof($tmpArray);
                    $tmpArray2 = array();

                    for($i = 0 ; $i < $tmpArraySizeof; ++$i) {
                        $val = trim($tmpArray[$i]);

                        if ($val === '') {
                            unset($tmpArray[$i]);
                        } else {
                            $tmpArray2 = explode('>', $tmpArray[$i]);

                            foreach ($tmpArray2  as $key => $val) {
                                $val = trim($val);
                                $val = str_replace(array('"', "'"), '', $val);
                                $tmpArray2[$key] = $val;

                                if ($val === '') {
                                    unset($tmpArray2);
                                }
                            }

                            if ($tmpArray2) {
                                self::$_CSSJOIN_INPUT_FILES_[$tmpArray2[0]] = $tmpArray2[1];
                            }
                        }
                    }

                    self::startCssJoin();
                    break;
                }

                case 'less': {

                    if (!$arrayParam['less']) {
                        echo 'Section of "less" is not found in the file "' . $kerlFileParam . '"';
                        $flag = false;
                    }

                    if (!$arrayParam['less']['pathInput']) {
                        echo 'Param of "pathInput" is not found in the section "less" ("' . $kerlFileParam . '")';
                        $flag = false;
                    } else {
                        self::$_LESS_DIR_INPUT_FILES_ = trim($arrayParam['less']['pathInput']);
                    }

                    if (!$arrayParam['less']['pathOutput']) {
                        echo 'Param of "pathOutput" is not found in the section "less" ("' . $kerlFileParam . '")';
                        $flag = false;
                    } else {
                        self::$_LESS_DIR_OUTPUT_FILES_ = trim($arrayParam['less']['pathOutput']);
                    }

                    if (!$flag) exit;

                    self::startLess();
                    break;
                }
            }
        }
    }

    static public function start ($tip) {
        switch ($tip) {
            case 'cssJoin': {
                self::parseIni($tip);
                $i = 0;

                while (true) {

                    if ($i === 40) {
                        $i = 0;
                        self::parseIni($tip);
                    }

                    self::cssJoin();
                    flush();
                    usleep(300000);
                    ++$i;
                }

                break;
            }

            case 'less': {
                self::parseIni($tip);
                $i = 0;

                while (true) {

                    if ($i === 40) {
                        $i = 0;
                        self::parseIni($tip);
                    }

                    self::less();
                    flush();
                    usleep(300000);
                    ++$i;
                }

                break;
            }
        }
    }
}