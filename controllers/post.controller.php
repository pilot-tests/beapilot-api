<?php
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
    $return -> fncResponse($response);
  }


  //-----> Post request to add data
  static public function postData($table, $data) {
    $response = PostModel::postData($table, $data);

    $return = new PostController();
    $return -> fncResponse($response);
  }




    //-----> Post request to verify user
  static public function  createOrUpdateUser($authId, $authEmail) {
    $response = PostModel::createOrUpdateUser($authId, $authEmail);
    $return = new PostController();
    $return -> fncResponse($response);
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
  public function fncResponse($response) {
    if(!empty($response)) {
      $json = array(
        'status' => 200,
        'results' => $response
      );
    }
    else {
      $json = array(
        'status' => 404,
        'results' => "Not Found",
        "method" => "post"
      );
    }

    echo json_encode($json, http_response_code($json["status"]));
  }
}