<?php 
	require_once $_SERVER['DOCUMENT_ROOT'].'/../functions/init.php';
	
	$output = DatabaseLogic::getCreators();
	JsonLogic::output($output);
?>