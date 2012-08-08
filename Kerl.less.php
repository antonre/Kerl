<?php
/*
 * © Aleksej Martirosyan 2012
 */

require 'Kerl.core.php';

Kerl::$_LESS_DIR_INPUT_FILES_ = '/var/www/server/Kerl/css/';
Kerl::$_LESS_DIR_OUTPUT_FILES_ = '/var/www/server/Kerl/css/';
Kerl::$_LESS_COMPRESS_ = true;

Kerl::startLess();