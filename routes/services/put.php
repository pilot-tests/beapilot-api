<?php
require_once "models/connection.php";
require_once "controllers/put.controller.php";

if(isset($_GET["id"]) && isset($_GET["nameId"])) {

  //-----> Get data from parameters
  $data = array();

  //-----> We convert the form data (body of request) into an array
  parse_str(file_get_contents('php://input'), $data);


  $columns = array();
  foreach (array_keys($data) as $key => $value) {
    array_push($columns, $value);
  }

  //-----> Validate table and columns exist on DB
  if(empty(Connection::getColumnsData($table, $columns))) {
    $json = array(
      'status' => 400,
      'results' => "Error: Fields sent doesn't match Database fields"
    );
    echo json_encode($json, http_response_code($json["status"]));
    return;
  }

   //-----> Ask a response from controller to edit data on any table
    $response = new PutController();
    $response -> putData($table, $data, $_GET["id"], $_GET["nameId"] );
}

else if(isset($_GET["id_test"])) {
    $response = new PutController();
    $response -> updateFinalScore($_GET["id_test"]);
}