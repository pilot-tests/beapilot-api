<?php

require_once "vendor/autoload.php";

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}
require_once('./models/get.model.php');

$allQuestions = GetModel::readImage();

?>

<!DOCTYPE html>
<html>
<head>
    <title>List of Questions</title>
    <style>
        .correct-answer {
            font-weight: bold;
            color: green;
        }
        tr:nth-child(odd) {
            background-color: #eee;
        }
    </style>
</head>
<body>
  <?php echo $allQuestions; ?>

</body>
</html>
