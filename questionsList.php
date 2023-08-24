<?php

require_once "vendor/autoload.php";

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}
require_once('./models/get.model.php');

$allQuestions = GetModel::listAllQuestions();

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
    <table border="1">
        <thead>
            <tr>
                <th>Question</th>
                <th>Answers</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allQuestions as $question): ?>
                <tr>
                    <td><?= $question['question'] ?></td>
                    <td><?= $question['category'] ?></td>
                    <td>
                        <ul>
                            <?php foreach ($question['answers'] as $answer): ?>
                                <li class="<?= $answer['isTrue'] ? 'correct-answer' : '' ?>">
                                    <?= $answer['text'] ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
