<?php

namespace misc;

use InvalidArgumentException;

trait Slug
{
	public function slug(): string
	{
		throw new InvalidArgumentException("Slug not defined for " . get_class($this));
	}

	public static function fromSlug(string $slug): self
	{
		return self::tryFromSlug($slug) ?? throw new InvalidArgumentException("Invalid slug: $slug");
	}

	public static function tryFromSlug(string $slug): ?self
	{
		$cases = method_exists(self::class, 'cases') ? self::cases() : [];
		foreach ($cases as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
		return null;
	}
}
