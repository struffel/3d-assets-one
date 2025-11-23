<?php

namespace misc;

class Html
{
	public static function readMetatagsFromHtmlString(string $htmlString): array
	{
		$output = [];
		$document = HtmlLogic::domObjectFromHtmlString($htmlString);

		$metaTags = $document->getElementsByTagName('meta');
		foreach ($metaTags as $tag) {
			if ($tag->getAttribute('name') ?? "" != "") {
				$output[$tag->getAttribute('name')] = $tag->getAttribute('content');
			} elseif ($tag->getAttribute('property') ?? "" != "") {
				$output[$tag->getAttribute('property')] = $tag->getAttribute('content');
			}
		}
		return $output;
	}

	public static function domObjectFromHtmlString(string $htmlString): DOMDocument
	{
		$document = new DOMDocument();
		@$document->loadHTML($htmlString);
		return $document;
	}

	public static function getElementsByClassName($dom, $className, $startAtNode = NULL,)
	{
		// https://stackoverflow.com/a/6366390
		$finder = new DomXPath($dom);
		$nodes = $finder->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' $className ')]", $startAtNode);
		return $nodes;
	}
}
