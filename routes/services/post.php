<?php
require_once "models/connection.php";
require_once "controllers/post.controller.php";


  if(isset($_POST["newTest"])) {
    $response = new PostController();
    $response -> postNewTest($_POST);
  }


// elseif(isset($_POST["prompt"])) {
//   $controller = new PostController();
//   $response = $controller->getAnswer($prompt);

//   echo $response;
// }

  //----> openAI
elseif(isset($_POST["prompt"]) && isset($_POST["type"])) {
    $controller = new PostController();
    $response = $controller->getAndStoreAnswer($_POST["prompt"], $_POST["type"], $_POST["userId"], $_POST["testId"]);
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