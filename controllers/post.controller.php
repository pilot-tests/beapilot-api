<?php
require_once "models/get.model.php";
require_once "models/post.model.php";
require_once "models/put.model.php";
require_once "models/connection.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once "vendor/autoload.php";
use \Stripe\Exception\ApiErrorException;
use Firebase\JWT\JWT;
\Stripe\Stripe::setApiKey($_ENV['STRIPE_KEY']);

class PostController {

 //-----> Post request to add new Test

  static public function postNewTest($addTest) {
    $response = PostModel::postNewTest($addTest);

    if ($response === false) {
      // Handle error here
      // You could throw an Exception or return a special response
    }

    $return = new PostController();
    $return -> fncResponse($response, null);
  }


  //-----> Post request to add data
  static public function postData($table, $data) {
    $response = PostModel::postData($table, $data);

    $return = new PostController();
    $return -> fncResponse($response, null);
  }





  //-----> Post request register user
  static public function postRegister($table, $data) {

    $response = GetModel::getDataFilter($table, "*","email_user", $data["email_user"], null, null, null, null);

    //-----> Check if the email exists or not.
    if(empty($response[0]->email_user)) {

      //-----> We create token based on if password is present.
      if(isset($data["password_user"]) && $data["password_user"] != null) {

        //TODO Create a random salt per user, store it on DB and use it to decrypt pass.
        // $salt = '$2a$07$' . substr(sha1(mt_rand()), 0, 22) . '$';
        $crypt = crypt($data["password_user"], '$2a$07$7b61560f4c62999371b4d3$');
        $data["password_user"] = $crypt;

        try {
          // Create a new customer in Stripe
          $stripeCustomer = \Stripe\Customer::create([
              'email' => $data["email_user"],
              'name'  => $data["name_user"]
          ]);

          // Add the Stripe customer ID to the user data
          $data["stripe_customer_id"] = $stripeCustomer->id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
          // Log the error for debugging purposes
          error_log($e->getMessage());

          // Respond with an appropriate error message
          $return = new PostController();
          $return->fncResponse(null, "An error occurred while creating the Stripe customer.", 500);
          return;
        }

        // Create a token for the email verification email
        $token = Connection::jwt($data["name_user"], $data["email_user"]);
        $jwt = JWT::encode($token, "d12sd124df3456dfw43w3fw34df", 'HS256');

        $data["email_token_user"] = $jwt;

        $response = PostModel::postData($table, $data);

        // Add Stripe ID to the $response
        $response["stripe_customer_id"] = $data["stripe_customer_id"];

        // Add Checkout Session to $response
        $checkout_session = PostModel::createCheckoutSession($data["stripe_customer_id"]);
        $response['stripe_session_id'] = $checkout_session;

        if(isset($response["comment"]) && $response["comment"] == "Sucess data entry") {
          // Create the verification link
          $verifyLink = $_ENV['API_URL'] . "/verify-email?token=$jwt";

          // Send the verification email
          $mail = new PHPMailer(true);
          $mail->isSMTP();
          $mail->Host = $_ENV['MAIL_HOST'];  // your host, could be localhost
          $mail->Port = $_ENV['MAIL_PORT'];        // port for MailHog, could be different with real SMTP
          $mail->SMTPAuth = false;   // MailHog doesn't need SMTP authentication

          $mail->setFrom('noreply@tusitio.com', 'Tu Sitio');
          $mail->addAddress($data["email_user"]); // Use the user's email
          $mail->Subject = 'Por favor verifica tu correo electrónico';
          $mail->Body    = "Hola,\n\nPor favor verifica tu correo electrónico haciendo clic en el siguiente enlace:\n\n$verifyLink";
          $mail->send();
        }

        $return = new PostController();
        $return -> fncResponse($response, null);
      }
      else {
        //-----> //TODO User register from external apps (Google, GitHub, Facebook, etc...)
        $response = PostModel::postData($table, $data);
        if(isset($response["comment"]) && $response["comment"] == "Edit successful") {
          //-----> Do stuff if user comes for external app.
        }
      }
    }
    else {
        $response = null;
        $return = new PostController();
        $return -> fncResponse(null, "Email already exists.", 409);
    }
  }




  //-----> Post request login user
  static public function postLogin($table, $data) {

    //-----> Validate user on DB
    $response = GetModel::getDataFilter($table, "*","email_user", $data["email_user"], null, null, null, null);
    if(!empty($response)) {

      //-----> Encrypt pass
      $crypt = crypt($data["password_user"], '$2a$07$7b61560f4c62999371b4d3$');

      if($response[0]->password_user == $crypt) {

        $stripeCustomerId = $response[0]->stripe_customer_id;
        $subscriptions = \Stripe\Subscription::all(['customer' => $stripeCustomerId]);
        $activeSubscription = false;
        foreach ($subscriptions->data as $subscription) {
            // if ($subscription->status === 'active') {
            //     $activeSubscription = true;
            //     break;
            // }
            $activeSubscription = $subscription->status;
        }

        $response[0]->active_subscription = $activeSubscription;
        $token = Connection::jwt($response[0]->id_user, $response[0]->email_user);
        $jwt = JWT::encode($token, "d12sd124df3456dfw43w3fw34df", 'HS256');
        //-----> Update database with Token
        $data = array(
          "token_user" => $jwt,
          "token_expiry_user" => $token["exp"]
        );
        $update = PutModel::putData($table, $data, $response[0]->id_user, "id_user");

        if(isset($update["comment"]) && $update["comment"] == "Edit successful") {
          $response[0]->token_user = $jwt;
          $response[0]->token_expiry_user = $token["exp"];

          $return = new PostController();
          $return -> fncResponse($response, null);
        }
      }
      else {
        $response = null;
        $return = new PostController();
        $return -> fncResponse(null, "Incorrect password.", 401);
      }
    }
    else {
      $response = null;
      $return = new PostController();
      $return -> fncResponse(null, "Wrong Email", 401);
    }
  }




  static public function postResubscribe($table, $data) {
    $customerId = $data["stripe_customer_id"];
    $subscriptions = \Stripe\Subscription::all(['customer' => $customerId]);

    $activeSubscriptions = [];
    $canceledSubscriptions = [];

    foreach ($subscriptions->autoPagingIterator() as $subscription) {
      if ($subscription->status === 'active') {
        array_push($activeSubscriptions, $subscription);
      } elseif ($subscription->status === 'canceled') {
        array_push($canceledSubscriptions, $subscription);
      }

    }
  }




    //-----> Post request to verify user
  public function  createOrUpdateUser($authId, $authEmail) {

    $postModel = new PostModel();
    $response = $postModel->createOrUpdateUser($authId, $authEmail);

    $return = new PostController();
    $return -> fncResponse($response, null);
  }





  public function postSubscribe($table, $postData) {
    $checkout_session = PostModel::createCheckoutSession();
  }







  //-----> OpenAI resquest
  public function getAnswer($userId, $testId) {
    $postModel = new PostModel();
    $testDetailPrompt = $postModel->getTestPrompt($userId, $testId);
    $responseOpenAi = $postModel->getAnswerFromOpenAI($prompt);
    return $responseOpenAi;
  }





  public function finishTest($prompt, $type, $userId, $testId) {
    $postModel = new PostModel();
    $putModel = new PutModel();

    PutModel::updateFinalScore($testId);


    $testPrompt = $postModel->getTestPrompt($userId, $testId);
    $globalPrompt = $postModel->getGlobalPrompt($userId);


    // Obtiene la respuesta de la API de OpenAI
    $timeStart = microtime(true);
    $noOpenAi = false;
    if($noOpenAi == true) {
      $globalResponseOpenAi = "Esta es la respuesta global de OpenAI";
      $testResponseOpenAi = "Esta es la respuesta al test de OpenAI";
    }
    else {
    $testResponseOpenAi = $postModel->getAnswerFromOpenAI($testPrompt);

    $timeEnd = microtime(true);
    $responseTimeTest = $timeEnd - $timeStart;


    $timeStartGlobal = microtime(true);
    $globalResponseOpenAi = $postModel->getAnswerFromOpenAI($globalPrompt);

    $timeEndGlobal = microtime(true);
    $responseTimeGlobal = $timeEndGlobal - $timeStartGlobal;

    }

    // Guarda la respuesta en la base de datos
    $storeResult = $postModel->storePromptResult($prompt, $type, $userId, $testId, $testResponseOpenAi, $globalResponseOpenAi, $responseTimeTest, $responseTimeGlobal);



    // return $storeResult;
    $return = new PostController();
    $return -> fncResponse($storeResult, null);
  }

  //-----> Controller response
  public function fncResponse($response, $error, $status = 200) {
    $json = array();

    if(!empty($response)) {
      //-----> Remove password from the response
      if(isset($response[0]->password_user)) {
        unset($response[0]->password_user);
      }

      $json = array(
        'status' => $status,
        'results' => $response
      );
    }
    else {
      $json = array(
        'status' => $status,
        'results' => $error ?? "Not Found"
      );
    }

    http_response_code($json["status"]);
    echo json_encode($json);
  }
}