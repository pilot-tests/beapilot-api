<?php
  // TODO: Refactor all variables in any SQL query into params
  require_once "connection.php";
  require_once "vendor/autoload.php";
  use Firebase\JWT\JWT;
  use \Firebase\JWT\Key;
  \Stripe\Stripe::setApiKey($_ENV['STRIPE_KEY']);
  set_time_limit(0);
  class GetModel {

    static public function getTestResult($examId, $userId) {
      $sql = "SELECT
                q.id_question,
                a.id_answer AS correct_answer_id,
                sa.id_answer_student_answer AS student_answer_id,
                CASE
                    WHEN a.id_answer = sa.id_answer_student_answer THEN 1
                    ELSE 0
                END AS correct_answered
            FROM
                questionintests qt
            LEFT JOIN
                student_answers sa ON qt.id_question_questionintest = sa.id_question_student_answer AND qt.id_test_questionintest = sa.id_test_student_answer AND sa.id_user_student_answer = :user_id
            INNER JOIN
                questions q ON qt.id_question_questionintest = q.id_question
            INNER JOIN
                answers a ON q.id_question = a.id_question_answer AND a.istrue_answer = 1
            WHERE
                qt.id_user_questionintest = :user_id AND qt.id_test_questionintest = :exam_id
            ORDER BY
                qt.id_questionintest
      ";

      $stmt = Connection::connect()->prepare($sql);
      $stmt -> execute([':user_id' => $userId, ':exam_id' => $examId]);
      return $stmt -> fetchAll(PDO::FETCH_CLASS);
    }




    static public function getUserStripeData($customerNumber) {
      $response = array();
      $response['stripeCustomerID'] = $customerNumber;

      // Obtener todas las suscripciones del cliente
      $subscriptions = \Stripe\Subscription::all(['customer' => $customerNumber]);

      if (count($subscriptions->data) > 0) {
          $firstSubscription = $subscriptions->data[0];
          $response['next_billing_date'] = $firstSubscription->current_period_end;
          $response['status'] = $firstSubscription->status;
      } else {
          // Si no hay suscripciones activas, buscar suscripciones canceladas
          $canceledSubscriptions = \Stripe\Subscription::all([
              'customer' => $customerNumber,
              'status' => 'canceled'
          ]);


          if (count($canceledSubscriptions->data) > 0) {
              $response['status'] = 'canceled';
          } else {
              $response['status'] = 'No Subscription Found';
          }
      }

      // Verificar métodos de pago
      $paymentMethods = \Stripe\PaymentMethod::all([
          'customer' => $customerNumber,
          'type' => 'card',
      ]);
      $response['has_payment_method'] = count($paymentMethods->data) > 0;

      return $response;
  }





    static public function verifyEmail($token) {
      $decoded = JWT::decode($token, new Key("d12sd124df3456dfw43w3fw34df", 'HS256'));
       try {

        // Get the user email from the decoded token
        $userEmail = $decoded->data->email;

        // Update the email_verified field in the database for this user
        $stmt = Connection::connect()->prepare("UPDATE users SET verified_user = 1 WHERE email_user = :email");
        $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
        $stmt->execute();
        // Return the response
        if ($stmt->rowCount() > 0) {
          return ['message' => 'Email verified successfully!', 'status' => 200];
        } else {
          return ['message' => 'Tu email ya ha sido verificado', 'status' => 404];
        }
      } catch (Exception $e) {
        return ['message' => 'The verification link is not valid.', 'status' => 400];
      }

    }

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




    static public function getAverageByCategory($userId) {
      $sql = "SELECT
                c.id_category,
                c.name_category,
                IF(COUNT(t.id_test) > 0, 1, 0) AS has_tests,
                IF(SUM(t.finished_test) > 0, 1, 0) AS has_finished_tests,
                ROUND(AVG(t.final_note), 2) AS average_note,
                IF(SUM(t.finished_test) < COUNT(t.id_test), 1, 0) AS has_inprogress_tests,
                COUNT(t.id_test) AS total_tests,
                SUM(CASE WHEN t.final_note >= 75 THEN 1 ELSE 0 END) AS approved_tests,
                SUM(CASE WHEN t.final_note < 75 THEN 1 ELSE 0 END) AS failed_tests,
                GROUP_CONCAT(IF(t.finished_test = 0, t.id_test, NULL)) AS inprogress_id_test
            FROM
                categories c
            LEFT JOIN
                test t ON c.id_category = t.id_category_test AND t.id_user_test = :user_id
            GROUP BY
                c.id_category, c.name_category
            ORDER BY
                c.id_category;
      ";


      $stmt = Connection::connect()->prepare($sql);
      $stmt -> execute([':user_id' => $userId]);
      return $stmt -> fetchAll(PDO::FETCH_CLASS);
    }


    //-----> Get exam (Questions and answers)
    static public function getExam($examId) {
      $sql = "SELECT
              q.*,
              c.name_category,
              qt.id_test_questionintest,
              MAX(CASE WHEN a.answer_number = 1 THEN a.id_answer END) AS answer_1_id,
              MAX(CASE WHEN a.answer_number = 1 THEN a.string_answer END) AS answer_1_string,
              MAX(CASE WHEN a.answer_number = 1 THEN qo.answer_order END) AS answer_1_order,
              MAX(CASE WHEN a.answer_number = 2 THEN a.id_answer END) AS answer_2_id,
              MAX(CASE WHEN a.answer_number = 2 THEN a.string_answer END) AS answer_2_string,
              MAX(CASE WHEN a.answer_number = 2 THEN qo.answer_order END) AS answer_2_order,
              MAX(CASE WHEN a.answer_number = 3 THEN a.id_answer END) AS answer_3_id,
              MAX(CASE WHEN a.answer_number = 3 THEN a.string_answer END) AS answer_3_string,
              MAX(CASE WHEN a.answer_number = 3 THEN qo.answer_order END) AS answer_3_order,
              MAX(CASE WHEN a.answer_number = 4 THEN a.id_answer END) AS answer_4_id,
              MAX(CASE WHEN a.answer_number = 4 THEN a.string_answer END) AS answer_4_string,
              MAX(CASE WHEN a.answer_number = 4 THEN qo.answer_order END) AS answer_4_order,
              MAX(sa.id_test_student_answer) AS id_test_student_answer,
              MAX(CASE WHEN sa.id_test_student_answer = $examId AND sa.id_question_student_answer = q.id_question THEN sa.id_answer_student_answer END) AS id_answer_student_answer
            FROM
              questions q
              INNER JOIN categories c ON q.id_category_question = c.id_category
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
              LEFT JOIN questionintests_order qo ON a.id_answer = qo.id_answer AND qt.id_test_questionintest = qo.id_test
              LEFT JOIN student_answers sa ON q.id_question = sa.id_question_student_answer
          WHERE
              qt.id_test_questionintest = $examId
          GROUP BY
              q.id_question
          ";
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

    //
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

    static public function getFinishedTestData($examId) {
      $sql = "SELECT * FROM test
              INNER JOIN categories ON test.id_category_test = categories.id_category
              INNER JOIN openai ON test.id_test = openai.id_test_openai
              WHERE id_test = :examId AND type_openai = :type_openai";

      $stmt = Connection::connect()->prepare($sql);
      $stmt -> execute([':examId' => $examId, ':type_openai' => 'test']);
      return $stmt -> fetchAll(PDO::FETCH_CLASS);
    }



    static public function listAllQuestions() {
      $sql = "SELECT
                *
            FROM
                questions q
            JOIN
                answers a ON q.id_question = a.id_question_answer
            JOIN categories c ON q.id_category_question = c.id_category
            ORDER BY
                q.id_question, a.id_answer;
      ";
      $stmt = Connection::connect()->prepare($sql);
      $stmt->execute();

      $questions = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          if (!isset($questions[$row['id_question']])) {
              $questions[$row['id_question']] = [
                  'question' => $row['string_question'],
                  'category' => $row['name_category'],
                  'answers'  => []
              ];
          }
          $questions[$row['id_question']]['answers'][] = [
              'id'    => $row['id_answer'],
              'text'  => $row['string_answer'],
              'isTrue'=> $row['istrue_answer']
          ];
      }
      return $questions;
    }

  }