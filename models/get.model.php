<?php

  require_once "connection.php";

  class GetModel {

    //-----> Get categories and the user's exams on that category
    //-----> Exams created by user
    static public function getUserExams($userId) {
      $sql = "SELECT
              *
            FROM
              categories c
            LEFT JOIN (
                SELECT * FROM test where id_user_test = $userId
                ) t
            ON c.id_category = t.id_category_test";
      $stmt = Connection::connect()->prepare($sql);

      $stmt -> execute();

      return $stmt -> fetchAll(PDO::FETCH_CLASS);
    }

    //-----> Get Request, no filter
    static public function getData($table, $select, $orderBy, $orderMode, $startAt, $endAt) {

      //-----> Validate that table and columns exists


      //-----> No limit, no Order query
      $sql = "SELECT $select FROM $table";

      //-----> No Limit, Order query
      if($orderBy != null && $orderMode != null && $startAt == null && $endAt == null) {
        $sql = "SELECT $select FROM $table ORDER BY $orderBy $orderMode";
      }

      //-----> Limit and Order query
      if($orderBy != null && $orderMode != null && $startAt != null && $endAt != null) {
        $sql = "SELECT $select FROM $table ORDER BY $orderBy $orderMode LIMIT $startAt, $endAt";
      }

      //-----> Limit, no Order query
      if($orderBy == null && $orderMode == null && $startAt != null && $endAt != null) {
        $sql = "SELECT $select FROM $table LIMIT $startAt, $endAt";
      }

      $stmt = Connection::connect()->prepare($sql);

      $stmt -> execute();

      return $stmt -> fetchAll(PDO::FETCH_CLASS);
    }

    //-----> Get Request with filter
    static public function getDataFilter($table, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt) {

       //-----> Validate that table exists

      if(empty(Connection::getColumnsData($table))){
        return null;
      }

      $linkToArray = explode(",",$linkTo);
      $equalToArray =  explode("_",$equalTo);
      $linkToText = "";

      if(count($linkToArray)>1) {
        foreach ($linkToArray as $key => $value) {
          if($key > 0) {
            $linkToText .= "AND ".$value." = :".$value." ";
          }
        }
      }

      //-----> Get Request, no filter
      $sql = "SELECT $select FROM $table WHERE $linkToArray[0] = :$linkToArray[0] $linkToText";

      //-----> No Limit, Order query
      if($orderBy != null && $orderMode != null && $startAt == null && $endAt == null) {
        $sql = "SELECT $select FROM $table WHERE $linkToArray[0] = :$linkToArray[0] $linkToText ORDER BY $orderBy $orderMode";
      }

      //-----> Limit and Order query
      if($orderBy != null && $orderMode != null && $startAt != null && $endAt != null) {
        $sql = "SELECT $select FROM $table WHERE $linkToArray[0] = :$linkToArray[0] $linkToText ORDER BY $orderBy $orderMode LIMIT $startAt, $endAt";
      }

      //-----> Limit, no Order query
      if($orderBy = null && $orderMode = null && $startAt != null && $endAt != null) {
        $sql = "SELECT $select FROM $table WHERE $linkToArray[0] = :$linkToArray[0] $linkToText LIMIT $startAt, $endAt";
      }

      $stmt = Connection::connect()->prepare($sql);

      foreach ($linkToArray as $key => $value) {
        $stmt -> bindParam(":".$value, $equalToArray[$key], PDO::PARAM_STR);
      }




      $stmt -> execute();

      return $stmt -> fetchAll(PDO::FETCH_CLASS);
    }


    //-----> Get Request, no filter among related tables
    static public function getRelData($rel, $type, $select, $orderBy, $orderMode, $startAt, $endAt) {


      $relArray = explode(",", $rel);
      $typeArray = explode(",", $type);

      $innerJoinText = "";

      if(count($relArray)>1) {
        foreach ($relArray as $key => $value) {
           //-----> Validate that table exists
          if(empty(Connection::getColumnsData($value))){
            return null;
          }
          if($key > 0) {
            $innerJoinText .= "INNER JOIN ".$value." ON ".$relArray[0].".".$typeArray[0] ." = ".$value.".".$typeArray[$key]." ";
          }
        }
      }

      //-----> No limit, no Order query
      $sql = "SELECT $select FROM $relArray[0] $innerJoinText";

      //-----> No Limit, Order query
      if($orderBy != null && $orderMode != null && $startAt == null && $endAt == null) {
        $sql = "SELECT $select FROM $relArray[0] $innerJoinText ORDER BY $orderBy $orderMode";
      }

      //-----> Limit and Order query
      if($orderBy != null && $orderMode != null && $startAt != null && $endAt != null) {
        $sql = "SELECT $select FROM $relArray[0] $innerJoinText ORDER BY $orderBy $orderMode LIMIT $startAt, $endAt";
      }

      //-----> Limit, no Order query
      if($orderBy == null && $orderMode == null && $startAt != null && $endAt != null) {
        $sql = "SELECT $select FROM $relArray[0] $innerJoinText LIMIT $startAt, $endAt";
      }

      $stmt = Connection::connect()->prepare($sql);

      $stmt -> execute();

      return $stmt -> fetchAll(PDO::FETCH_CLASS);
    }


    //-----> Get Request, WITH filter among related tables
    static public function getRelDataFilter($rel, $type, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt) {

      //-----> Organise filters
      $linkToArray = explode(",",$linkTo);
      $equalToArray =  explode("_",$equalTo);
      $linkToText = "";

      if(count($linkToArray)>1) {
        foreach ($linkToArray as $key => $value) {
           //-----> Validate that table exists
          if(empty(Connection::getColumnsData($value))){
            return null;
          }
          if($key > 0) {
            $linkToText .= "AND ".$value." = :".$value." ";
          }
        }
      }

      //-----> Organise relations
      $relArray = explode(",", $rel);
      $typeArray = explode(",", $type);

      $innerJoinText = "";

      if(count($relArray)>1) {
        foreach ($relArray as $key => $value) {
          if($key > 0) {
            $innerJoinText .= "INNER JOIN ".$value." ON ".$relArray[0].".".$typeArray[0] ." = ".$value.".".$typeArray[$key]." ";
          }
        }
      }

      //-----> No limit, no Order query
      $sql = "SELECT $select FROM $relArray[0] $innerJoinText WHERE $linkToArray[0] = :$linkToArray[0] $linkToText";


      //-----> No Limit, Order query
      if($orderBy != null && $orderMode != null && $startAt == null && $endAt == null) {
        $sql = "SELECT $select FROM $relArray[0] $innerJoinText WHERE $linkToArray[0] = :$linkToArray[0] $linkToText ORDER BY $orderBy $orderMode";
      }

      //-----> Limit and Order query
      if($orderBy != null && $orderMode != null && $startAt != null && $endAt != null) {
        $sql = "SELECT $select FROM $relArray[0] $innerJoinText WHERE $linkToArray[0] = :$linkToArray[0] $linkToText ORDER BY $orderBy $orderMode LIMIT $startAt, $endAt";
      }

      //-----> Limit, no Order query
      if($orderBy == null && $orderMode == null && $startAt != null && $endAt != null) {
        $sql = "SELECT $select FROM $relArray[0] $innerJoinText LIMIT $startAt, $endAt";
      }

      $stmt = Connection::connect()->prepare($sql);

      foreach ($linkToArray as $key => $value) {
        $stmt -> bindParam(":".$value, $equalToArray[$key], PDO::PARAM_STR);
      }

      $stmt -> execute();

      return $stmt -> fetchAll(PDO::FETCH_CLASS);
    }
  }