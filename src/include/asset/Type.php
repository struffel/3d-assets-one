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
			TYPE::OTHER => 'other',
			TYPE::PBR_MATERIAL => 'pbr-material',
			TYPE::MODEL_3D => '3d-model',
			TYPE::SUBSTANCE_MATERIAL => 'sbsar',
			TYPE::HDRI => 'hdri',
		};
	}

	public function name(): string
	{
		return match ($this) {
			TYPE::OTHER => 'Other',
			TYPE::PBR_MATERIAL => 'PBR material',
			TYPE::MODEL_3D => '3D model',
			TYPE::SUBSTANCE_MATERIAL => 'Substance material',
			TYPE::HDRI => 'HDRI',
		};
	}

	public static function fromSlug(string $slug): Type
	{
		foreach (TYPE::cases() as $c) {
			if ($c->slug() === $slug) {
				return $c;
			}
		}
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
