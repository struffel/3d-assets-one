package scraper

import (
	"context"
	"fmt"
	"log/slog"

	"github.com/struffel/3d-assets-one/internal/model"
)

type PolyhavenScraper struct{}

func (s *PolyhavenScraper) Creator() model.Creator { return model.CreatorPolyhaven }

var phTypeMapping = map[int]model.AssetType{
	0: model.AssetTypeHDRI,
	1: model.AssetTypePBRMaterial,
	2: model.AssetTypeModel3D,
}

func (s *PolyhavenScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 10
	const apiURL = "https://api.polyhaven.com/assets"
	const viewBase = "https://polyhaven.com/a/"
	const thumbPrefix = "https://cdn.polyhaven.com/asset_img/thumbs/"
	const thumbSuffix = ".png?height=512"

	var resp map[string]struct {
		Name          string   `json:"name"`
		Tags          []string `json:"tags"`
		Type          int      `json:"type"`
		DatePublished int64    `json:"date_published"`
	}

	if _, err := FetchJSON(ctx, apiURL, &resp); err != nil {
		return nil, fmt.Errorf("polyhaven api: %w", err)
	}

	var results []ScrapedAsset
	for key, ph := range resp {
		if len(results) >= maxPerRun {
			break
		}

		url := viewBase + key
		if ContainsURL(existing, url) {
			continue
		}

		img, err := FetchImage(ctx, thumbPrefix+key+thumbSuffix)
		if err != nil {
			slog.Warn("polyhaven: thumbnail fetch failed", "key", key, "err", err)
			continue
		}

		at := phTypeMapping[ph.Type]
		results = append(results, ScrapedAsset{
			Title:        ph.Name,
			URL:          url,
			Tags:         ph.Tags,
			Type:         at,
			Creator:      model.CreatorPolyhaven,
			Status:       model.ScrapedNewlyFound,
			RawThumbnail: img,
		})
	}

	return results, nil
}

func (s *PolyhavenScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
