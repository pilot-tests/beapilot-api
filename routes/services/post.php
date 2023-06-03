<?php
require_once "models/connection.php";
require_once "controllers/post.controller.php";


  if(isset($_POST["newTest"])) {
    $response = new PostController();
    $response -> postNewTest($_POST);
  }



  //----> openAI
elseif(isset($_POST["prompt"]) && isset($_POST["type"])) {
    $controller = new PostController();
    $response = $controller->getAndStoreAnswer($_POST["prompt"], $_POST["type"], $_POST["userId"], $_POST["testId"]);
}




  //----> VerifyUser
elseif(isset($_POST["authId"]) && isset($_POST["authEmail"])) {
    $post = new PostController();
    $response = $post->createOrUpdateUser($_POST["authId"], $_POST["authEmail"]);
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