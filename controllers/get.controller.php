<?php

  require_once "models/get.model.php";

  class GetController {


    static public function getUserExams($userId) {
      $response = GetModel::getUserExams($userId);

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
          'total' => count($response),
          'results' => "Not Found"
        );
      }

      echo json_encode($json, http_response_code($json["status"]));
    }
  }