SELECT 
  q.*,
  qt.id_test_questionintest,
  MAX(CASE WHEN a.answer_number = 1 THEN a.id_answer END) AS answer_id_1,
  MAX(CASE WHEN a.answer_number = 1 THEN a.string_answer END) AS answer_string_1,
  MAX(CASE WHEN a.answer_number = 2 THEN a.id_answer END) AS answer_id_2,
  MAX(CASE WHEN a.answer_number = 2 THEN a.string_answer END) AS answer_string_2,
  MAX(CASE WHEN a.answer_number = 3 THEN a.id_answer END) AS answer_id_3,
  MAX(CASE WHEN a.answer_number = 3 THEN a.string_answer END) AS answer_string_3,
  MAX(CASE WHEN a.answer_number = 4 THEN a.id_answer END) AS answer_id_4,
  MAX(CASE WHEN a.answer_number = 4 THEN a.string_answer END) AS answer_string_4
FROM 
  questions q
  INNER JOIN questionintests qt ON q.id_question = qt.id_question_questionintest
  INNER JOIN (
    SELECT 
      a.*,
      @rn := IF(@prev_q = a.id_question_answer, @rn + 1, 1) AS answer_number,
      @prev_q := a.id_question_answer
    FROM 
      answers a,
      (SELECT @prev_q := NULL, @rn := 0) vars
    ORDER BY 
      a.id_question_answer, a.id_answer
  ) a ON q.id_question = a.id_question_answer
WHERE 
  qt.id_test_questionintest = 11
GROUP BY 
  q.id_question;