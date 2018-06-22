<?php

define('HALKA_BASEDIR', __DIR__);
define('HALKA_FRONTSCRIPT', basename(__FILE__));

require_once 'halka.php';

// debug

error_reporting(E_ALL);

$routes = $_router->get_routes();

foreach ($routes as $route){
    print_r($route);
    echo PHP_EOL;
}