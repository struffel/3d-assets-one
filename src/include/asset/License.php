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
			LICENSE::CUSTOM => 'custom',
			LICENSE::CC0 => 'cc-0',
			#LICENSE::CC_BY => 'cc-by',
			#LICENSE::CC_BY_SA => 'cc-by-sa',
			#LICENSE::CC_BY_NC => 'cc-by-nc',
			LICENSE::CC_BY_ND => 'cc-by-nd',
			#LICENSE::CC_BY_NC_SA => 'cc-by-nc-sa',
			#LICENSE::CC_BY_NC_ND => 'cc-by-nc-nd',
			LICENSE::APACHE_2_0 => 'apache-2-0',
		};
	}

	public function name(): string
	{
		return match ($this) {
			LICENSE::CUSTOM => 'Custom License - Check Website',
			LICENSE::CC0 => 'Creative Commons CC0',
			#LICENSE::CC_BY => 'Creative Commons BY',
			#LICENSE::CC_BY_SA => 'Creative Commons BY-SA',
			#LICENSE::CC_BY_NC => 'Creative Commons BY-NC',
			LICENSE::CC_BY_ND => 'Creative Commons BY-ND',
			#LICENSE::CC_BY_NC_SA => 'Creative Commons BY-NC-SA',
			#LICENSE::CC_BY_NC_ND => 'Creative Commons BY-NC-ND',
			LICENSE::APACHE_2_0 => 'Apache License 2.0',
		};
	}

	public static function fromSlug(string $slug): LICENSE
	{
		foreach (LICENSE::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
	}
}
