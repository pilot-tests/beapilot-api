<?php

//-----> Show Errors
ini_set('display_errors', 1);
ini_set('show_errors', 1);
ini_set('error_log', 'C:/Users/wakko/Documents/beapilot-api/php_errors_log');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');


//-----> Requirements

require_once "controllers/routes.controller.php";


$index = new RoutesController();
$index -> index();
