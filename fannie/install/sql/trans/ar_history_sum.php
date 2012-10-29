<?php
/*
Table: ar_history_sum

Columns:
	card_no int
	charges dbms currency
	payments dbms currency
	balance dbms currency

Depends on:
	ar_history (view)

Use:
Sum of all charges and payments per customer
*/

$CREATE['trans.ar_history_sum'] = "
	CREATE TABLE ar_history_sum (
		card_no INT,
		charges DECIMAL(10,2),
		payments DECIMAL(10,2),
		balance DECIMAL(10,2),
		PRIMARY KEY(card_no)
	)
";
?>
