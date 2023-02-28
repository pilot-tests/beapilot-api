
BEGIN;
INSERT INTO test (id_category_test, id_user_test)
  VALUES(8, 5);
INSERT INTO questionintests (id_test_questionintest, id_question_questionintest, id_user_questionintest) 
  SELECT LAST_INSERT_ID(), id_question, 5 FROM beapilot.questions WHERE id_category_question = 2 ORDER BY RAND() LIMIT 20;
COMMIT;
