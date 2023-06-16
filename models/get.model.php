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
                SELECT
                  t1.*
                FROM
                  test t1
                WHERE
                  t1.id_user_test = $userId
                  AND
                  t1.id_test = (
                    SELECT
                      t2.id_test
                    FROM
                      test t2
                    WHERE
                      t2.id_user_test = t1.id_user_test
                      AND
                      t2.id_category_test = t1.id_category_test
                    ORDER BY
                      t2.id_test DESC
                    LIMIT 1
                  )
              ) t
              ON c.id_category = t.id_category_test
              ORDER BY
                c.id_category
              ";
      $stmt = Connection::connect()->prepare($sql);
      $stmt -> execute();
      return $stmt -> fetchAll(PDO::FETCH_CLASS);
    }

    //-----> Get exam (Questions and answers)
  static public function getExam($examId) {
    $sql = "SELECT
      q.*,
      qt.id_test_questionintest,
      MAX(CASE WHEN a.answer_number = 1 THEN a.id_answer END) AS answer_1_id,
      MAX(CASE WHEN a.answer_number = 1 THEN a.string_answer END) AS answer_1_string,
      MAX(CASE WHEN a.answer_number = 2 THEN a.id_answer END) AS answer_2_id,
      MAX(CASE WHEN a.answer_number = 2 THEN a.string_answer END) AS answer_2_string,
      MAX(CASE WHEN a.answer_number = 3 THEN a.id_answer END) AS answer_3_id,
      MAX(CASE WHEN a.answer_number = 3 THEN a.string_answer END) AS answer_3_string,
      MAX(CASE WHEN a.answer_number = 4 THEN a.id_answer END) AS answer_4_id,
      MAX(CASE WHEN a.answer_number = 4 THEN a.string_answer END) AS answer_4_string,
      MAX(sa.id_test_student_answer) AS id_test_student_answer
    FROM
      questions q
      INNER JOIN questionintests qt ON q.id_question = qt.id_question_questionintest
      INNER JOIN (
        SELECT
          a.*,
          @rn := IF(@prev_q = a.id_question_answer, @rn + 1, 1) AS answer_number,
          @prev_q := a.id_question_answer
        FROM
          answers a,
          (SELECT @prev_q := NULL, @rn := 0) vars
        ORDER BY
          a.id_question_answer, a.id_answer
      ) a ON q.id_question = a.id_question_answer
      LEFT JOIN student_answers sa ON q.id_question = sa.id_question_student_answer
    WHERE
      qt.id_test_questionintest = $examId
    GROUP BY
      q.id_question";
   $stmt = Connection::connect()->prepare($sql);

    $stmt -> execute();

    return $stmt -> fetchAll(PDO::FETCH_CLASS);
  }

    //-----> Get Request, no filter
    static public function getData($table, $select, $orderBy, $orderMode, $startAt, $endAt) {

      //-----> Validate that table and columns exists

      $selectArray = explode(",",$select);

      if(empty(Connection::getColumnsData($table, $selectArray))){
        return null;
      }

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

      // if(empty(Connection::getColumnsData($table))){
      //   return null;
      // }

      $linkToArray = explode(",",$linkTo);
      $equalToArray =  explode(",",$equalTo);
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
          //  //-----> Validate that table exists
          // if(empty(Connection::getColumnsData($value))){
          //   return null;
          // }
          if($key > 0) {
            $innerJoinText .= "INNER JOIN ".$value." ON ".$relArray[0].".".$typeArray[0] ." = ".$value.".".$typeArray[$key]." ";
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
      else {
        return null;
      }
    }


    //-----> Get Request, WITH filter among related tables
    static public function getRelDataFilter($rel, $type, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt) {

      //-----> Organise filters
      $linkToArray = explode(",",$linkTo);
      $equalToArray =  explode(",",$equalTo);
      $linkToText = "";

      if(count($linkToArray)>1) {
        foreach ($linkToArray as $key => $value) {
          //  //-----> Validate that table exists
          // if(empty(Connection::getColumnsData($value))){
          //   return null;
          // }
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
            $innerJoinText .= "INNER JOIN ".$value." ON ".$relArray[0].".".$typeArray[0]." = ".$value.".".$typeArray[$key]." ";
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