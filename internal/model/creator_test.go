package model

import (
	"testing"
)

func TestAllCreatorsHaveNonEmptyTitle(t *testing.T) {
	for _, c := range AllCreators() {
		if c.Title() == "" {
			t.Errorf("Creator %d has empty title", int(c))
		}
	}
}

func TestAllCreatorsHaveNonEmptyDescription(t *testing.T) {
	for _, c := range AllCreators() {
		if c.Description() == "" {
			t.Errorf("Creator %d (%s) has empty description", int(c), c.Slug())
		}
	}
}

func TestAllCreatorsHaveValidBaseURL(t *testing.T) {
	for _, c := range AllCreators() {
		url := c.BaseURL()
		if len(url) < 8 || (url[:7] != "http://" && url[:8] != "https://") {
			t.Errorf("Creator %s has invalid base URL: %s", c.Slug(), url)
		}
	}
}

func TestCreatorFromSlugRoundTrip(t *testing.T) {
	for _, c := range AllCreators() {
		slug := c.Slug()
		got, ok := CreatorFromSlug(slug)
		if !ok {
			t.Errorf("CreatorFromSlug(%q) not found", slug)
			continue
		}
		if got != c {
			t.Errorf("CreatorFromSlug(%q) = %d, want %d", slug, int(got), int(c))
		}
	}
}

func TestCreatorFromValueRoundTrip(t *testing.T) {
	for _, c := range AllCreators() {
		got, ok := CreatorFromValue(int(c))
		if !ok {
			t.Errorf("CreatorFromValue(%d) not found", int(c))
			continue
		}
		if got != c {
			t.Errorf("CreatorFromValue(%d) = %d, want %d", int(c), int(got), int(c))
		}
	}
}

func TestCreatorFromSlugInvalidReturnsNotOk(t *testing.T) {
	_, ok := CreatorFromSlug("nonexistent-slug")
	if ok {
		t.Error("CreatorFromSlug(nonexistent) should return false")
	}
}

func TestCreatorSlugsAreUnique(t *testing.T) {
	seen := make(map[string]bool)
	for _, c := range AllCreators() {
		slug := c.Slug()
		if seen[slug] {
			t.Errorf("Duplicate creator slug: %s", slug)
		}
		seen[slug] = true
	}
}
