<?php
require_once "models/connection.php";
require_once "controllers/post.controller.php";


  //-----> Insert new test
  if(isset($_POST)) {
    $columns = array();
    foreach (array_keys($_POST) as $key => $value) {
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

    //-----> Ask a response from controller to insert data on any table
    $response = new PostController();
    $response -> postData($table, $_POST);
  }