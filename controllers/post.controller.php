<?php
require_once "models/get.model.php";
require_once "models/post.model.php";
require_once "models/put.model.php";
require_once "models/connection.php";

require_once "vendor/autoload.php";
use Firebase\JWT\JWT;


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
        $response = PostModel::postData($table, $data);
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
        $return -> fncResponse($response, "Email already exists");
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
        $return -> fncResponse($response, "Wrong Password");
      }
    }
    else {
      $response = null;
      $return = new PostController();
      $return -> fncResponse($response, "Wrong Email");
    }
  }




    //-----> Post request to verify user
  public function  createOrUpdateUser($authId, $authEmail) {

    $postModel = new PostModel();
    $response = $postModel->createOrUpdateUser($authId, $authEmail);

    $return = new PostController();
    $return -> fncResponse($response, null);
  }





  //-----> OpenAI resquest
  public function getAnswer($prompt) {
    $postModel = new PostModel();
    $responseOpenAi = $postModel->getAnswerFromOpenAI($prompt);
    return $responseOpenAi;
  }

  public function getAndStoreAnswer($prompt, $type, $userId, $testId) {
    $postModel = new PostModel();

    // Obtiene la respuesta de la API de OpenAI
    $responseOpenAi = $postModel->getAnswerFromOpenAI($prompt);

    // Guarda la respuesta en la base de datos
    $storeResult = $postModel->storePromptResult($prompt, $type, $userId, $testId, $responseOpenAi);

    // return $storeResult;
    $return = new PostController();
    $return -> fncResponse($storeResult);
  }

  //-----> Controller response
  public function fncResponse($response, $error) {
    if(!empty($response)) {
      //-----> Remove password from the response
      if(isset($response[0]->password_user)) {
        unset($response[0]->password_user);
      }

      $json = array(
        'status' => 200,
        'results' => $response
      );
    }
    else {

      if($error != null) {
        $json = array(
          'status' => 404,
          'results' => $error
        );
      }
      else {
        $json = array(
          'status' => 404,
          'results' => "Not Found",
          "method" => "post"
        );
      }
    }

    echo json_encode($json, http_response_code($json["status"]));
  }
}