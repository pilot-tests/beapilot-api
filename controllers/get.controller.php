<?php

  require_once "models/get.model.php";
  require_once "vendor/autoload.php";
  use Firebase\JWT\JWT;
  use \Firebase\JWT\Key;

  class GetController {

        //-----> Verify Email
    public static function verifyEmail($token) {
      $response = GetModel::verifyEmail($token);

      $return = new GetController();
      $return -> fncResponse($response, "verifyEmail");
    }

    static public function getUserExams($userId) {
      $response = GetModel::getUserExams($userId);

      $return = new GetController();
      $return -> fncResponse($response, "getUserExams");
    }

    static public function getExam($examId) {
      $questions = GetModel::getExam($examId);
      $examDetails = GetModel::getFinishedTestData($examId);

      if(empty($examDetails)) {
        $examDetails = GetModel::getRelDataFilter('test,categories', 'id_category_test, id_category', '*', 'id_test', $examId, null, null, null, null);
      }

      $currentTime = date('Y-m-d H:i:s');
      if (isset($examDetails[0])) {
          $examDetails[0]->serverTime = $currentTime;
      }


      $response = array(
        'examDetails' => $examDetails,
        'questions' => $questions
      );

      $return = new GetController();
      $return -> fncResponse($response, "getExam");
    }

    //-----> Get test data
    static public function getTestResult($examId, $userId) {
      $response = GetModel::getTestResult($examId, $userId);

      $return = new GetController();
      $return -> fncResponse($response, "getTestResult");
    }


    //-----> Get request
    static public function getData($table, $select, $orderBy, $orderMode, $startAt, $endAt) {
      $response = GetModel::getData($table, $select, $orderBy, $orderMode, $startAt, $endAt);

      $return = new GetController();
      $return -> fncResponse($response, "getData");
    }

    //-----> Get request, with Filter
    static public function getDataFilter($table, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt) {
      $response = GetModel::getDataFilter($table, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt);

      $return = new GetController();
      $return -> fncResponse($response, "getDataFilter");
    }

    //-----> Get Requests WITHOUT filter among RELATED TABLES
    static public function getRelData($rel, $type, $select, $orderBy, $orderMode, $startAt, $endAt) {
      $response = GetModel::getRelData($rel, $type, $select, $orderBy, $orderMode, $startAt, $endAt);

      $return = new GetController();
      $return -> fncResponse($response, "getRelData");
    }

    //-----> Get Requests WITH filter among RELATED TABLES
    static public function getRelDataFilter($rel, $type, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt) {
      $response = GetModel::getRelDataFilter($rel, $type, $select ,$linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt);


      $return = new GetController();
      $return -> fncResponse($response, "getRelDataFilter");
    }

    //-----> Get Requests to get AVG user tests by category
    static public function getAverageByCategory($userId) {
      $response = GetModel::getAverageByCategory($userId);


      $return = new GetController();
      $return -> fncResponse($response, "getAverageByCategory");
    }

    //-----> Controller response
    public function fncResponse($response, $endpoint) {
      if(!empty($response)) {
        if($endpoint === "getExam") {
          $json = array(
            'status' => 200,
            'total' => count($response['questions']),
            'endpoint' => $endpoint,
            'examDetails' => $response['examDetails'],
            'results' => $response['questions']
          );
        }
        else {
          $json = array(
          'status' => 200,
          'total' => count($response),
          'endpoint' => $endpoint,
          'results' => $response
          );
        }
      }
      else {
        $json = array(
          'status' => 404,
          'total' => count((is_countable($response)?$response:[])),
          'endpoint' => $endpoint,
          'results' => "Not Found"
        );
      }

      echo json_encode($json, http_response_code($json["status"]));
    }
  }