<?php

class StringLogic
{
	public static function onlySmallLetters(string $input)
	{
		$output = strtolower($input);
		$output = preg_replace('/[^a-z]/', '', $output);
		return $output;
	}
	public static function onlyNumbers(string $input)
	{
		$output = preg_replace('/[^0-9]/', '', $input);
		return $output;
	}
	public static function removeNewline($string)
	{
		return str_replace(array("\n\r", "\n", "\r"), '', $string);
	}
	public static function explodeFilterTrim(string $separator, string $string)
	{
		return array_filter(array_map('trim', explode($separator, $string)));
	}

	public static function addHttpParameters(string $url, array $newParameters): string
	{
		$url_parts = parse_url($url);
		// If URL doesn't have a query string.
		if (isset($url_parts['query'])) { // Avoid 'Undefined index: query'
			parse_str($url_parts['query'], $params);
		} else {
			$params = array();
		}

		$params = array_merge($params, $newParameters);

		// Note that this will url_encode all values
		$url_parts['query'] = http_build_query($params);

		// If not
		return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '?' . $url_parts['query'];
	}
}
