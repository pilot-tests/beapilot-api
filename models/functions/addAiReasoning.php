<?php
  static public function AddAIReasoning() {
      try {
        // Conexión a la base de datos
        $link = Connection::connect();

        // Define el tamaño del lote y el número total de registros
        $batch_size = 3;  // Ajusta según tus necesidades
        $total_query = "SELECT COUNT(*) AS total FROM questions WHERE ai_reasoning_questions IS NULL OR ai_reasoning_questions = ''";
        $total_stmt = $link->prepare($total_query);
        $total_stmt->execute();
        $total_row = $total_stmt->fetch(PDO::FETCH_ASSOC);
        $total = $total_row['total'];
        // Calcula el número de lotes
        $batches = ceil($total / $batch_size);

        for ($i = 0; $i < $batches; $i++) {
          $offset = $i * $batch_size;
          $sql = "
              SELECT
                  q.id_question,
                  q.string_question,
                  c.name_category,
                  a.string_answer,
                  a.istrue_answer
              FROM
                  questions q
              INNER JOIN
                  answers a ON q.id_question = a.id_question_answer
              INNER JOIN
                  categories c ON q.id_category_question = c.id_category
              WHERE
                  q.ai_reasoning_questions IS NULL OR q.ai_reasoning_questions = ''
              ORDER BY
                  q.id_question, a.id_answer
              LIMIT $batch_size OFFSET $offset";

          $stmt = $link->prepare($sql);
          $stmt->execute();

          // Array para almacenar las preguntas y respuestas
          $questions = [];

          while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id_question = $row['id_question'];
            if (!isset($questions[$id_question])) {
                $questions[$id_question] = [
                    'question' => $row['string_question'],
                    'category' => $row['name_category'],
                    'answers' => []
                ];
            }
            $questions[$id_question]['answers'][] = [
                'answer' => $row['string_answer'],
                'is_true' => $row['istrue_answer']
            ];
          }

          // Procesar cada pregunta a través de la API de OpenAI
          foreach ($questions as $id_question => $data) {
              $category = $data['category'];
              $question = $data['question'];
              $answers = $data['answers'];

              // Formatear las respuestas para la solicitud a OpenAI
              $answer_text = "";
              $correct_answer = "";
              $letters = ['A', 'B', 'C', 'D'];
              foreach ($answers as $index => $answer_data) {
                $answer_text .= $letters[$index] . ". " . $answer_data['answer'] . "\n";
                if ($answer_data['is_true']) {
                    $correct_answer = $letters[$index];
                }
              }

              // Crear el texto de la pregunta para OpenAI
              $question_text = "Estoy haciendo un test para la licencia PPL, en la categoría $category, me preguntan lo siguiente:\n$question\n\nLas opciones de respuesta son\n$answer_text\n\nRespuesta correcta: $correct_answer\n\nRazona detalladamente el por qué.";


              // Solicitud a OpenAI

              try {
                  $yourApiKey = $_ENV['OPENAI_API_KEY'];
                  $client = OpenAI::client($yourApiKey);

                  $result = $client->completions()->create([
                      'model' => 'text-davinci-003',
                      'prompt' => $question_text,
                      'max_tokens' => 2000,
                      'temperature' => 0.3,
                  ]);

                  $openai_response = $result['choices'][0]['text'];
              } catch (Exception $e) {
                  echo 'Caught exception: ',  $e->getMessage(), "\n";
              }

              // Guardar el razonamiento en la base de datos
              $update_query = "UPDATE questions SET ai_reasoning_questions = :reasoning WHERE id_question = :id_question";
              $update_stmt = $link->prepare($update_query);
              $update_stmt->execute([':reasoning' => $openai_response, ':id_question' => $id_question]);
              // echo $openai_response . "<hr>";

              // Retardo entre solicitudes para evitar alcanzar el límite de tasa de la API
              // sleep(10);
          }
        }

      } catch (PDOException $e) {
          echo "Error: " . $e->getMessage();
      } catch (Exception $e) {
          echo "Error: " . $e->getMessage();
      }
    }
?>