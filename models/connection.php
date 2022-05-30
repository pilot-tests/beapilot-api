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
}
