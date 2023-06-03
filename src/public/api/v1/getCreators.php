<?php 
	require_once $_SERVER['DOCUMENT_ROOT'].'/../functions/init.php';
	
	$output = loadCreatorsFromDatabase();
	outputJson($output);
?>