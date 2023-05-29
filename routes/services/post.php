<?php
require_once "models/connection.php";
require_once "controllers/post.controller.php";

$prompt = $_POST['prompt'];


  if(isset($_POST["newTest"])) {
    $response = new PostController();
    $response -> postNewTest($_POST);
  }


elseif(isset($_POST["prompt"])) {
  $controller = new PostController();
  $response = $controller->getAnswer($prompt);

  echo $response;
}



  //-----> Insert any POST
  else {
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