<?php
    // Requiere el archivo que contiene tu modelo (que incluye tu función)
    require_once "vendor/autoload.php";

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}
    require_once('./models/get.model.php'); // Reemplaza 'path_to_your_model_file.php' con la ruta de tu archivo

    // Llama a la función
    GetModel::AddAIReasoning(); // Reemplaza YourModelClassName con el nombre de la clase de tu modelo que contiene la función
?>
