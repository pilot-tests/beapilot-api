<?php

// $yourApiKey = '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';


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

    static public function getAnswerFromOpenAI($prompt) {
      require_once __DIR__.'/../vendor/autoload.php'; // Asegúrate de que este camino apunta a tu archivo autoload.php generado por Composer


      $yourApiKey = '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';
      $client = OpenAI::client($yourApiKey);

      $result = $client->completions()->create([
          'model' => 'text-davinci-003',
          'prompt' => $prompt,
          'max_tokens' => 200, // Ajusta este valor a la cantidad de tokens que necesites
      ]);

      return  $result['choices'][0]['text'];
    }

    static public function storePromptResult($prompt, $type, $userId, $testId, $responseOpenAi) {

      // Aquí deberías abrir una conexión a tu base de datos
      $link = Connection::connect();
      // Prepara la consulta SQL
      $sql = "INSERT INTO openai (id_user_openai, id_test_openai, type_openai, response_openai) VALUES (:id_user_openai, :id_test_openai, :type_openai, :response_openai)";

      // Prepara y ejecuta la consulta SQL
      $stmt = $link->prepare($sql);

      if($stmt->execute([':id_user_openai' => $userId, ':id_test_openai' => $testId, ':type_openai' => $type, ':response_openai' => $responseOpenAi])) {
        $response = array(
          "lastId" => $link->lastInsertId(),
          "comment" => "Sucess data entry"
        );
        return $response;
      }else {
        return $link->errorInfo();
      }
    }





  public static function createOrUpdateUser($authId, $authEmail) {

    $link = Connection::connect();

    $sql = 'INSERT INTO users (auth0_user_id, email_user)
    VALUES (:id_auth0, :email_user)
            ON DUPLICATE KEY UPDATE email_user = :email_user';

    $stmt = $link->prepare($sql);

    if($stmt->execute([':id_auth0' => $authId, ':email_user' => $authEmail])) {
        $response = array(
          "lastId" => $link->lastInsertId(),
          "comment" => "User added successful =D"
        );
        return $response;
      }else {
        return $link->errorInfo();
      }
    }




    public function userExists($auth0_user_id)
    {
      $query = "SELECT id_user FROM users WHERE auth0_user_id = ? LIMIT 0,1";
m
      $stmt = $this->conn->prepare($query);

      $stmt->bindParam(1, $auth0_user_id);

      $stmt->execute();

      $num = $stmt->rowCount();

      if($num > 0){
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          return $row['id_user'];
      }

      return false;
    }

  }