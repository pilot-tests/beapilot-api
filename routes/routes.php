
<?php

$routesArray = explode('/', $_SERVER['REQUEST_URI']);
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

  //-----> Get Request Response
  if($_SERVER['REQUEST_METHOD'] == "GET") {
    include "services/get.php";
  }

  //-----> POST Request Response
  if($_SERVER['REQUEST_METHOD'] == "POST") {
    $json = array(
    'status' => 200,
    'result' => 'POST Request'
    );
    echo json_encode($json, http_response_code($json["status"]));
  }

  //-----> PUT Request Response
  if($_SERVER['REQUEST_METHOD'] == "PUT") {
    $json = array(
    'status' => 200,
    'result' => 'PUT Request'
    );
    echo json_encode($json, http_response_code($json["status"]));
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