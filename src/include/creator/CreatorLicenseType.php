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
}
