<?php
require_once "get.model.php";

class Connection {
  //-----> DB Info
  static public function infoDatabase() {
    $infoDB = array(
      "database" => "beapilot",
      "user" => "root",
      "pass" => "root"
    );

    return $infoDB;
  }

  //-----> DB Connect
  static public function connect() {
    try {
      $link = new PDO(
        "mysql:host=localhost;dbname=".Connection::infoDatabase()["database"],
        Connection::infoDatabase()["user"],
        Connection::infoDatabase()["pass"]
      );
      $link->exec("set names utf8");

    }catch(PDOException $e) {
      die("Error: ".$e->getMessage());
    }

    return $link;
  }

  // -----> Validate that table exists

  static public function getColumnsData($table, $columns){

    $database = Connection::infoDatabase()["database"];

    $validate = Connection::connect()
    ->query("SELECT COLUMN_NAME AS item FROM information_schema.columns WHERE table_schema = '$database' AND table_name = '$table'")
    ->fetchAll(PDO::FETCH_OBJ);

    if(empty($validate)){

      return null;

    }else {
      $sum = 0;
      foreach ($validate as $key => $value) {

        $sum += in_array($value->item, $columns);

      }
      return $sum == count($columns) ? $validate : null;
    }
  }




  //-----> Create Auth Token
  static public function jwt($id, $email) {

    $time = time();

    $token = array(
      "iat" => $time, //Time token is creted
      "exp" => date('Y-m-d H:i:s', time() + 60*60*24), // Token Expiration time (1day)
      "data" => [
        "id" => $id,
        "email" => $email
      ]
    );
    return $token;
  }


  //-----> Validate security token

  static public function tokenValidation($token) {
    //-----> Retrieve user using that token
    $user = GetModel::getDataFilter("users", "token_expiry_user", "token_user", $token, null, null, null, null);

    if(!empty($user)) {
      $currentDate = date('Y-m-d H:i:s');
      if ($currentDate < $user[0]->token_expiry_user) {
        return "ok";
      }
      else {
        return "expired";
      }
    }
    else {
      return "no-auth";
    }

  }
}
