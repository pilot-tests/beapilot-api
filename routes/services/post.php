<?php
require_once "models/connection.php";
require_once "controllers/post.controller.php";

$response = new PostController();

// Register User
if (isset($_GET["register"]) && $_GET["register"] == true) {
    $response->postRegister($table, $_POST);
    exit;
}

// Login User
if (isset($_GET["login"]) && $_GET["login"] == true) {
    $response->postLogin($table, $_POST);
    exit;
}

// At this point, login and register have been handled and are no longer needed.
// So, we will now handle the token verification.
if (isset($_GET["token"])) {
    $validate = Connection::tokenValidation($_GET["token"]);

    if ($validate != "ok") {
        if ($validate == "expired") {
            $json = array(
                'status' => 303,
                'results' => "Error: Token has expired."
            );
        } elseif ($validate == "no-auth") {
            $json = array(
                'status' => 401,
                'results' => "Error: user not Authorized."
            );
        }
        echo json_encode($json, http_response_code($json["status"]));
        exit;
    }
} else {
    $json = array(
        'status' => 401,
        'results' => "Error: No token provided."
    );
    echo json_encode($json, http_response_code($json["status"]));
    exit;
}

// If we get to this point, we know that a valid token has been provided.
// So, we can now safely handle the other POST actions.

if (isset($_POST["newTest"])) {
    $response->postNewTest($_POST);
}

elseif (isset($_POST["prompt"]) && isset($_POST["type"])) {
    $response = $controller->getAndStoreAnswer($_POST["prompt"], $_POST["type"], $_POST["userId"], $_POST["testId"]);
}

elseif (isset($_POST["authId"]) && isset($_POST["authEmail"])) {
    $post = new PostController();
    $response = $post->createOrUpdateUser($_POST["authId"], $_POST["authEmail"]);
}

elseif (isset($_POST)) {
    // Insert any POST
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