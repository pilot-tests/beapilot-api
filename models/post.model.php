<?php
require_once "connection.php";
  class PostModel {

    static public function postNewTest($addTest) {
      $sql = "BEGIN;
              INSERT INTO test (id_category_test, id_user_test)
                VALUES($addTest[id_category_test], $addTest[id_user_test]);
              INSERT INTO questionintests (id_test_questionintest, id_question_questionintest, id_user_questionintest)
                SELECT LAST_INSERT_ID(), id_question, $addTest[id_user_test] FROM beapilot.questions WHERE id_category_question = $addTest[id_category_test] ORDER BY RAND() LIMIT 20;
              COMMIT;
              ";
      echo '<pre>'; print_r($sql); echo '</pre>';
       $stmt = Connection::connect()->prepare($sql);

      $stmt -> execute();

      return $stmt -> fetchAll(PDO::FETCH_CLASS);
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