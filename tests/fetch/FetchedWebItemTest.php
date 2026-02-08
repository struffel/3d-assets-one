<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use fetch\FetchedWebItem;
use fetch\WebItemReference;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

final class FetchedWebItemTest extends TestCase
{
	private function makeItem(?string $content, ?int $httpStatusCode = 200): FetchedWebItem
	{
		return new FetchedWebItem(
			reference: new WebItemReference(url: 'https://example.com'),
			content: $content,
			httpStatusCode: $httpStatusCode,
			lastUpdated: null
		);
	}

	// parseAsJson()

	public function testParseAsJsonValid(): void
	{
		$item = $this->makeItem('{"key": "value", "number": 42}');
		$result = $item->parseAsJson();
		$this->assertIsArray($result);
		$this->assertEquals('value', $result['key']);
		$this->assertEquals(42, $result['number']);
	}

	public function testParseAsJsonArray(): void
	{
		$item = $this->makeItem('[1, 2, 3]');
		$result = $item->parseAsJson();
		$this->assertIsArray($result);
		$this->assertEquals([1, 2, 3], $result);
	}

	public function testParseAsJsonInvalid(): void
	{
		$item = $this->makeItem('not json at all');
		$result = $item->parseAsJson();
		$this->assertNull($result);
	}

	public function testParseAsJsonNullContent(): void
	{
		$item = $this->makeItem(null);
		$result = $item->parseAsJson();
		$this->assertNull($result);
	}

	public function testParseAsJsonEmptyObject(): void
	{
		$item = $this->makeItem('{}');
		$result = $item->parseAsJson();
		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	// parseAsCommaSeparatedList()

	public function testParseAsCommaSeparatedListBasic(): void
	{
		$item = $this->makeItem('alpha,beta,gamma');
		$result = $item->parseAsCommaSeparatedList();
		$this->assertEquals(['alpha', 'beta', 'gamma'], array_values($result));
	}

	public function testParseAsCommaSeparatedListTrims(): void
	{
		$item = $this->makeItem('  alpha , beta , gamma  ');
		$result = $item->parseAsCommaSeparatedList();
		$this->assertEquals(['alpha', 'beta', 'gamma'], array_values($result));
	}

	public function testParseAsCommaSeparatedListFiltersEmpty(): void
	{
		$item = $this->makeItem('a,,b,,,c');
		$result = $item->parseAsCommaSeparatedList();
		$this->assertEquals(['a', 'b', 'c'], array_values($result));
	}

	public function testParseAsCommaSeparatedListStripsNewlines(): void
	{
		$item = $this->makeItem("a,\nb,\nc");
		$result = $item->parseAsCommaSeparatedList();
		$this->assertContains('a', $result);
		$this->assertContains('b', $result);
		$this->assertContains('c', $result);
	}

	public function testParseAsCommaSeparatedListNullContent(): void
	{
		$item = $this->makeItem(null);
		$result = $item->parseAsCommaSeparatedList();
		$this->assertEmpty($result);
	}

	//  parseAsSitemap()

	public function testParseAsSitemapBasic(): void
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
				<url>
					<loc>https://example.com/page1</loc>
					<lastmod>2025-01-01</lastmod>
				</url>
				<url>
					<loc>https://example.com/page2</loc>
					<lastmod>2025-06-01</lastmod>
				</url>
			</urlset>';
		$item = $this->makeItem($xml);
		$result = $item->parseAsSitemap();

		$this->assertNotNull($result);
		$this->assertCount(2, $result);
		$this->assertInstanceOf(WebItemReference::class, $result[0]);
	}

	public function testParseAsSitemapFilterByDate(): void
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
				<url>
					<loc>https://example.com/old</loc>
					<lastmod>2020-01-01</lastmod>
				</url>
				<url>
					<loc>https://example.com/new</loc>
					<lastmod>2025-06-01</lastmod>
				</url>
			</urlset>';
		$item = $this->makeItem($xml);
		$cutoff = new DateTime('2024-01-01');
		$result = $item->parseAsSitemap(filterNewerThan: $cutoff);

		$this->assertNotNull($result);
		$this->assertCount(1, $result);
	}

	public function testParseAsSitemapNullContent(): void
	{
		$item = $this->makeItem(null);
		$result = $item->parseAsSitemap();
		$this->assertNull($result);
	}

	// parseHtmlMetaTags()

	public function testParseHtmlMetaTagsWithNameAttribute(): void
	{
		$html = '<html><head>
			<meta name="description" content="A test page">
			<meta name="keywords" content="test,php,unit">
		</head><body></body></html>';
		$item = $this->makeItem($html);
		$result = $item->parseHtmlMetaTags();

		$this->assertNotNull($result);
		$this->assertEquals('A test page', $result['description']);
		$this->assertEquals('test,php,unit', $result['keywords']);
	}

	public function testParseHtmlMetaTagsWithPropertyAttribute(): void
	{
		$html = '<html><head>
			<meta property="og:title" content="OG Title">
			<meta property="og:image" content="https://example.com/img.jpg">
		</head><body></body></html>';
		$item = $this->makeItem($html);
		$result = $item->parseHtmlMetaTags();

		$this->assertNotNull($result);
		$this->assertEquals('OG Title', $result['og:title']);
		$this->assertEquals('https://example.com/img.jpg', $result['og:image']);
	}

	public function testParseHtmlMetaTagsMixedAttributes(): void
	{
		$html = '<html><head>
			<meta name="author" content="John">
			<meta property="og:type" content="website">
		</head><body></body></html>';
		$item = $this->makeItem($html);
		$result = $item->parseHtmlMetaTags();

		$this->assertNotNull($result);
		$this->assertArrayHasKey('author', $result);
		$this->assertArrayHasKey('og:type', $result);
	}

	public function testParseHtmlMetaTagsNullContent(): void
	{
		$item = $this->makeItem(null);
		$result = $item->parseHtmlMetaTags();
		$this->assertNull($result);
	}

	public function testParseHtmlMetaTagsNoMetaTags(): void
	{
		$html = '<html><head><title>No meta</title></head><body></body></html>';
		$item = $this->makeItem($html);
		$result = $item->parseHtmlMetaTags();

		$this->assertNotNull($result);
		$this->assertEmpty($result);
	}

	// parseAsDomDocument()

	public function testParseAsDomDocumentValid(): void
	{
		$html = '<html><body><p>Hello</p></body></html>';
		$item = $this->makeItem($html);
		$result = $item->parseAsDomDocument();

		$this->assertNotNull($result);
		$this->assertInstanceOf(\DOMDocument::class, $result);
	}

	public function testParseAsDomDocumentNullContent(): void
	{
		$item = $this->makeItem(null);
		$result = $item->parseAsDomDocument();
		$this->assertNull($result);
	}

	// parseAsSimpleXmlElement()

	public function testParseAsSimpleXmlElementValid(): void
	{
		$xml = '<?xml version="1.0"?><root><item>test</item></root>';
		$item = $this->makeItem($xml);
		$result = $item->parseAsSimpleXmlElement();

		$this->assertNotNull($result);
		$this->assertInstanceOf(\SimpleXMLElement::class, $result);
		$this->assertEquals('test', (string)$result->item);
	}

	public function testParseAsSimpleXmlElementNullContent(): void
	{
		$item = $this->makeItem(null);
		$result = $item->parseAsSimpleXmlElement();
		$this->assertNull($result);
	}

	// parseAsGdImage()

	public function testParseAsGdImageValidPng(): void
	{
		// Create a minimal 64x64 PNG in memory
		$img = imagecreatetruecolor(64, 64);
		ob_start();
		imagepng($img);
		$pngData = ob_get_clean();

		$item = $this->makeItem($pngData);
		$result = $item->parseAsGdImage();

		$this->assertNotNull($result);
		$this->assertInstanceOf(GdImage::class, $result);
	}

	#[WithoutErrorHandler]
	public function testParseAsGdImageInvalidData(): void
	{
		$item = $this->makeItem('not an image');
		$result = $item->parseAsGdImage();
		$this->assertNull($result);
	}

	public function testParseAsGdImageNullContent(): void
	{
		$item = $this->makeItem(null);
		$result = $item->parseAsGdImage();
		$this->assertNull($result);
	}
}
