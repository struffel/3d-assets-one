<?php
	function onlySmallLetters(string $input){
		$output = strtolower($input);
		$output = preg_replace('/[^a-z]/','',$output);
		return $output;
	}
?>