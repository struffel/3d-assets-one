package model

import (
	"testing"
)

func TestAssetTypeSlugsAreUnique(t *testing.T) {
	seen := make(map[string]bool)
	for _, at := range AllAssetTypes() {
		slug := at.Slug()
		if seen[slug] {
			t.Errorf("Duplicate AssetType slug: %s", slug)
		}
		seen[slug] = true
	}
}

func TestAssetTypeFromSlugRoundTrip(t *testing.T) {
	for _, at := range AllAssetTypes() {
		slug := at.Slug()
		got, ok := AssetTypeTryFromSlug(slug)
		if !ok {
			t.Errorf("AssetTypeTryFromSlug(%q) not found", slug)
			continue
		}
		if got != at {
			t.Errorf("AssetTypeTryFromSlug(%q) = %d, want %d", slug, int(got), int(at))
		}
	}
}

func TestAssetTypeTryFromSlugInvalid(t *testing.T) {
	_, ok := AssetTypeTryFromSlug("nonexistent")
	if ok {
		t.Error("AssetTypeTryFromSlug(nonexistent) should return false")
	}
}

func TestAssetTypeTryFromSlugEmpty(t *testing.T) {
	_, ok := AssetTypeTryFromSlug("")
	if ok {
		t.Error("AssetTypeTryFromSlug('') should return false")
	}
}

func TestCreatorLicenseTypeSlugsAreUnique(t *testing.T) {
	seen := make(map[string]bool)
	for _, l := range AllCreatorLicenseTypes() {
		slug := l.Slug()
		if seen[slug] {
			t.Errorf("Duplicate CreatorLicenseType slug: %s", slug)
		}
		seen[slug] = true
	}
}

func TestCreatorLicenseTypeFromSlugRoundTrip(t *testing.T) {
	for _, l := range AllCreatorLicenseTypes() {
		slug := l.Slug()
		got, ok := CreatorLicenseTypeTryFromSlug(slug)
		if !ok {
			t.Errorf("CreatorLicenseTypeTryFromSlug(%q) not found", slug)
			continue
		}
		if got != l {
			t.Errorf("CreatorLicenseTypeTryFromSlug(%q) = %d, want %d", slug, int(got), int(l))
		}
	}
}

func TestCreatorLicenseTypeTryFromSlugInvalid(t *testing.T) {
	_, ok := CreatorLicenseTypeTryFromSlug("nonexistent")
	if ok {
		t.Error("should return false for invalid slug")
	}
}

func TestCreatorLicenseTypeTryFromSlugEmpty(t *testing.T) {
	_, ok := CreatorLicenseTypeTryFromSlug("")
	if ok {
		t.Error("should return false for empty slug")
	}
}

func TestAssetSortingFromSlugRoundTrip(t *testing.T) {
	for _, s := range AllSortings() {
		got := AssetSortingFromSlug(string(s))
		if got != s {
			t.Errorf("AssetSortingFromSlug(%q) = %q, want %q", string(s), string(got), string(s))
		}
	}
}

func TestAssetSortingFromSlugInvalidReturnsDefault(t *testing.T) {
	got := AssetSortingFromSlug("nonexistent")
	if got != SortLatest {
		t.Errorf("AssetSortingFromSlug(nonexistent) = %q, want %q", string(got), string(SortLatest))
	}
}
