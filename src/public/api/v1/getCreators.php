<?php 
	require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';
	
	$output = DatabaseLogic::getCreators();
	JsonLogic::output($output);
?>