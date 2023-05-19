<?php

require_once "models/connection.php";

if(isset($_GET["id"]) && isset($_GET["testId"])) {
  $data = array();

  file_get_contents('php://input');
  parse_str(file_get_contents('php://input'), $data);


  //----> Separate properties into an array
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

}