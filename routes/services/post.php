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

// We lowercase all headers inc ase server (Apache sometimes does it) converts it into Capital word
$headers = array_change_key_case(getallheaders(), CASE_LOWER);

if (isset($headers["token"])) {
    $validate = Connection::tokenValidation($headers["token"]);

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

// Suscripción de un usuario no existente
if (isset($_GET["subscribe"]) && $_GET["subscribe"] == true) {
	$response->postSubscribe($table, $_POST);
    exit;
}


if (isset($_POST)) {

	if (isset($_POST["newTest"])) {
		$response->postNewTest($table, $_POST);
		exit;
	}

	if (isset($_POST["prompt"]) && isset($_POST["type"])) {
		$controller = new PostController(); // Not ideal, since we already declares a $response = new PostController at begining of this file.
		$response = $controller->finishTest($_POST["prompt"], $_POST["type"], $_POST["userId"], $_POST["testId"]);
		exit;
	}

	elseif (isset($_POST["addCorrectAnswer"]) && $_POST["addCorrectAnswer"] == true && $table === "addCorrectAnswer") {
		$controller = new PostController();
		$response = $controller->addCorrectAnswer();
		exit;
	}

	elseif ($table === "userContact") {
		$data = json_decode(file_get_contents("php://input"));
    $name = $data->name;
    $email = $data->email;
    $message = $data->message;
		$controller = new PostController();
		$response = $controller->userContact($data, $name, $email, $message);
		exit;
	}

	elseif (isset($_POST["cancelCustomerNumber"])) {
		$controller = new PostController();
		$response = $controller->cancelSubscription($_POST["cancelCustomerNumber"]);
		exit;
	}

	// Suscripción de un usuario que ya existe

	elseif (isset($_POST["SubscribeExistingUser"])) {
		$controller = new PostController();
		$response = $controller->SubscribeExistingUser($table, $_POST);
		exit;
	}

	elseif (isset($_POST["ResubscribeCustomerNumber"])) {
		$controller = new PostController();
		$response = $controller->ResubscribeCustomerNumber($_POST["ResubscribeCustomerNumber"]);
		exit;
	}

	elseif (isset($_POST["authId"]) && isset($_POST["authEmail"])) {
		$post = new PostController();
		$response = $post->createOrUpdateUser($_POST["authId"], $_POST["authEmail"]);
		exit;
	}

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