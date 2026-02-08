<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use misc\StringUtil;

final class StringUtilTest extends TestCase
{
	// filterTagArray()

	public function testFilterTagArrayBasic(): void
	{
		$result = StringUtil::filterTagArray(['Wood', 'Metal', 'Stone']);
		$this->assertEqualsCanonicalizing(['wood', 'metal', 'stone'], $result);
	}

	public function testFilterTagArrayTrimsAndLowercases(): void
	{
		$result = StringUtil::filterTagArray(['  BRICK  ', ' Tile ']);
		$this->assertEqualsCanonicalizing(['brick', 'tile'], $result);
	}

	public function testFilterTagArraySplitsBySpace(): void
	{
		$result = StringUtil::filterTagArray(['red brick', 'blue stone']);
		$this->assertEqualsCanonicalizing(['red', 'brick', 'blue', 'stone'], $result);
	}

	public function testFilterTagArrayRemovesSpecialCharacters(): void
	{
		$result = StringUtil::filterTagArray(['wood!@#', 'metal$%^']);
		$this->assertEqualsCanonicalizing(['wood', 'metal'], $result);
	}

	public function testFilterTagArrayRemovesDuplicates(): void
	{
		$result = StringUtil::filterTagArray(['wood', 'Wood', 'WOOD']);
		$this->assertEquals(['wood'], array_values($result));
	}

	public function testFilterTagArraySkipsEmptyElements(): void
	{
		$result = StringUtil::filterTagArray(['', '   ', '!!!']);
		$this->assertEmpty($result);
	}

	public function testFilterTagArrayHandlesEmptyInput(): void
	{
		$result = StringUtil::filterTagArray([]);
		$this->assertEmpty($result);
	}

	// onlySmallLetters()

	public function testOnlySmallLettersBasic(): void
	{
		$this->assertEquals('helloworld', StringUtil::onlySmallLetters('Hello World!'));
	}

	public function testOnlySmallLettersRemovesNumbers(): void
	{
		$this->assertEquals('abc', StringUtil::onlySmallLetters('a1b2c3'));
	}

	public function testOnlySmallLettersReturnsEmptyForNoLetters(): void
	{
		$this->assertEquals('', StringUtil::onlySmallLetters('12345!@#'));
	}

	public function testOnlySmallLettersPreservesLowercase(): void
	{
		$this->assertEquals('alreadylower', StringUtil::onlySmallLetters('alreadylower'));
	}

	// onlyNumbers()

	public function testOnlyNumbersBasic(): void
	{
		$this->assertEquals('123', StringUtil::onlyNumbers('abc123def'));
	}

	public function testOnlyNumbersReturnsEmptyForNoDigits(): void
	{
		$this->assertEquals('', StringUtil::onlyNumbers('abcdef'));
	}

	public function testOnlyNumbersPreservesAllDigits(): void
	{
		$this->assertEquals('9876543210', StringUtil::onlyNumbers('9876543210'));
	}

	public function testOnlyNumbersStripsSpacesAndSpecials(): void
	{
		$this->assertEquals('42', StringUtil::onlyNumbers(' 4 2 '));
	}

	// removeNewline()

	public function testRemoveNewlineLineFeed(): void
	{
		$this->assertEquals('hello world', StringUtil::removeNewline("hello\n world"));
	}

	public function testRemoveNewlineCarriageReturn(): void
	{
		$this->assertEquals('hello world', StringUtil::removeNewline("hello\r world"));
	}

	public function testRemoveNewlineCRLF(): void
	{
		$this->assertEquals('hello world', StringUtil::removeNewline("hello\n\r world"));
	}

	public function testRemoveNewlineNoNewlines(): void
	{
		$this->assertEquals('hello world', StringUtil::removeNewline('hello world'));
	}

	// explodeFilterTrim()

	public function testExplodeFilterTrimBasic(): void
	{
		$result = StringUtil::explodeFilterTrim(',', 'a, b, c');
		$this->assertEquals(['a', 'b', 'c'], array_values($result));
	}

	public function testExplodeFilterTrimRemovesEmptyEntries(): void
	{
		$result = StringUtil::explodeFilterTrim(',', 'a,,b,,,c');
		$this->assertEquals(['a', 'b', 'c'], array_values($result));
	}

	public function testExplodeFilterTrimTrimsWhitespace(): void
	{
		$result = StringUtil::explodeFilterTrim(',', '  alpha  ,  beta  ,  gamma  ');
		$this->assertEquals(['alpha', 'beta', 'gamma'], array_values($result));
	}

	public function testExplodeFilterTrimEmptyString(): void
	{
		$result = StringUtil::explodeFilterTrim(',', '');
		$this->assertEmpty($result);
	}

	public function testExplodeFilterTrimThrowsOnEmptySeparator(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		StringUtil::explodeFilterTrim('', 'a,b,c');
	}

	public function testExplodeFilterTrimCustomSeparator(): void
	{
		$result = StringUtil::explodeFilterTrim('|', 'x | y | z');
		$this->assertEquals(['x', 'y', 'z'], array_values($result));
	}

	// addHttpParameters()

	public function testAddHttpParametersToUrl(): void
	{
		$result = StringUtil::addHttpParameters('https://example.com/path', ['key' => 'value']);
		$this->assertStringContainsString('key=value', $result);
		$this->assertStringStartsWith('https://example.com', $result);
	}

	public function testAddHttpParametersMergesWithExisting(): void
	{
		$result = StringUtil::addHttpParameters('https://example.com/path?existing=1', ['new' => '2']);
		$this->assertStringContainsString('existing=1', $result);
		$this->assertStringContainsString('new=2', $result);
	}

	public function testAddHttpParametersOverridesExisting(): void
	{
		$result = StringUtil::addHttpParameters('https://example.com/path?key=old', ['key' => 'new']);
		$this->assertStringContainsString('key=new', $result);
		$this->assertStringNotContainsString('key=old', $result);
	}

	public function testAddHttpParametersMultipleParams(): void
	{
		$result = StringUtil::addHttpParameters('https://example.com/', ['a' => '1', 'b' => '2']);
		$this->assertStringContainsString('a=1', $result);
		$this->assertStringContainsString('b=2', $result);
	}

	public function testAddHttpParametersHandlesUrlWithoutScheme(): void
	{
		// A URL without scheme still produces a result with the parameters
		$result = StringUtil::addHttpParameters('/path/only', ['key' => 'value']);
		$this->assertStringContainsString('key=value', $result);
		$this->assertStringContainsString('/path/only', $result);
	}
}
