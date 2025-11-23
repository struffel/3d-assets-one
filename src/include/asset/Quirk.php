<?php

namespace creator;

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
			QUIRK::SIGNUP_REQUIRED => 'sign-up',
			QUIRK::ADS => 'ads',
			QUIRK::ASSET_PACK => 'asset-pack',
			QUIRK::LIMITED_FREE_DOWNLOADS => 'limited-free-downloads'
		};
	}

	public function name(): string
	{
		return match ($this) {
			QUIRK::SIGNUP_REQUIRED => 'Sign-up Required',
			QUIRK::ADS => 'On-site Ads',
			QUIRK::ASSET_PACK => 'Asset Packs',
			QUIRK::LIMITED_FREE_DOWNLOADS => 'Limited Number of Free Downloads'
		};
	}

	public static function fromSlug(string $slug): QUIRK
	{
		foreach (QUIRK::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
	}
}
