<?php

namespace Creator;

enum CreatorLicenseType: int
{
	case ANY_LICENSE = 2;
	case PUBLIC_DOMAIN = 1;


	public function title(): string
	{
		return match ($this) {
			self::PUBLIC_DOMAIN => 'Public Domain Only',
			self::ANY_LICENSE => 'Any License',
		};
	}

	public function slug(): string
	{
		return match ($this) {
			self::PUBLIC_DOMAIN => 'public-domain',
			self::ANY_LICENSE => 'any',
		};
	}

	public static function tryFromSlug(string $slug): ?self
	{
		return match ($slug) {
			'public-domain' => self::PUBLIC_DOMAIN,
			'any-license' => self::ANY_LICENSE,
			default => null,
		};
	}
}
