<?php
	function onlySmallLetters(string $input){
		$output = strtolower($input);
		$output = preg_replace('/[^a-z]/','',$output);
		return $output;
	}
	function onlyNumbers(string $input){
		$output = preg_replace('/[^0-9]/','',$input);
		return $output;
	}
	function removeNewline($string)
	{
		return str_replace(array("\n\r", "\n", "\r"), '', $string);
	}
?>