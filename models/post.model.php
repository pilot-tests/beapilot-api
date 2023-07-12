<?php

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


    static public function getTestPrompt($userId, $testId) {

  $link = Connection::connect();

  $sql = "SELECT a.id_question_answer, a.istrue_answer, sa.id_answer_student_answer, q.string_question, c.name_category, t.final_note
    FROM student_answers sa
    INNER JOIN answers a ON sa.id_answer_student_answer = a.id_answer
    INNER JOIN questions q ON a.id_question_answer = q.id_question
    INNER JOIN test t ON sa.id_test_student_answer = t.id_test
    INNER JOIN categories c ON t.id_category_test = c.id_category
    WHERE sa.id_user_student_answer = $userId AND sa.id_test_student_answer = $testId";

  $stmt = $link->prepare($sql);
  $stmt->execute();

  // Primera extracción
  if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $details = 'Te voy a hacer una consulta pero es importante que sigamos dos pautas:
      - Háblame siempre de TU. No usted ni en tercera persona
      - La respuesta debe ser en código HTML (Sólo necesito que uses la etqueta <p> y si hace falta <b> o <a> con su target="_blank").

      Voy con la pregunta:
      Estoy sacándome el carnet de piloto TPL. Este es mi resultado:' . $row['final_note'] . ' sobre 100.
      ¿Podrías darme un análisis del test?, así como un análisis y estadísticas de las áreas que he fallado.

      También me vendría bien bibliografía o links (sólo uno o dos) que puedas recomendar para mejorar en las áreas en las que he fallado.
      Pongo las preguntas en las que fallé y las que acerté:';
    $countCorrect = 0; // Contador para las respuestas correctas
    $countWrong = 0; // Contador para las respuestas incorrectas
    do {
      if($row['istrue_answer']){
        $countCorrect++;
        $details .= "\n" . $countCorrect . ". Acerté la pregunta: " . $row['string_question'];
      } else {
        $countWrong++;
        $details .= "\n" . $countWrong . ". Fallé en la pregunta: " . $row['string_question'];
      }
    } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));

    return $details;
  } else {
    // Si no hay ninguna fila, se puede manejar la situación aquí, por ejemplo, retornando un mensaje de error
    return "No se encontraron resultados.";
  }
}





static public function getGlobalPrompt($userId) {
  $link = Connection::connect();

  $sql = "SELECT c.name_category, AVG(t.final_note) AS average_note, COUNT(sa.id_answer_student_answer) AS total_questions,
        SUM(a.istrue_answer) AS total_correct
        FROM student_answers sa
        INNER JOIN answers a ON sa.id_answer_student_answer = a.id_answer
        INNER JOIN test t ON sa.id_test_student_answer = t.id_test
        INNER JOIN categories c ON t.id_category_test = c.id_category
        WHERE sa.id_user_student_answer = $userId
        GROUP BY c.name_category";

  $stmt = $link->prepare($sql);
  $stmt->execute();

  // Primera extracción
  if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $details = 'Estoy sacándome el carnet de piloto TPL. Aquí está mi rendimiento por categoría:';
    do {
      $details .= "\n En la categoría " . $row['name_category'] . ", respondí correctamente a "
                  . $row['total_correct'] . " de " . $row['total_questions'] . " preguntas.";
    } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
    $details .= "\n ¿Podrías darme un análisis global de mi rendimiento y sugerencias sobre las áreas en las que debería concentrarme más?";
    return $details;
  } else {
    // Si no hay ninguna fila, se puede manejar la situación aquí, por ejemplo, retornando un mensaje de error
    return "No se encontraron resultados.";
  }
}





    static public function getAnswerFromOpenAI($prompt) {
      set_time_limit(0);
      $yourApiKey = $_ENV['OPENAI_API_KEY'];
      $client = OpenAI::client($yourApiKey);

      $result = $client->completions()->create([
          'model' => 'text-davinci-003',
          'prompt' => $prompt,
          'max_tokens' => 2000, // Ajusta este valor a la cantidad de tokens que necesites
      ]);

      return  $result['choices'][0]['text'];
    }

    public static function createCheckoutSession( $customer_id) {
      \Stripe\Stripe::setApiKey($_ENV['STRIPE_KEY']);
      $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
          'price' => $_ENV['STRIPE_SUBSCRIPTION_PLAN_ID'],
          'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => $_ENV['FRONTEND_URL'] . 'register/success/{CHECKOUT_SESSION_ID}',
        'cancel_url' => $_ENV['FRONTEND_URL'] . 'register/cancel',
        'customer' => $customer_id
      ]);

      return $checkout_session['id'];
    }





  static public function storePromptResult($prompt, $type, $userId, $testId, $testResponseOpenAi, $globalResponseOpenAi, $responseTimeTest, $responseTimeGlobal) {
    try {
      // Aquí deberías abrir una conexión a tu base de datos
      $link = Connection::connect();

      // Inicia una transacción
      $link->beginTransaction();

      // Prepara la consulta SQL para actualizar la tabla 'test'
      $updateTestSql = 'UPDATE test SET finished_test = 1 WHERE id_test = :testId';
      $updateTestStmt = $link->prepare($updateTestSql);
      $updateTestStmt->execute([':testId' => $testId]);

      // Prepara la consulta SQL para insertar en la tabla 'openai' el resultado del test
      $insertOpenAiSql = "INSERT INTO openai (id_user_openai, id_test_openai, type_openai, response_openai, response_time) VALUES (:id_user_openai, :id_test_openai, :type_openai, :response_openai, :response_time)";

      $insertOpenAiStmt = $link->prepare($insertOpenAiSql);
      $insertOpenAiStmt->execute([':id_user_openai' => $userId, ':id_test_openai' => $testId, ':type_openai' => 1, ':response_openai' => $testResponseOpenAi, ':response_time' => $responseTimeTest]);

      // Prepara la consulta SQL para insertar en la tabla 'openai' el resultado global
      $insertOpenAiGlobalSql = "INSERT INTO openai (id_user_openai, type_openai, response_openai, response_time) VALUES (:id_user_openai, :type_openai, :response_openai, :response_time)";
      $insertOpenAiGlobalStmt = $link->prepare($insertOpenAiGlobalSql);
      $insertOpenAiGlobalStmt->execute([':id_user_openai' => $userId, ':type_openai' => 2, ':response_openai' => $globalResponseOpenAi, ':response_time' => $responseTimeGlobal]);

      // Confirma la transacción
      $link->commit();

      $response = array(
        "comment" => "Success data entry"
      );
      return $response;

    } catch (Exception $e) {
      // Si hay un error, revierte la transacción
      $link->rollBack();
      return $link->errorInfo();
    }
  }







  public function createOrUpdateUser($authId, $authEmail) {

    $link = Connection::connect();
    $userId = $this->userExists($authId);

    if ($userId === false) {
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
    } else {
      return $userId;
    }
  }

    public function userExists($auth0_user_id)
    {
      $link = Connection::connect();

      $sql = "SELECT id_user FROM users WHERE auth0_user_id = ? LIMIT 0,1";

      $stmt = $link->prepare($sql);

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