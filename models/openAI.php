<?php

require_once __DIR__.'/../vendor/autoload.php'; // Asegúrate de que este camino apunta a tu archivo autoload.php generado por Composer


$yourApiKey = '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';
$client = OpenAI::client($yourApiKey);

$result = $client->completions()->create([
    'model' => 'text-davinci-003',
    'prompt' => $prompt,
    'max_tokens' => 200, // Ajusta este valor a la cantidad de tokens que necesites
]);

return  $result['choices'][0]['text'];

