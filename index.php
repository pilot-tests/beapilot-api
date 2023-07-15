<?php
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: https://beapilot-q2lyc.ondigitalocean.app");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Auth, Token");
    header("Access-Control-Allow-Methods: PUT, POST, GET, HEAD");
    header('Content-Type: application/x-www-form-urlencoded; charset=utf-8');
    exit;
}

require_once "vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

//-----> Show Errors
ini_set('display_errors', 1);
ini_set('show_errors', 1);
ini_set('error_log', './logs/php_errors_log');


//-----> Requirements

require_once "controllers/routes.controller.php";

$index = new RoutesController();
$index -> index();
