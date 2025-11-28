<?php

use misc\Log;

class Fetch
{

	public static array $defaultHeaders = [
		"User-Agent" => "3dassets.one / Fetching"
	];

	public static function fetchRemoteCookie(string $targetCookieName, string $url, array $headers = [], string $method = 'GET', $body = NULL): ?string
	{

		Log::stepIn(__FUNCTION__);
		Log::write("Fetching URL for cookie '$targetCookieName' : $url using $method with body '$body'");

		if (!isset($headers['User-Agent'])) {
			$headers = self::$defaultHeaders;
		}

		$client = new GuzzleHttp\Client(['cookies' => true]);
		try {
			$options = [
				'headers' => $headers,
				'body' => $body
			];
			$client->request($method, $url, $options);

			// https://github.com/guzzle/guzzle/issues/3114#issuecomment-1627228395
			$cookieJar = $client->getConfig('cookies');
			$cookie = $cookieJar->getCookieByName($targetCookieName)->getValue();
			Log::write("Cookie Request successful!");
		} catch (GuzzleHttp\Exception\ClientException $e) {
			Log::write("Cookie Request error, Status code: " . $e->getResponse()->getStatusCode(), "HTTP-ERROR");
			$cookie = NULL;
		} catch (Exception $e) {
			Log::write("Cookie Generic request error: " . $e->getMessage(), "HTTP-ERROR");
			$cookie = NULL;
		}

		Log::write("Cookie value determined: $cookie");
		Log::stepOut(__FUNCTION__);
		return $cookie; // Return null if the target cookie was not found
	}

	public static function fetchRemoteData(string $url, array $headers = [], string $method = 'GET', $body = NULL): string
	{
		Log::stepIn(__FUNCTION__);
		Log::write("Fetching URL: $url using $method with body '$body'");

		if (!isset($headers['User-Agent'])) {
			$headers = self::$defaultHeaders;
		}

		$client = new GuzzleHttp\Client();
		try {
			$options = [
				'headers' => $headers,
				'body' => $body
			];
			$result = $client->request($method, $url, $options);
			$content = $result->getBody();
			Log::write("Request successful!");
		} catch (GuzzleHttp\Exception\ClientException $e) {
			Log::write("Request error, Status code: " . $e->getResponse()->getStatusCode(), "HTTP-ERROR");
			$content = "";
		} catch (Exception $e) {
			Log::write("Generic request error: " . $e->getMessage(), "HTTP-ERROR");
			$content = "";
		}

		Log::write("Content length: " . strlen($content) . "");
		Log::stepOut(__FUNCTION__);
		return $content;
	}

	public static function fetchRemoteJson(string $url, array $headers = [], string $method = 'GET', $body = NULL, $jsonContentTypeHeader = false): mixed
	{
		Log::stepIn(__FUNCTION__);
		if ($jsonContentTypeHeader) {
			$headers['Content-Type'] = "application/json";
		}
		$result = json_decode(self::fetchRemoteData(url: $url, headers: $headers, method: $method, body: $body), true);
		Log::write("Received and parsed JSON.");
		Log::stepOut(__FUNCTION__);
		return $result;
	}

	public static function fetchRemoteCommaSeparatedList(string $url, array $headers = [], string $method = 'GET', $body = NULL): array
	{
		Log::stepIn(__FUNCTION__);
		$content = self::fetchRemoteData($url, $headers, $method, body: $body);
		$content = str_replace("\n", "", $content);

		$contentArray = explode(",", $content);
		$contentArray = array_filter($contentArray);
		$contentArray = array_map('trim', $contentArray);
		Log::stepOut(__FUNCTION__);
		return $contentArray;
	}
}
