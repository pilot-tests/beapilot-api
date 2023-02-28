<?php


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
}
