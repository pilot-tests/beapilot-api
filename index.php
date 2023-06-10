<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Auth");
header("Access-Control-Allow-Methods: PUT, POST, GET, OPTIONS");
header('Content-Type: application/x-www-form-urlencoded; charset=utf-8');


if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

//-----> Show Errors
ini_set('display_errors', 1);
ini_set('show_errors', 1);
ini_set('error_log', 'C:/Users/wakko/Documents/beapilot-api/php_errors_log');


//-----> Requirements

require_once "controllers/routes.controller.php";


$index = new RoutesController();
$index -> index();
