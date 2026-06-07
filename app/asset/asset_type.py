from enum import IntEnum
from typing import Optional


class AssetType(IntEnum):
    OTHER = 0
    PBR_MATERIAL = 1
    MODEL_3D = 2
    SUBSTANCE_MATERIAL = 3
    HDRI = 4

    def slug(self) -> str:
        return {
            AssetType.OTHER: "other",
            AssetType.PBR_MATERIAL: "pbr-material",
            AssetType.MODEL_3D: "3d-model",
            AssetType.SUBSTANCE_MATERIAL: "sbsar",
            AssetType.HDRI: "hdri",
        }[self]

    def label(self) -> str:
        return {
            AssetType.OTHER: "Other",
            AssetType.PBR_MATERIAL: "PBR material",
            AssetType.MODEL_3D: "3D model",
            AssetType.SUBSTANCE_MATERIAL: "Substance material",
            AssetType.HDRI: "HDRI",
        }[self]

    @classmethod
    def from_slug(cls, slug: str) -> "AssetType":
        return cls.try_from_slug(slug) or cls.OTHER

    @classmethod
    def try_from_slug(cls, slug: str) -> Optional["AssetType"]:
        for member in cls:
            if member.slug() == slug:
                return member
        return None

    @classmethod
    def from_tex1_tag(cls, tex1_tag: str | None) -> "AssetType":
        mapping: dict[str, "AssetType"] = {
            "pbr-scanned": cls.PBR_MATERIAL,
            "pbr-procedural": cls.PBR_MATERIAL,
            "pbr-approximated": cls.PBR_MATERIAL,
            "pbr-multiangle": cls.PBR_MATERIAL,
            "pbr-stereo": cls.PBR_MATERIAL,
            "sbsar-procedural": cls.SUBSTANCE_MATERIAL,
            "hdri-real": cls.HDRI,
            "3d-modeled": cls.MODEL_3D,
            "3d-scanned": cls.MODEL_3D,
            "3d-models": cls.MODEL_3D,
        }
        return mapping.get(tex1_tag or "", cls.OTHER)
