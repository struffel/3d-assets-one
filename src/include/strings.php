<?php

class StringLogic{
	public static function onlySmallLetters(string $input){
		$output = strtolower($input);
		$output = preg_replace('/[^a-z]/','',$output);
		return $output;
	}
	public static function onlyNumbers(string $input){
		$output = preg_replace('/[^0-9]/','',$input);
		return $output;
	}
	public static function removeNewline($string){
		return str_replace(array("\n\r", "\n", "\r"), '', $string);
	}
	public static function explodeFilterTrim(string $separator, string $string){
		return array_filter(array_map('trim', explode($separator,$string)));
	}
}
	
?>