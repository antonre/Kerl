<?php
/*
 * © Aleksej Martirosyan 2012
 */

require 'Kerl.core.php';

Kerl::$_CSSJOIN_INPUT_FILES_BASE_PATH_ = '/var/www/server/Kerl/css/';

Kerl::$_CSSJOIN_OUTPUT_FILES_BASE_PATH_ = '/var/www/server/Kerl/css/';

Kerl::$_CSSJOIN_INPUT_FILES_ = array(
    'inputLogin.css' => 'login.css',
    '2.css' => 'input2.css'
);

Kerl::startCssJoin();