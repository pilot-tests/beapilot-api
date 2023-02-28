select 
	*
from
	categories c
left join (
		SELECT * FROM test where id_user_test = 2
    ) t
ON c.id_category = t.id_category_test
