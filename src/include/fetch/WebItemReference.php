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
		public array $headers = [],
		public string $requestBody = "",
		public array $queryParameters = []
	) {
		$this->headers = array_merge(self::$defaultHeaders, $this->headers);

		$this->options = [
			'headers' => $this->headers,
		];

		// Add body or query parameters if provided
		if (!empty($this->requestBody)) {
			$this->options['body'] = $this->requestBody;
		} elseif (!empty($this->queryParameters)) {
			$this->options['form_params'] = $this->queryParameters;
		}
	}

	public function fetchCookie(string $targetCookieName): ?string
	{
		Log::write("Fetching cookie for request: ", ["targetCookieName" => $targetCookieName, "request" => $this]);

		$client = new Client(['cookies' => true]);
		try {
			$client->request($this->method, $this->url, $this->options);

			// https://github.com/guzzle/guzzle/issues/3114#issuecomment-1627228395
			$cookieJar = $client->getConfig('cookies');
			$cookie = $cookieJar->getCookieByName($targetCookieName)->getValue();
			Log::write("Cookie Request successful!");
		} catch (ClientException $e) {
			Log::write("Cookie Request client error", [$e->getCode(), $e->getMessage()], LogLevel::ERROR);
			$cookie = NULL;
		} catch (Exception $e) {
			Log::write("Cookie request generic error: ", [$e->getCode(), $e->getMessage()], LogLevel::ERROR);
			$cookie = NULL;
		}

		Log::write("Cookie value determined", ["cookie" => $cookie]);

		return $cookie; // Return null if the target cookie was not found
	}

	/*public function __toString(): string
	{
		return sprintf(
			"URL: %s, Method: %s, Headers: %s, Body: %s, Parameters: %s",
			$this->url,
			$this->method,
			print_r($this->headers, true),
			$this->requestBody,
			print_r($this->queryParameters, true)
		);
	}*/

	public function fetch(): FetchedWebItem
	{
		$client = new Client();
		Log::write("Fetching: ", ["request" => $this]);
		try {
			$result = $client->request($this->method, $this->url, $this->options);
			$content = $result->getBody();
		} catch (ClientException $e) {
			Log::write("Request client error", [$e->getCode(), $e->getMessage()], LogLevel::ERROR);
			$content = NULL;
		} catch (Exception $e) {
			Log::write("Generic request error: ", [$e->getCode(), $e->getMessage()], LogLevel::ERROR);
			$content = NULL;
		}

		Log::write("Request completed", ["length" => strlen($content), "statusCode" => isset($result) ? $result->getStatusCode() : NULL]);

		return new FetchedWebItem(
			reference: $this,
			content: $content,
			httpStatusCode: isset($result) ? $result->getStatusCode() : NULL,
			lastUpdated: NULL
		);
	}
}
