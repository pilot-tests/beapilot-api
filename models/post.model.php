<?php
require_once "connection.php";
  class PostModel {

    static public function postNewTest($addTest) {
      $link = Connection::connect();

      // Begin transaction
      $link->beginTransaction();

      try {
        // First query
        $sql1 = "INSERT INTO test (id_category_test, id_user_test)
            VALUES(:id_category_test, :id_user_test)";
        $stmt1 = $link->prepare($sql1);
        $stmt1->execute([':id_category_test' => $addTest['id_category_test'], ':id_user_test' => $addTest['id_user_test']]);
        $lastInsertId = $link->lastInsertId();

        // Second query
        $sql2 = "INSERT INTO questionintests (id_test_questionintest, id_question_questionintest, id_user_questionintest)
            SELECT :last_insert_id, id_question, :id_user_test FROM beapilot.questions WHERE id_category_question = :id_category_test ORDER BY RAND() LIMIT 20";
        $stmt2 = $link->prepare($sql2);
        $stmt2->execute([':last_insert_id' => $lastInsertId, ':id_category_test' => $addTest['id_category_test'], ':id_user_test' => $addTest['id_user_test']]);

        // Commit transaction
        $link->commit();

          return $lastInsertId;
      } catch (PDOException $e) {
        // Rollback transaction in case of an error
        $link->rollBack();
        return false;
      }
    }
    //-----> Post Request to create data dinamically.
    static public function postData($table, $data) {

      $columns = "";
      $params = "";
      foreach ($data as $key => $value) {
        $columns .= $key.",";
        $params .= ":".$key.",";
      }

      $columns = substr($columns, 0, -1);
      $params = substr($params, 0, -1);

      $sql = "INSERT INTO $table ($columns) VALUES ($params)";

      $link = Connection::connect();
      $stmt = $link->prepare($sql);

      foreach ($data as $key => $value) {
        $stmt->bindParam(":".$key, $data[$key], PDO::PARAM_STR);
      }
      if($stmt -> execute()) {
        $response = array(
          "lastId" => $link->lastInsertId(),
          "comment" => "Sucess data entry"
        );
        return $response;
      }else {
        return $link->errorInfo();
      }
    }

  }