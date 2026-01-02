<?php

namespace misc;

class StringUtil
{
	public static function filterTagArray(array $inputArray)
	{
		// Initialize an empty result array
		$resultArray = array();

		// Loop through each element in the input array
		foreach ($inputArray as $element) {
			// Trim the element and convert it to lowercase
			$filteredElement = strtolower(trim($element));

			// Split the element into multiple elements by space
			$splitElements = preg_split('/\s+/', $filteredElement);

			// Loop through the split elements and remove non-alphanumeric characters
			foreach ($splitElements as $splitElement) {
				// Remove non-alphanumeric characters using a regular expression
				$filteredSplitElement = preg_replace('/[^a-z0-9]/', '', $splitElement);

				// Check if the filtered element is not empty and add it to the result array
				if (!empty($filteredSplitElement)) {
					$resultArray[] = $filteredSplitElement;
				}
			}
		}

		return array_unique($resultArray);
	}
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
