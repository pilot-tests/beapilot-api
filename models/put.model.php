<?php
require_once "connection.php";
class PutModel {


  //-----> Put Request to edit data dinamically.
  static public function putData($table, $data, $id, $nameId) {


    $set = "";

    foreach ($data as $key => $value) {
      $set .=  $key." = :".$key.",";
    }

    $set = substr($set, 0, -1);


    $sql="UPDATE $table SET $set WHERE $nameId = :$nameId";

    $link = Connection::connect();
    $stmt = $link->prepare($sql);

    foreach ($data as $key => $value) {
      $stmt->bindParam(":".$key, $data[$key], PDO::PARAM_STR);
    }

    $stmt->bindParam(":".$nameId, $id, PDO::PARAM_STR);

    if($stmt -> execute()) {
      $response = array(
        "comment" => "Edit successful"
      );
      return $response;
    }else {
      return $link->errorInfo();
    }
  }

  static public function updateFinalScore($id_test) {
    $sql = "UPDATE
        test t
        INNER JOIN (
            SELECT
                sa.id_user_student_answer,
                (SUM(COALESCE(a.istrue_answer, 0)) / COUNT(*)) * 100 AS final_score
            FROM
                student_answers sa
                INNER JOIN answers a ON sa.id_answer_student_answer = a.id_answer
            WHERE
                sa.id_test_student_answer = :id_test
            GROUP BY
                sa.id_user_student_answer
        ) scores ON t.id_user_test = scores.id_user_student_answer
    SET
        t.final_note = scores.final_score,
        t.finished_test = true
    WHERE
        t.id_test = :id_test";

    $link = Connection::connect();
    $stmt = $link->prepare($sql);

    $stmt->bindParam(":id_test", $id_test, PDO::PARAM_INT);

    if($stmt -> execute()) {
      if($stmt->rowCount() > 0) {
          $response = array(
              "comment" => "Update successful"
          );
      } else {
          $response = array(
              "comment" => "Update failed, no rows were updated"
          );
      }
    return $response;
    } else {
        return $link->errorInfo();
    }
  }

}