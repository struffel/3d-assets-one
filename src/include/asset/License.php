<?php

namespace asset;

enum License: int
{
	case CUSTOM = 0;
	case CC0 = 1;
		#case CC_BY = 2;
		#case CC_BY_SA = 3;
		#case CC_BY_NC = 4;
	case CC_BY_ND = 5;
		#case CC_BY_NC_SA = 6;
		#case CC_BY_NC_ND = 7;
	case APACHE_2_0 = 8;

	public function slug(): string
	{
		return match ($this) {
			License::CUSTOM => 'custom',
			License::CC0 => 'cc-0',
			#License::CC_BY => 'cc-by',
			#License::CC_BY_SA => 'cc-by-sa',
			#License::CC_BY_NC => 'cc-by-nc',
			License::CC_BY_ND => 'cc-by-nd',
			#License::CC_BY_NC_SA => 'cc-by-nc-sa',
			#License::CC_BY_NC_ND => 'cc-by-nc-nd',
			License::APACHE_2_0 => 'apache-2-0',
		};
	}

	public function name(): string
	{
		return match ($this) {
			License::CUSTOM => 'Custom License - Check Website',
			License::CC0 => 'Creative Commons CC0',
			#License::CC_BY => 'Creative Commons BY',
			#License::CC_BY_SA => 'Creative Commons BY-SA',
			#License::CC_BY_NC => 'Creative Commons BY-NC',
			License::CC_BY_ND => 'Creative Commons BY-ND',
			#License::CC_BY_NC_SA => 'Creative Commons BY-NC-SA',
			#License::CC_BY_NC_ND => 'Creative Commons BY-NC-ND',
			License::APACHE_2_0 => 'Apache License 2.0',
		};
	}

	public static function fromSlug(string $slug): ?self
	{
		foreach (License::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
		return null;
	}
}
