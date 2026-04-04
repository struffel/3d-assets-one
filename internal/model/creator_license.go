package model

// CreatorLicenseType classifies the license openness of a creator's assets.
type CreatorLicenseType int

const (
	LicensePublicDomain CreatorLicenseType = 1
	LicenseOpenLicense  CreatorLicenseType = 2
	LicenseAnyLicense   CreatorLicenseType = 3
)

var allLicenseTypes = []CreatorLicenseType{
	LicensePublicDomain,
	LicenseOpenLicense,
	LicenseAnyLicense,
}

func AllCreatorLicenseTypes() []CreatorLicenseType { return allLicenseTypes }

func (l CreatorLicenseType) Title() string {
	switch l {
	case LicensePublicDomain:
		return "Public Domain Only"
	case LicenseOpenLicense:
		return "Open License"
	case LicenseAnyLicense:
		return "Any License"
	default:
		return "Any License"
	}
}

func (l CreatorLicenseType) Slug() string {
	switch l {
	case LicensePublicDomain:
		return "public-domain"
	case LicenseOpenLicense:
		return "open"
	case LicenseAnyLicense:
		return "any"
	default:
		return "any"
	}
}

func CreatorLicenseTypeTryFromSlug(slug string) (CreatorLicenseType, bool) {
	for _, l := range allLicenseTypes {
		if l.Slug() == slug {
			return l, true
		}
	}
	return 0, false
}

func CreatorLicenseTypeFromSlug(slug string) CreatorLicenseType {
	l, ok := CreatorLicenseTypeTryFromSlug(slug)
	if !ok {
		return LicenseAnyLicense
	}
	return l
}
