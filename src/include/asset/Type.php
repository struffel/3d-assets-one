<?php

namespace asset;

enum Type: int
{
	case OTHER = 0;
	case PBR_MATERIAL = 1;
	case MODEL_3D = 2;
	case SUBSTANCE_MATERIAL = 3;
	case HDRI = 4;

	public function slug(): string
	{
		return match ($this) {
			Type::OTHER => 'other',
			Type::PBR_MATERIAL => 'pbr-material',
			Type::MODEL_3D => '3d-model',
			Type::SUBSTANCE_MATERIAL => 'sbsar',
			Type::HDRI => 'hdri',
		};
	}

	public function name(): string
	{
		return match ($this) {
			Type::OTHER => 'Other',
			Type::PBR_MATERIAL => 'PBR material',
			Type::MODEL_3D => '3D model',
			Type::SUBSTANCE_MATERIAL => 'Substance material',
			Type::HDRI => 'HDRI',
		};
	}

	public static function fromSlug(string $slug): Type
	{
		foreach (Type::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
		return Type::OTHER;
	}

	public static function fromTex1Tag(string $tex1Tag): Type
	{
		return match ($tex1Tag) {
			"pbr-scanned", "pbr-procedural", "pbr-approximated", "pbr-multiangle", "pbr-stereo" => Type::PBR_MATERIAL,
			"sbsar-procedural" => Type::SUBSTANCE_MATERIAL,
			"hdri-real" => Type::HDRI,
			"3d-modeled", "3d-scanned", "3d-models" => Type::MODEL_3D,
			default => Type::OTHER,
		};
	}
}
