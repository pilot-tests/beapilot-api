# Start new test (id_category_test is _POST/_GET)
INSERT INTO test (id_category_test, creationdate_test) VALUES (3, NOW());

# Get the ID of the last register created
SELECT * from categories;

# WIP populate the questions in the test with the questions.
INSERT INTO 
	questionintests (id_test_questionintest, id_question_questionintest) 
VALUES (
	(SELECT LAST_INSERT_ID()), 
    (
		SELECT a.*, b.* 
        from questions a, test b
        where a.id_category_question = b.id_category_test
        and b.id_test = LAST_INSERT_ID()
    )
);