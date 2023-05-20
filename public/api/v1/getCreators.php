<?php 
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	
	$output = loadCreatorsFromDatabase();
	outputJson($output);
?>