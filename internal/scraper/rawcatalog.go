package scraper

import (
	"context"
	"encoding/xml"
	"fmt"
	"log/slog"

	"github.com/struffel/3d-assets-one/internal/model"
)

type RawCatalogScraper struct{}

func (s *RawCatalogScraper) Creator() model.Creator { return model.CreatorRawCatalog }

func (s *RawCatalogScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 25
	const apiURL = "https://rawcatalog.com/freeset.xml"

	body, _, err := FetchURL(ctx, apiURL)
	if err != nil {
		return nil, fmt.Errorf("rawcatalog fetch: %w", err)
	}

	// Parse XML
	var catalog rawCatalogXML
	if err := xml.Unmarshal([]byte(body), &catalog); err != nil {
		return nil, fmt.Errorf("rawcatalog xml: %w", err)
	}

	typeMapping := map[string]model.AssetType{
		"blueprints": model.AssetTypeOther,
		"materials":  model.AssetTypePBRMaterial,
		"atlases":    model.AssetTypePBRMaterial,
		"models":     model.AssetTypeModel3D,
	}

	var results []ScrapedAsset
	for _, section := range catalog.Sections {
		at, ok := typeMapping[section.XMLName.Local]
		if !ok {
			continue
		}

		for _, f := range section.Files {
			if len(results) >= maxPerRun {
				return results, nil
			}
			if ContainsURL(existing, f.URL) {
				continue
			}

			var tags []string
			for _, t := range f.Tags.Tag {
				tags = append(tags, t)
			}

			img, err := FetchImage(ctx, f.Cover)
			if err != nil {
				slog.Warn("rawcatalog: thumbnail failed", "url", f.URL, "err", err)
				continue
			}

			results = append(results, ScrapedAsset{
				Title:        f.Name,
				URL:          f.URL,
				Tags:         tags,
				Type:         at,
				Creator:      model.CreatorRawCatalog,
				Status:       model.ScrapedNewlyFound,
				RawThumbnail: img,
			})
		}
	}

	return results, nil
}

func (s *RawCatalogScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}

// XML structures for RawCatalog
type rawCatalogXML struct {
	XMLName  xml.Name            `xml:"freeset"`
	Sections []rawCatalogSection `xml:",any"`
}

type rawCatalogSection struct {
	XMLName xml.Name         `xml:""`
	Files   []rawCatalogFile `xml:"file"`
}

type rawCatalogFile struct {
	Name  string         `xml:"name"`
	URL   string         `xml:"url"`
	Cover string         `xml:"cover"`
	Tags  rawCatalogTags `xml:"tags"`
}

type rawCatalogTags struct {
	Tag []string `xml:"tag"`
}
