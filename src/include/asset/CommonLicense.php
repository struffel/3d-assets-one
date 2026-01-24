<?php

namespace asset;

use creator\Creator;
use GuzzleHttp\Promise\Create;

enum CommonLicense: int
{
	
	case CC0 = 1;
		#case CC_BY = 2;
		#case CC_BY_SA = 3;
		#case CC_BY_NC = 4;
	case CC_BY_ND = 5;
		#case CC_BY_NC_SA = 6;
		#case CC_BY_NC_ND = 7;
	case APACHE_2_0 = 8;
	case NONE = 0;

	public function getCreators(): array
	{
		$creators = [];
		foreach (Creator::cases() as $c) {
			if ($c->commonLicense() === $this) {
				$creators[] = $c;
			}
		}
		return $creators;
	}

	public function slug(): string
	{
		return match ($this) {
			CommonLicense::NONE => 'none',
			CommonLicense::CC0 => 'cc-0',
			#License::CC_BY => 'cc-by',
			#License::CC_BY_SA => 'cc-by-sa',
			#License::CC_BY_NC => 'cc-by-nc',
			CommonLicense::CC_BY_ND => 'cc-by-nd',
			#License::CC_BY_NC_SA => 'cc-by-nc-sa',
			#License::CC_BY_NC_ND => 'cc-by-nc-nd',
			CommonLicense::APACHE_2_0 => 'apache-2-0',
		};
	}

	public function name(): string
	{
		return match ($this) {
			CommonLicense::NONE => 'Custom License',
			CommonLicense::CC0 => 'Creative Commons CC0',
			#License::CC_BY => 'Creative Commons BY',
			#License::CC_BY_SA => 'Creative Commons BY-SA',
			#License::CC_BY_NC => 'Creative Commons BY-NC',
			CommonLicense::CC_BY_ND => 'Creative Commons BY-ND',
			#License::CC_BY_NC_SA => 'Creative Commons BY-NC-SA',
			#License::CC_BY_NC_ND => 'Creative Commons BY-NC-ND',
			CommonLicense::APACHE_2_0 => 'Apache License 2.0',
		};
	}

	public static function fromSlug(string $slug): ?self
	{
		foreach (CommonLicense::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
		return null;
	}
}
