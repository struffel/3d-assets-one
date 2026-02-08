<?php

namespace asset;

use misc\Slug;

enum AssetType: int
{

	use Slug;

	case OTHER = 0;
	case PBR_MATERIAL = 1;
	case MODEL_3D = 2;
	case SUBSTANCE_MATERIAL = 3;
	case HDRI = 4;

	public function slug(): string
	{
		return match ($this) {
			self::OTHER => 'other',
			self::PBR_MATERIAL => 'pbr-material',
			self::MODEL_3D => '3d-model',
			self::SUBSTANCE_MATERIAL => 'sbsar',
			self::HDRI => 'hdri',
		};
	}

	public function name(): string
	{
		return match ($this) {
			self::OTHER => 'Other',
			self::PBR_MATERIAL => 'PBR material',
			self::MODEL_3D => '3D model',
			self::SUBSTANCE_MATERIAL => 'Substance material',
			self::HDRI => 'HDRI',
		};
	}

	public static function fromSlug(string $slug): self
	{
		return self::tryFromSlug($slug) ?? self::OTHER;
	}

	public static function fromTex1Tag(?string $tex1Tag): self
	{
		return match ($tex1Tag) {
			"pbr-scanned", "pbr-procedural", "pbr-approximated", "pbr-multiangle", "pbr-stereo" => self::PBR_MATERIAL,
			"sbsar-procedural" => self::SUBSTANCE_MATERIAL,
			"hdri-real" => self::HDRI,
			"3d-modeled", "3d-scanned", "3d-models" => self::MODEL_3D,
			default => self::OTHER,
		};
	}
}
