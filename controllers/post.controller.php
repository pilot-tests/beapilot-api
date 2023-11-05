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

  static public function postNewTest($table,$addTest) {

    $response = PostModel::postNewTest($addTest);

    if ($response === false) {
      $return = new PostController();
      $return -> fncResponse("El test no se ha creado", null);
      exit;
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


  //-----> Post request send a message FROM the user
  static public function userContact($data, $name, $email, $message) {
    $response = PostModel::userContact($data, $name, $email, $message);

    $return = new PostController();
    $return -> fncResponse($response, null);
  }






  //-----> Post request register user
  static public function postRegister($table, $data) {

    $response = GetModel::getDataFilter($table, "*","email_user", $data["email_user"], null, null, null, null);

    //-----> Check if the email exists or not.
    if (!empty($response[0]->email_user)) {
      $response = null;
      $return = new PostController();
      $return->fncResponse(null, "Ya existe ese email.", 409);
      return;
    }


    //-----> We make sure we have password.
    if(isset($data["password_user"]) && $data["password_user"] != null) {
      $crypt = crypt($data["password_user"], '$2a$07$7b61560f4c62999371b4d3$');
      $data["password_user"] = $crypt;

      if($data["subscription_type"] != "free" && !empty($data["subscription_type"]) && isset($data["subscription_type"])) {
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
          error_log($e->getHttpStatus());
          error_log($e->getStripeCode());
          error_log($e->getError()->message);

          $stripeCode = $e->getStripeCode();
          $errorMessage = "An error occurred while creating the Stripe customer.";

          if ($stripeCode === 'email_invalid') {
              $errorMessage = "Email inválido, necesitamos un email en formato jon@doe.me";
          }

          // Respond with an appropriate error message
          $return = new PostController();
          $return->fncResponse(null, $errorMessage, 500);
          return;
        }
        $checkout_session = PostModel::createCheckoutSession($data["stripe_customer_id"]);
      } else {
        $data["stripe_customer_id"] = null;
        $checkout_session = null;
      }

      // Create a token for the email verification email
      $token = Connection::jwt($data["name_user"], $data["email_user"]);
      $jwt = JWT::encode($token, "d12sd124df3456dfw43w3fw34df", 'HS256');

      $data["email_token_user"] = $jwt;

      //-----> Create token and send email
      $verifyLink = $_ENV['FRONTEND_URL'] . "verify-email?token=$jwt";
      $email = new \SendGrid\Mail\Mail();
      $email->setFrom("noreply@testpilotpro.ai", "Daniel Martínez");
      $email->setSubject("Por favor verifica tu correo electrónico");
      $email->addTo($data["email_user"], $data["email_user"]);
      $email->addContent(
          "text/html", "<a clicktracking=off href=\"". $verifyLink . "\">". $verifyLink . "</a>"
      );
      $sendgrid = new \SendGrid($_ENV['SENDGRID_API_KEY']);

      try {
        $responseSG = $sendgrid->send($email);

      } catch (Exception $e) {
        $response["sendgrid_error"] = 'Caught exception: ' . $e->getMessage() . "\n";

      }

      $response = PostModel::postData($table, $data);

      // Add Stripe ID to the $response
      $response["stripe_customer_id"] = $data["stripe_customer_id"];

      // Add Checkout Session to $response
      $response['stripe_session_id'] = $checkout_session;
      $response["subscription_type"] = $data["subscription_type"];

      if(isset($response["comment"]) && $response["comment"] == "Sucess data entry") {
        $return = new PostController();
        $return->fncResponse($response, null);
      }
    }
      else {
        // Handle the case where no password is provided
        // TODO: User registration from external apps (Google, GitHub, Facebook, etc...)
        $response = PostModel::postData($table, $data);
        if (isset($response["comment"]) && $response["comment"] == "Edit successful") {
            // Do stuff if the user comes from an external app.
        }
      }
  }




  //-----> Post request login user
  static public function postLogin($table, $data) {

    //-----> Validate user on DB
    $response = GetModel::getDataFilter($table, "*","email_user", $data["email_user"], null, null, null, null);
    if(!empty($response)) {

      //-----> Encrypt pass- TODO: Change for password_hash().
      $crypt = crypt($data["password_user"], '$2a$07$7b61560f4c62999371b4d3$');

      if($response[0]->password_user == $crypt) {

        $stripeCustomerId = $response[0]->stripe_customer_id;

        $activeSubscription = false;
        $endOfSubscriptionPeriod = false;

        try {
          $subscriptions = \Stripe\Subscription::all(['customer' => $stripeCustomerId]);
          foreach ($subscriptions->data as $subscription) {
            // if ($subscription->status === 'active') {
            //     $activeSubscription = true;
            //     break;
            // }
            $activeSubscription = $subscription->status;
            $endOfSubscriptionPeriod = $subscription->current_period_end;
          }
        } catch (\Stripe\Exception\ApiErrorException $e) {
          if ($e->getHttpStatus() === 404 || $e->getStripeCode() === 'resource_missing') {
            // Cliente no encontrado, podrías establecer $subscriptions a null o manejarlo de alguna otra manera.
            $subscriptions = null;
            $activeSubscription = null;
          } else {
            // Ocurrió otro error, puedes optar por volver a lanzar la excepción o manejarlo de otra manera.
            throw $e;
            $activeSubscription = null;
          }
        }


        $response[0]->active_subscription = $activeSubscription;
        $response[0]->subscription_ends = $endOfSubscriptionPeriod;
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




  static public function resubscribe($customerID) {

    $response = PostModel::resubscribe($customerID);

    $return = new PostController();
    $return -> fncResponse($response, null);
  }




  //-----> Post request to cancel subscription
  public function cancelSubscription($customerNumber) {
    $postModel = new PostModel();
    $cancelSubscription = $postModel->cancelSubscription($customerNumber);
    $return = new PostController();
    $return -> fncResponse($cancelSubscription, null);
  }




    //-----> Post request to verify user
  public function createOrUpdateUser($authId, $authEmail) {

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




  //-----> Add correctg answer via OpenAI to unanswered questions
  static public function addCorrectAnswer() {
    $response = PostModel::addCorrectAnswer();
    $return = new PostController();
    $return -> fncResponse($response, null);
  }





  public function finishTest($prompt, $type, $userId, $testId) {
    $postModel = new PostModel();
    $putModel = new PutModel();

    $updateScoreResult = PutModel::updateFinalScore($testId);
    // Check if no questions were answered
    if ($updateScoreResult['comment'] == 'No questions answered') {
        // Here your code to return a message to frontend
        // return $storeResult;
      $return = new PostController();
      $return -> fncResponse("Cannot finish test, no questions were answered", null);
      exit;
    }


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