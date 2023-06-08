<?php
require_once "models/get.model.php";
require_once "models/post.model.php";



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

    if(isset($data["password_user"]) && $data["password_user"] != null) {

      //TODO Create a random salt per user, store it on DB and use it to decrypt pass.
      // $salt = '$2a$07$' . substr(sha1(mt_rand()), 0, 22) . '$';
      $crypt = crypt($data["password_user"], '$2a$07$7b61560f4c62999371b4d3$');
      $data["password_user"] = $crypt;
      $response = PostModel::postData($table, $data);
    }

    $response = PostModel::postData($table, $data);

    $return = new PostController();
    $return -> fncResponse($response, null);
  }




  //-----> Post request login user
  static public function postLogin($table, $data) {

    //-----> Validate user on DB
    $response = GetModel::getDataFilter($table, "*","email_user", $data["email_user"], null, null, null, null);


    if(!empty($response)) {
      //-----> Encrypt pass
      $crypt = crypt($data["password_user"], '$2a$07$7b61560f4c62999371b4d3$');

      if($response[0]->password_user == $crypt) {
        
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