<?php

  require_once "controllers/get.controller.php";

  $table = explode("?", $routesArray[1])[0];

  $select = $_GET["select"] ?? "*";
  $orderBy = $_GET["orderBy"] ?? null;
  $orderMode = $_GET["orderMode"] ?? null;
  $startAt = $_GET["startAt"] ?? null;
  $endAt = $_GET["endAt"] ?? null;
  $userID = $_GET["userID"] ?? null;

  $response = new GetController();

//----> Get users exams

if(isset($_GET["userID"])) {
  $response -> getUserExams($_GET["userID"]);
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


else {
  //-----> Request WITHOUT filter
  $response -> getData($table, $select, $orderBy, $orderMode, $startAt, $endAt);
}

