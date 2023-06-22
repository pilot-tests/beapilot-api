<?php

  require_once "models/get.model.php";
  require_once "vendor/autoload.php";
  use Firebase\JWT\JWT;
  use \Firebase\JWT\Key;

  class GetController {

    //-----> Verify Email
    public static function verifyEmail($token) {
      // Verify the JWT
      try {
        $decoded = JWT::decode($token, new Key("d12sd124df3456dfw43w3fw34df", 'HS256'));

        // Get the user email from the decoded token
        $userEmail = $decoded->data->email;


        // Update the email_verified field in the database for this user
        $stmt = Connection::connect()->prepare("UPDATE users SET verified_user = 1 WHERE email_user = :email");
        $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $stmt->execute();

        //TODO: You might want to handle the case where no rows were affected (e.g. the user doesn't exist)

        echo "Email verified successfully!";
      } catch (Exception $e) {
        // The token was not valid, show an error message
        // The token was not valid, show an error message
        echo "The verification link is not valid.";
        // Print the actual exception message
        echo 'Caught exception: ',  $e->getMessage(), "\n";
      }
    }





    static public function getUserExams($userId) {
      $response = GetModel::getUserExams($userId);

      $return = new GetController();
      $return -> fncResponse($response);
    }

     static public function getExam($examId) {
      $response = GetModel::getExam($examId);

      $return = new GetController();
      $return -> fncResponse($response);
    }


    //-----> Get request
    static public function getData($table, $select, $orderBy, $orderMode, $startAt, $endAt) {
      $response = GetModel::getData($table, $select, $orderBy, $orderMode, $startAt, $endAt);

      $return = new GetController();
      $return -> fncResponse($response);
    }

    //-----> Get request, with Filter
    static public function getDataFilter($table, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt) {
      $response = GetModel::getDataFilter($table, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt);

      $return = new GetController();
      $return -> fncResponse($response);
    }

    //-----> Get Requests WITHOUT filter among RELATED TABLES
    static public function getRelData($rel, $type, $select, $orderBy, $orderMode, $startAt, $endAt) {
      $response = GetModel::getRelData($rel, $type, $select, $orderBy, $orderMode, $startAt, $endAt);

      $return = new GetController();
      $return -> fncResponse($response);
    }

    //-----> Get Requests WITH filter among RELATED TABLES
    static public function getRelDataFilter($rel, $type, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt) {
      $response = GetModel::getRelDataFilter($rel, $type, $select ,$linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt);


      $return = new GetController();
      $return -> fncResponse($response);
    }

    //-----> Get Requests to get AVG user tests by category
    static public function getAverageByCategory($userId) {
      $response = GetModel::getAverageByCategory($userId);


      $return = new GetController();
      $return -> fncResponse($response);
    }

    //-----> Controller response
    public function fncResponse($response) {
      if(!empty($response)) {
        $json = array(
          'status' => 200,
          'total' => count($response),
          'results' => $response
        );
      }
      else {
        $json = array(
          'status' => 404,
          'total' => count((is_countable($response)?$response:[])),
          'results' => "Not Found"
        );
      }

      echo json_encode($json, http_response_code($json["status"]));
    }
  }