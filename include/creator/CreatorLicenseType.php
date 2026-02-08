<?php

namespace creator;

use misc\Slug;

enum CreatorLicenseType: int
{

	use Slug;

	case ANY_LICENSE = 3;
	case OPEN_LICENSE = 2;
	case PUBLIC_DOMAIN = 1;


	public function title(): string
	{
		return match ($this) {
			self::PUBLIC_DOMAIN => 'Public Domain Only',
			self::OPEN_LICENSE => 'Open License',
			self::ANY_LICENSE => 'Any License',
		};
	}

	public function slug(): string
	{
		return match ($this) {
			self::PUBLIC_DOMAIN => 'public-domain',
			self::OPEN_LICENSE => 'open',
			self::ANY_LICENSE => 'any',
		};
	}
}
