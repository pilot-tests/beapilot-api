<?php
$pdo=null;
$servername = "beapilotpplspdb.mysql.database.azure.com";
$username = "dbbeapilotpplsp";
$password = "57J7ADT368KA31AN";
$bd = "beapilot";



function connectToDB() {
  try {
    $GLOBALS['pdo']=new PDO("mysql:host=".$GLOBALS['servername'].";dbname=".$GLOBALS['bd']."", $GLOBALS['username'], $GLOBALS['password']);
    $GLOBALS['pdo']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }catch (PDOException $e) {
    print "Error! No se pudo conectar a la BD ".$GLOBALS["bd"]."<br/>";
    print "\nError!: ".$e."<br/>";
    die();
  }
}

function disconnectDB() {
  $GLOBALS['pdo']=null;
}

function methodGet($query) {
  try{
    connectToDB();
    $statement=$GLOBALS['pdo']->prepare($query);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $statement->execute();
    disconnectDB();
    return $statement;
  }catch(Exception $e) {
    die("Error: ".$e);
  }
}

function methodPost($query, $queryAutoIncrement){
  try{
      conectar();
      $statement=$GLOBALS['pdo']->prepare($query);
      $statement->execute();
      $idAutoIncrement=methodGet($queryAutoIncrement)->fetch(PDO::FETCH_ASSOC);
      $resultado=array_merge($idAutoIncrement, $_POST);
      $statement->closeCursor();
      desconectar();
      return $resultado;
  }catch(Exception $e){
      die("Error: ".$e);
  }
}