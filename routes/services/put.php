<?php
require_once "models/connection.php";
require_once "controllers/put.controller.php";


//We will now handle the token verification.
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
