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

        // Retrieve the limit for the second query
        $sqlLimit = "SELECT numberquestions_category FROM categories WHERE id_category = :id_category_test";
        $stmtLimit = $link->prepare($sqlLimit);
        $stmtLimit->execute([':id_category_test' => $addTest['id_category_test']]);
        $limit = $stmtLimit->fetchColumn();

        // Second query
        $sql2 = "INSERT INTO questionintests (id_test_questionintest, id_question_questionintest, id_user_questionintest)
        SELECT :last_insert_id, id_question, :id_user_test FROM beapilot.questions WHERE id_category_question = :id_category_test ORDER BY RAND() LIMIT $limit";
        $stmt2 = $link->prepare($sql2);
        $stmt2->execute([':last_insert_id' => $lastInsertId, ':id_category_test' => $addTest['id_category_test'], ':id_user_test' => $addTest['id_user_test']]);

        // Get the questions for the newly created test
        $sql3 = "SELECT id_question_questionintest FROM questionintests WHERE id_test_questionintest = :last_insert_id";
        $stmt3 = $link->prepare($sql3);
        $stmt3->execute([':last_insert_id' => $lastInsertId]);
        $questions = $stmt3->fetchAll(PDO::FETCH_COLUMN);

        $letters = ['A', 'B', 'C', 'D'];
        // For each question, get the answers and insert them in questionintests_order with a random order
        foreach ($questions as $question) {
          $sql4 = "SELECT id_answer FROM answers WHERE id_question_answer = :question";
          $stmt4 = $link->prepare($sql4);
          $stmt4->execute([':question' => $question]);
          $answers = $stmt4->fetchAll(PDO::FETCH_COLUMN);

          try {
            foreach ($answers as $index => $answer) {

              $letter = $letters[$index];

              $sql5 = "INSERT INTO questionintests_order (id_test, id_question, id_answer, answer_order) VALUES (:id_test, :id_question, :id_answer, :answer_order)";
              $stmt5 = $link->prepare($sql5);
              $stmt5->execute([':id_test' => $lastInsertId, ':id_question' => $question, ':id_answer' => $answer, ':answer_order' => $letter]);

            }
          } catch(Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
          }
        }



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

      $sql = "SELECT a.id_question_answer, a.istrue_answer, sa.id_answer_student_answer, q.string_question, c.name_category, c.testtime_category, t.final_note, t.creationdate_test, t.updatedate_test, c.numberquestions_category
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
       $details = '<p><b>Contexto:</b> Imagina que eres un instructor de vuelo experimentado. Yo soy tu alumno y he completado un examen. Mi objetivo es obtener el permiso PPL.</p>';
       // Información sobre el examen
        $details .= "<p><b>Detalles del Examen:</b> Se aprueba con un 75% de la nota y mi porcentaje fue de {$row['final_note']}%. Tenía un total de {$row['numberquestions_category']} preguntas. El tiempo asignado para el examen era de <b>{$row['testtime_category']}</b> minutos.</p>";

        // Tiempo tomado para el examen
        $timeTaken = strtotime($row['updatedate_test']) - strtotime($row['creationdate_test']);
        $minutesTaken = round($timeTaken / 60);
        $details .= "<p><b>Tiempo Empleado:</b> Tomé <b>{$minutesTaken}</b> minutos para completarlo. ¿Qué opinas del tiempo que empleé en relación al tiempo asignado?</p>";

        // Resultado
        $details .= "<p><b>Resultado:</b> Mi nota final es <b>{$row['final_note']} / 100</b>. ¿Cumplí con el requisito del 75%?</p>";

        $countCorrect = 0;
        $countWrong = 0;
        $questionsDetails = "<p><b>Análisis de Respuestas:</b></p>";
        do {
          if ($row['istrue_answer']) {
              $countCorrect++;
              $questionsDetails .= "<p><b>Correcta $countCorrect:</b> {$row['string_question']}</p>";
          } else {
              $countWrong++;
              $questionsDetails .= "<p><b>Incorrecta $countWrong:</b> {$row['string_question']} ¿Puedes darme retroalimentación sobre esta pregunta en particular y cómo podría haberla abordado mejor?</p>";
          }
        } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));

        $details .= $questionsDetails;

        $details .= "<p>Basado en mi desempeño y los detalles proporcionados, ¿qué recomendaciones me darías? ¿Hay áreas específicas en las que necesite mejorar? ¿Tienes recursos o consejos adicionales que me puedan ayudar a prepararme mejor la próxima vez? Necesito que toda la respuesta sea en formato HTML, usa sólo las etiquetas <a>, <li>, <ul>, <b> o cualquier etiqueta de texto apra formatear la respuesta,</p>";

        return $details;


      } else {
        // Si no hay ninguna fila, se puede manejar la situación aquí, por ejemplo, retornando un mensaje de error
        return "No se encontraron resultados.";
      }
    }





    static public function getGlobalPrompt($userId) {
      $link = Connection::connect();

      // 1. Rendimiento General del Estudiante
      $sql_general = "SELECT COUNT(id_test) AS total_tests, AVG(final_note) AS average_score FROM test WHERE id_user_test = $userId AND finished_test = 1";
      $result_general = $link->query($sql_general)->fetch(PDO::FETCH_ASSOC);

      // 2. Rendimiento por Categoría
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

      // 3. Preguntas problemáticas
      $sql_problemas = "SELECT id_question_student_answer, COUNT(id_student_answer) AS errors_count
                        FROM student_answers
                        WHERE id_user_student_answer = $userId
                        GROUP BY id_question_student_answer
                        HAVING errors_count > 1
                        ORDER BY errors_count DESC LIMIT 5";
      $result_problemas = $link->query($sql_problemas)->fetchAll(PDO::FETCH_ASSOC);

      // Construyendo el mensaje para la IA
      $details = "Contexto: Estoy trabajando para obtener el carnet de piloto TPL y he tomado varios tests para evaluar mis habilidades y conocimientos en diferentes áreas.";

      // Rendimiento General
      $details .= "\n\nRendimiento General:\n";
      $details .= "Tests Tomados: {$result_general['total_tests']}\n";
      $details .= "Nota Media: {$result_general['average_score']} / 100\n";

      // Rendimiento por Categoría
      if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $details .= "\n\nRendimiento por Categoría:";

          do {
              $percentageCorrect = round(($row['total_correct'] / $row['total_questions']) * 100, 2);
              $details .= "\n\nCategoría: {$row['name_category']}\n";
              $details .= "Preguntas Correctas: {$row['total_correct']} / {$row['total_questions']} ({$percentageCorrect}% correcto).\n";
              $details .= "¿Cómo se compara este porcentaje con el promedio necesario para aprobar en esta categoría? ¿En qué áreas específicas de esta categoría debería concentrarme más?";
          } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
      }

      // Preguntas Problemáticas
      if (!empty($result_problemas)) {
          $details .= "\n\nPreguntas Problemáticas:";
          foreach ($result_problemas as $row) {
              $details .= "\n\nPregunta ID: {$row['id_question_student_answer']}, Errores: {$row['errors_count']}\n";
          }
      }

      $details .= "\n\nBasándote en mi rendimiento general y específico, ¿qué áreas son mis puntos fuertes y cuáles son mis áreas de mejora? ¿Tienes recomendaciones específicas o recursos que puedan ayudarme a mejorar en las áreas donde mi rendimiento es más bajo? Tu respuesta debe estar en etiquetas HTML, no uses div ni HTML ni body, sólo etiquetas para formatear el texto.";

      return $details;
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
      $insertOpenAiGlobalSql = "INSERT INTO openai (id_user_openai, id_test_openai, type_openai, response_openai, response_time)
        VALUES (:id_user_openai, :id_test_openai, :type_openai, :response_openai, :response_time)
        ON DUPLICATE KEY UPDATE response_openai = :response_openai";
      $insertOpenAiGlobalStmt = $link->prepare($insertOpenAiGlobalSql);
      $insertOpenAiGlobalStmt->execute([':id_user_openai' => $userId, ':id_test_openai' => $testId, ':type_openai' => 2, ':response_openai' => $globalResponseOpenAi, ':response_time' => $responseTimeGlobal]);

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




  static public function cancelSubscription($customerNumber) {
    try {
      $subscriptions = \Stripe\Subscription::all(['customer' => $customerNumber]);
      if (count($subscriptions->data) > 0) {
        $subscriptionId = $subscriptions->data[0]->id;

        $canceledSubscription = \Stripe\Subscription::retrieve($subscriptionId);
        $canceledSubscription->cancel();
        return [
          'status' => 'success',
          'message' => 'La suscripción ha sido cancelada exitosamente.',
          'data' => $canceledSubscription
        ];
      } else {
        return [
          'status' => 'error',
          'message' => 'No se encontró una suscripción para este cliente.'
        ];
      }
    } catch (\Stripe\Exception\StripeException $e) {
    // Algo salió mal con la solicitud a Stripe
      return [
        'status' => 'error',
        'message' => $e->getMessage()
      ];
    } catch (Exception $e) {
      // Alguna otra cosa salió mal
      return [
        'status' => 'error',
        'message' => 'Hubo un error al procesar la solicitud.'
      ];
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