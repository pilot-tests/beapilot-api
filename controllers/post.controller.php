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

  public function getAnswer($prompt) {
        $postModel = new PostModel();
        $response = $postModel->getAnswerFromOpenAI($prompt);
        return $response;
    }
}