package model

import (
	"image"
	"time"
)

// StoredAsset represents an asset persisted in the database.
type StoredAsset struct {
	ID                       int64
	CreatorGivenID           *string
	Title                    string
	URL                      string
	Date                     time.Time
	Type                     AssetType
	Creator                  Creator
	Tags                     []string
	Status                   StoredAssetStatus
	Clicks                   int
	PopularityScore          float64
	LastSuccessfulValidation *time.Time
}

// ThumbnailURL returns the URL path for a thumbnail of this asset.
func (a *StoredAsset) ThumbnailURL(format ThumbnailFormat, cdnBaseURL string) string {
	ext := format.Extension()
	return cdnBaseURL + "/thumbnail/" + format.Value() + "/" + itoa(a.ID) + "." + ext
}

// APIRepresentation returns a map suitable for JSON serialization.
func (a *StoredAsset) APIRepresentation(format ThumbnailFormat, cdnBaseURL string) map[string]any {
	return map[string]any{
		"id":             a.ID,
		"creatorGivenId": a.CreatorGivenID,
		"title":          a.Title,
		"url":            a.URL,
		"date":           a.Date.Format(time.RFC3339),
		"type":           a.Type.Slug(),
		"creator":        a.Creator.Slug(),
		"tags":           a.Tags,
		"thumbnail":      a.ThumbnailURL(format, cdnBaseURL),
	}
}

func itoa(i int64) string {
	if i == 0 {
		return "0"
	}
	neg := false
	if i < 0 {
		neg = true
		i = -i
	}
	buf := [20]byte{}
	pos := len(buf)
	for i > 0 {
		pos--
		buf[pos] = byte('0' + i%10)
		i /= 10
	}
	if neg {
		pos--
		buf[pos] = '-'
	}
	return string(buf[pos:])
}

// ScrapedAsset is a temporary asset obtained from scraping, before persistence.
type ScrapedAsset struct {
	ID             *int64
	CreatorGivenID *string
	Title          string
	URL            string
	Type           AssetType
	Creator        Creator
	Tags           []string
	Status         ScrapedAssetStatus
	RawThumbnail   image.Image
}

// ToStoredAsset converts a scraped asset to a stored asset.
func (s *ScrapedAsset) ToStoredAsset() *StoredAsset {
	now := time.Now()
	var id int64
	if s.ID != nil {
		id = *s.ID
	}
	return &StoredAsset{
		ID:                       id,
		CreatorGivenID:           s.CreatorGivenID,
		Title:                    s.Title,
		URL:                      s.URL,
		Date:                     now,
		Type:                     s.Type,
		Creator:                  s.Creator,
		Tags:                     s.Tags,
		Status:                   s.Status.ToStoredAssetStatus(),
		LastSuccessfulValidation: nil,
	}
}

// StoredAssetCollection holds a slice of stored assets plus pagination info.
type StoredAssetCollection struct {
	Assets    []*StoredAsset
	NextQuery *string // serialized query string for next page, or nil
}

// ContainsURL checks if any asset in the collection has the given URL (case-insensitive).
func (c *StoredAssetCollection) ContainsURL(url string) bool {
	lower := toLower(url)
	for _, a := range c.Assets {
		if toLower(a.URL) == lower {
			return true
		}
	}
	return false
}

func toLower(s string) string {
	b := make([]byte, len(s))
	for i := 0; i < len(s); i++ {
		c := s[i]
		if c >= 'A' && c <= 'Z' {
			c += 'a' - 'A'
		}
		b[i] = c
	}
	return string(b)
}
