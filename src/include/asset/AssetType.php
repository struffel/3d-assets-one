<?php

namespace asset;

enum AssetType: int
{
	case OTHER = 0;
	case PBR_MATERIAL = 1;
	case MODEL_3D = 2;
	case SUBSTANCE_MATERIAL = 3;
	case HDRI = 4;

	public function slug(): string
	{
		return match ($this) {
			AssetType::OTHER => 'other',
			AssetType::PBR_MATERIAL => 'pbr-material',
			AssetType::MODEL_3D => '3d-model',
			AssetType::SUBSTANCE_MATERIAL => 'sbsar',
			AssetType::HDRI => 'hdri',
		};
	}

	public function name(): string
	{
		return match ($this) {
			AssetType::OTHER => 'Other',
			AssetType::PBR_MATERIAL => 'PBR material',
			AssetType::MODEL_3D => '3D model',
			AssetType::SUBSTANCE_MATERIAL => 'Substance material',
			AssetType::HDRI => 'HDRI',
		};
	}

	public static function fromSlug(string $slug): AssetType
	{
		foreach (AssetType::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
		return AssetType::OTHER;
	}

	public static function fromTex1Tag(string $tex1Tag): AssetType
	{
		return match ($tex1Tag) {
			"pbr-scanned", "pbr-procedural", "pbr-approximated", "pbr-multiangle", "pbr-stereo" => AssetType::PBR_MATERIAL,
			"sbsar-procedural" => AssetType::SUBSTANCE_MATERIAL,
			"hdri-real" => AssetType::HDRI,
			"3d-modeled", "3d-scanned", "3d-models" => AssetType::MODEL_3D,
			default => AssetType::OTHER,
		};
	}
}
