<?php

declare(strict_types=1);
// This test class covers all implementations of Slugable.

use asset\AssetType;
use creator\Creator;
use creator\CreatorLicenseType;
use slug\Slugable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SlugTest extends TestCase
{

	public static function slugableImplementationsProvider(): array
	{
		return [
			"Creator" => [Creator::class],
			"AssetType" => [AssetType::class],
			"CreatorLicenseType" => [CreatorLicenseType::class],

		];
	}

	#[DataProvider('slugableImplementationsProvider')]
	public function testFromSlugRoundTrip(string $class): void
	{
		$cases = $class::cases();
		foreach ($cases as $instance) {
			$slug = $instance->slug();
			$this->assertSame($instance, $class::fromSlug($slug));
		}
	}

	#[DataProvider('slugableImplementationsProvider')]
	public function testTryFromSlugInvalidReturnsNull(string $class): void
	{
		$this->assertNull($class::tryFromSlug('nonexistent-slug'));
	}

	#[DataProvider('slugableImplementationsProvider')]
	public function testTryFromSlugEmptyReturnsNull(string $class): void
	{
		$this->assertNull($class::tryFromSlug(''));
	}

	#[DataProvider('slugableImplementationsProvider')]
	public function testAllSlugsAreUnique(string $class): void
	{
		$cases = $class::cases();
		$slugs = array_map(fn($case) => $case->slug(), $cases);
		$this->assertCount(count($cases), array_unique($slugs), "Slugs are not unique in $class");
	}
}
