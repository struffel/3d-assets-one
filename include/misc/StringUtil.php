<?php

namespace misc;

use InvalidArgumentException;

class StringUtil
{
	/**
	 * 
	 * @param string[] $inputArray 
	 * @return string[] 
	 */
	public static function filterTagArray(array $inputArray): array
	{
		// Initialize an empty result array
		$resultArray = array();

		// Loop through each element in the input array
		foreach ($inputArray as $element) {
			// Trim the element and convert it to lowercase
			$filteredElement = strtolower(trim($element));

			// Split the element into multiple elements by space
			$splitElements = preg_split('/\s+/', $filteredElement);

			if ($splitElements === false) {
				$splitElements = [];
			}

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
	public static function onlySmallLetters(string $input): string
	{
		$output = strtolower($input);
		$output = preg_replace('/[^a-z]/', '', $output) ?? "";
		return $output;
	}
	public static function onlyNumbers(string $input): string
	{
		$output = preg_replace('/[^0-9]/', '', $input) ?? "";
		return $output;
	}
	public static function removeNewline(string $string): string
	{
		return str_replace(array("\n\r", "\n", "\r"), '', $string);
	}

	/**
	 * 
	 * @param string $separator 
	 * @param string $string 
	 * @return string[] 
	 */
	public static function explodeFilterTrim(string $separator, string $string): array
	{
		if (empty($separator)) {
			throw new InvalidArgumentException("Separator string cannot be empty");
		}
		return array_filter(array_map('trim', explode($separator, $string)));
	}


	/**
	 * 
	 * @param string $url 
	 * @param array<string, string> $newParameters 
	 * @return string 
	 */
	public static function addHttpParameters(string $url, array $newParameters): string
	{
		$urlParts = parse_url($url);

		if ($urlParts === false) {
			throw new InvalidArgumentException("Invalid URL provided");
		}

		if (isset($urlParts['query'])) {
			parse_str($urlParts['query'], $params);
		} else {
			$params = array();
		}

		$params = array_merge($params, $newParameters);

		$query = http_build_query($params);

		$scheme = $urlParts['scheme'] ?? 'http';
		$host = $urlParts['host'] ?? '';
		$path = $urlParts['path'] ?? '';

		// Build the final URL
		$output = $query;
		if ($path) {
			$output = $path . '?' . $output;
		}
		if ($host) {
			$output = $scheme . '://' . $host . $output;
		}

		return $output;
	}
}
