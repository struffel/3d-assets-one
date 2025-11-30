<?php

namespace fetch;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use log\Log;
use log\LogLevel;

class WebItemReference
{

	// Static
	private static array $defaultHeaders = [
		"User-Agent" => "3dassets.one / Fetching"
	];


	// Non-static
	private array $options = [];

	public function __construct(
		public string $url,
		public string $method = 'GET',
		public array $headers = self::$defaultHeaders,
		public string $requestBody = "",
		public array $queryParameters = []
	) {
		$this->headers = array_merge(self::$defaultHeaders, $this->headers);

		$this->options = [
			'headers' => $this->headers,
			'body' => $this->requestBody,
			'form_params' => $this->queryParameters
		];
	}

	public function fetchCookie(string $targetCookieName): ?string
	{
		Log::write(sprintf(
			"Fetching URL for cookie '%s' : %s using %s with body '%s' and parameters '%s'",
			$targetCookieName,
			$this->url,
			$this->method,
			$this->requestBody,
			$this->queryParameters
		));

		$client = new Client(['cookies' => true]);
		try {
			$client->request($this->method, $this->url, $this->options);

			// https://github.com/guzzle/guzzle/issues/3114#issuecomment-1627228395
			$cookieJar = $client->getConfig('cookies');
			$cookie = $cookieJar->getCookieByName($targetCookieName)->getValue();
			Log::write("Cookie Request successful!");
		} catch (ClientException $e) {
			Log::write("Cookie Request error, Status code: " . $e->getResponse()->getStatusCode(), LogLevel::ERROR);
			$cookie = NULL;
		} catch (Exception $e) {
			Log::write("Cookie Generic request error: " . $e->getMessage(), LogLevel::ERROR);
			$cookie = NULL;
		}

		Log::write("Cookie value determined: $cookie");

		return $cookie; // Return null if the target cookie was not found
	}

	public function fetch(): FetchedWebItem
	{
		$client = new Client();
		Log::write(sprintf(
			"Fetching URL: %s using %s with body '%s', parameters '%s', and headers: %s",
			$this->url,
			$this->method,
			$this->requestBody,
			$this->queryParameters,
			print_r($this->headers, true)
		));
		try {
			$result = $client->request($this->method, $this->url, $this->options);
			$content = $result->getBody();
			Log::write("Request successful!");
		} catch (ClientException $e) {
			Log::write("Request error, Status code: " . $e->getResponse()->getStatusCode(), LogLevel::ERROR);
			$content = NULL;
		} catch (Exception $e) {
			Log::write("Generic request error: " . $e->getMessage(), LogLevel::ERROR);
			$content = NULL;
		}

		Log::write("Content length: " . strlen($content) . "");

		return new FetchedWebItem(
			reference: $this,
			content: $content,
			httpStatusCode: isset($result) ? $result->getStatusCode() : NULL,
			lastUpdated: NULL
		);
	}
}
