<?php
  include 'db/conn.php';

  header('Access-Control-Allow-Origin: *');

  if($_SERVER['REQUEST_METHOD']=='GET') {
    if(isset($_GET['id'])){
      $query="select * from users where user_id=".$_GET['id'];
      $result=methodGet($query);
      echo json_encode($result->fetch(PDO::FETCH_ASSOC));
    }else {
      $query="select * from users";
      $result=methodGet($query);
      echo json_encode($result->fetchAll());
    }
    header("HTTP/1.1 200 OK");
    exit();
  }

  if($_POST['METHOD']=='POST') {
    unset($_POST['METHOD']);
    $email=$_POST['user_email'];
    $joindate=$_POST['user_joindate'];
    $subscription=$_POST['user_subscription'];
    $query="insert into users(user_email, user_joindate, user_subscription) values ('$email', '$joindate', '$subscription')";
    $queryAutoIncrement="select MAX(id) as user_id from users";
    $result=methodPost($query, $queryAutoIncrement);
    header("HTTP/1.1 200 OK");
    exit();
  }

?>