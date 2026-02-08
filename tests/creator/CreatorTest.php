<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use creator\Creator;
use creator\CreatorLicenseType;
use creator\CreatorLogic;

final class CreatorTest extends TestCase
{
	/**
	 * @return array<string, array{Creator}>
	 */
	public static function allCreatorsProvider(): array
	{
		$cases = [];
		foreach (Creator::cases() as $case) {
			$cases[$case->name] = [$case];
		}
		return $cases;
	}

	// fromValueOrString()

	#[DataProvider('allCreatorsProvider')]
	public function testFromValueOrStringWithInt(Creator $creator): void
	{
		$this->assertSame($creator, Creator::fromValueOrSlug($creator->value));
	}

	#[DataProvider('allCreatorsProvider')]
	public function testFromValueOrStringWithNumericString(Creator $creator): void
	{
		$this->assertSame($creator, Creator::fromValueOrSlug((string)$creator->value));
	}

	#[DataProvider('allCreatorsProvider')]
	public function testFromValueOrStringWithSlug(Creator $creator): void
	{
		$this->assertSame($creator, Creator::fromValueOrSlug($creator->slug()));
	}

	public function testFromValueOrStringWithInvalidTypeThrows(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		Creator::fromValueOrSlug(null);
	}

	public function testFromValueOrStringWithBoolThrows(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		Creator::fromValueOrSlug(true);
	}

	// title()

	#[DataProvider('allCreatorsProvider')]
	public function testTitleIsNonEmpty(Creator $creator): void
	{
		$this->assertNotEmpty($creator->title());
	}

	// description()

	#[DataProvider('allCreatorsProvider')]
	public function testDescriptionIsNonEmpty(Creator $creator): void
	{
		$this->assertNotEmpty($creator->description());
	}

	// baseUrl()

	#[DataProvider('allCreatorsProvider')]
	public function testBaseUrlStartsWithHttps(Creator $creator): void
	{
		$url = $creator->baseUrl();
		$this->assertMatchesRegularExpression('/^https?:\/\//', $url);
	}
}
