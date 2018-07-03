<?php

$_halka_file = '../src/halka.php';

define('HALKA_BASEDIR', __DIR__);
define('HALKA_FRONTSCRIPT', basename(__FILE__));

require_once $_halka_file;

start_halka();
