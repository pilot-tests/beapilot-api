<?php

include_once "models/connection.php";
echo '<pre>'; print_r($_SERVER['REQUEST_URI']); echo '</pre>';

$fullRoute = ltrim($_SERVER['REQUEST_URI'], '/');
if(isset($_SERVER['QUERY_STRING'])) {
    $fullRoute .= '?'.$_SERVER['QUERY_STRING'];
}
$routesArray = explode('/', $fullRoute);
$routesArray = array_filter($routesArray);




//-----> No API request
if(count($routesArray) == 0) {
  $json = array(
    'status' => 404,
    'result' => 'Not Found'
  );
  echo json_encode($json, http_response_code($json["status"]));
  return;
}


//-----> Some Request
if(count($routesArray) == 1 && isset($_SERVER['REQUEST_METHOD'])) {

  $table = explode("?", $routesArray[0])[0];

  // echo '<pre>'; print_r("RoutesArray enterito: " . $routesArray); echo '</pre>';
  // echo '<pre>'; print_r("routesArray 1: " . $routesArray[1]); echo '</pre>';
  // echo '<pre>'; print_r("routesArray 0: " . $routesArray[0]); echo '</pre>';
  echo '<pre>'; print_r("Tabla: " . $table); echo '</pre>';

  if(!isset(getallheaders()["Auth"]) || getallheaders()["Auth"] != Connection::apikey()) {
    $json = array(
      'status' => 401,
      'result' => 'Not Authorized'
    );
    echo json_encode($json, http_response_code($json["status"]));
    exit;
  }

  //-----> Get Request Response
  if($_SERVER['REQUEST_METHOD'] == "GET") {
    include "services/get.php";
  }

  //-----> POST Request Response
  if($_SERVER['REQUEST_METHOD'] == "POST") {
    include "services/post.php";
  }

  //-----> PUT Request Response
  if($_SERVER['REQUEST_METHOD'] == "PUT") {
    include "services/put.php";
  }

  //-----> DELETE Request Response
  if($_SERVER['REQUEST_METHOD'] == "DELETE") {
    $json = array(
    'status' => 200,
    'result' => 'DELETE Request'
    );
    echo json_encode($json, http_response_code($json["status"]));
  }
}