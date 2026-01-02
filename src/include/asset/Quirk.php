<?php

namespace asset;

enum Quirk: int
{
	case SIGNUP_REQUIRED = 1;
		#case PAYMENT_REQUIRED = 2;
	case ADS = 3;
	case ASSET_PACK = 4;
	case LIMITED_FREE_DOWNLOADS = 5;
	#case LIMITED_FREE_QUALITY = 6;

	public function slug(): string
	{
		return match ($this) {
			self::SIGNUP_REQUIRED => 'sign-up',
			self::ADS => 'ads',
			self::ASSET_PACK => 'asset-pack',
			self::LIMITED_FREE_DOWNLOADS => 'limited-free-downloads'
		};
	}

	public function name(): string
	{
		return match ($this) {
			self::SIGNUP_REQUIRED => 'Sign-up Required',
			self::ADS => 'On-site Ads',
			self::ASSET_PACK => 'Asset Packs',
			self::LIMITED_FREE_DOWNLOADS => 'Limited Number of Free Downloads'
		};
	}

	public static function fromSlug(string $slug): ?self
	{
		foreach (self::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
		return null;
	}
}
