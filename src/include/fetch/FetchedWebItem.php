<?php

namespace fetch;

use DateTime;
use DOMDocument;
use DOMNodeList;
use DOMXPath;
use SimpleXMLElement;

class FetchedWebItem
{

	public function __construct(
		public readonly WebItemReference $reference,
		public readonly ?string $content,
		public readonly ?int $httpStatusCode,
		public readonly ?DateTime $lastUpdated
	) {}

	/**
	 * 
	 * @return WebItemReference[]
	 */
	public function parseAsSitemap(?DateTime $filterNewerThan = NULL): ?array
	{

		$xml = $this->parseAsSimpleXmlElement();
		if ($xml === null) {
			return null;
		}

		$urls = [];
		foreach ($xml->url as $urlEntry) {
			$loc = (string)$urlEntry->loc;
			$lastmod = isset($urlEntry->lastmod) ? new DateTime((string)$urlEntry->lastmod) : new DateTime();
			if ($filterNewerThan === null || $lastmod >= $filterNewerThan) {
				$urls[] = new WebItemReference(url: $loc);
			}
		}

		return $urls;
	}

	/**
	 * 
	 * @return array<string> 
	 */
	public function parseAsCommaSeparatedList(): array
	{
		if ($this->content === null) {
			return [];
		}

		$content = str_replace("\n", "", $this->content);

		$contentArray = explode(",", $content);
		$contentArray = array_filter($contentArray);
		$contentArray = array_map('trim', $contentArray);

		return $contentArray;
	}

	/**
	 * 
	 * @return null|array<mixed,mixed>
	 */
	public function parseAsJson(): ?array
	{
		if ($this->content === null) {
			return null;
		}
		$result = json_decode($this->content, associative: true);

		return $result;
	}

	public function parseAsSimpleXmlElement(): ?SimpleXMLElement
	{
		if ($this->content === null) {
			return null;
		}
		$result = new SimpleXMLElement($this->content);

		return $result;
	}

	public function parseAsDomDocument(): ?DOMDocument
	{
		if ($this->content === null) {
			return null;
		}
		$document = new DOMDocument();
		@$document->loadHTML($this->content);
		return $document;
	}

	/**
	 * 
	 * @return null|array<string,string>
	 */
	public function parseHtmlMetaTags(): ?array
	{
		$output = [];
		$document = $this->parseAsDomDocument();
		if ($document === null) {
			return null;
		}

		$metaTags = $document->getElementsByTagName('meta');
		foreach ($metaTags as $tag) {
			if ($tag->getAttribute('name') != "") {
				$output[$tag->getAttribute('name')] = $tag->getAttribute('content');
			} elseif ($tag->getAttribute('property')  != "") {
				$output[$tag->getAttribute('property')] = $tag->getAttribute('content');
			}
		}
		return $output;
	}

	/*public function parseAsWordpressApiPosts()
	{
		// Not implemented yet
	}*/
}
