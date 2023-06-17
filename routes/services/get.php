<?php
require_once "models/connection.php";
require_once "controllers/get.controller.php";

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
// So, we can now safely handle the other GET actions.

$response = new GetController();
$select = $_GET["select"] ?? "*";
$orderBy = $_GET["orderBy"] ?? null;
$orderMode = $_GET["orderMode"] ?? null;
$startAt = $_GET["startAt"] ?? null;
$endAt = $_GET["endAt"] ?? null;
$userID = $_GET["userID"] ?? null;

//----> Get users exams

if(isset($_GET["userID"])) {
  $response -> getUserExams($_GET["userID"]);
}

//----> Get Exam by ID

else if(isset($_GET["examId"])) {
  $response -> getExam($_GET["examId"]);
}

//-----> Request with filter
else if(isset($_GET["linkTo"]) && isset($_GET["equalTo"]) && !isset($_GET["rel"]) && !isset($_GET["type"])) {
  $response -> getDataFilter($table, $select, $_GET["linkTo"], $_GET["equalTo"], $orderBy, $orderMode, $startAt, $endAt);
}

// Get

//-----> Get Requests WITHOUT filter among RELATED TABLES
else if(isset($_GET["rel"]) && isset($_GET["type"]) && $table == "relations" && !isset($_GET["linkTo"]) && !isset($_GET["equalTo"])) {
  $response -> getRelData($_GET["rel"], $_GET["type"], $select, $orderBy, $orderMode, $startAt, $endAt);
}

//-----> Get Requests WITH filters among RELATED TABLES
else if(isset($_GET["rel"]) && isset($_GET["type"]) && $table == "relations" && isset($_GET["linkTo"]) && isset($_GET["equalTo"])) {
  $response -> getRelDataFilter($_GET["rel"], $_GET["type"], $select, $_GET["linkTo"], $_GET["equalTo"], $orderBy, $orderMode, $startAt, $endAt);
}

//-----> Get Requests to get AVG user tests by category
else if($table == "averageByCategory" && isset($_GET["userId"])) {
  $response -> getAverageByCategory($_GET["userId"]);
}


else {
  //-----> Request WITHOUT filter
  $response -> getData($table, $select, $orderBy, $orderMode, $startAt, $endAt);
}
