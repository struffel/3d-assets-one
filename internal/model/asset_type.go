package model

// AssetType represents the category of a 3D asset.
type AssetType int

const (
	AssetTypeOther             AssetType = 0
	AssetTypePBRMaterial       AssetType = 1
	AssetTypeModel3D           AssetType = 2
	AssetTypeSubstanceMaterial AssetType = 3
	AssetTypeHDRI              AssetType = 4
)

var allAssetTypes = []AssetType{
	AssetTypeOther,
	AssetTypePBRMaterial,
	AssetTypeModel3D,
	AssetTypeSubstanceMaterial,
	AssetTypeHDRI,
}

func AllAssetTypes() []AssetType { return allAssetTypes }

func (t AssetType) Slug() string {
	switch t {
	case AssetTypeOther:
		return "other"
	case AssetTypePBRMaterial:
		return "pbr-material"
	case AssetTypeModel3D:
		return "3d-model"
	case AssetTypeSubstanceMaterial:
		return "sbsar"
	case AssetTypeHDRI:
		return "hdri"
	default:
		return "other"
	}
}

func (t AssetType) Name() string {
	switch t {
	case AssetTypeOther:
		return "Other"
	case AssetTypePBRMaterial:
		return "PBR material"
	case AssetTypeModel3D:
		return "3D model"
	case AssetTypeSubstanceMaterial:
		return "Substance material"
	case AssetTypeHDRI:
		return "HDRI"
	default:
		return "Other"
	}
}

func AssetTypeFromSlug(slug string) AssetType {
	t, ok := AssetTypeTryFromSlug(slug)
	if !ok {
		return AssetTypeOther
	}
	return t
}

func AssetTypeTryFromSlug(slug string) (AssetType, bool) {
	for _, t := range allAssetTypes {
		if t.Slug() == slug {
			return t, true
		}
	}
	return AssetTypeOther, false
}

func AssetTypeFromTex1Tag(tag string) AssetType {
	switch tag {
	case "pbr-scanned", "pbr-procedural", "pbr-approximated", "pbr-multiangle", "pbr-stereo":
		return AssetTypePBRMaterial
	case "sbsar-procedural":
		return AssetTypeSubstanceMaterial
	case "hdri-real":
		return AssetTypeHDRI
	case "3d-modeled", "3d-scanned", "3d-models":
		return AssetTypeModel3D
	default:
		return AssetTypeOther
	}
}
