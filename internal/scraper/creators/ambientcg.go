package scraper

import (
	"context"
	"fmt"
	"log/slog"
	"strings"

	"github.com/struffel/3d-assets-one/internal/model"
)

type AmbientCGScraper struct{}

func (s *AmbientCGScraper) Creator() model.Creator { return model.CreatorAmbientCG }

var acgTypeMapping = map[string]model.AssetType{
	"Material":       model.AssetTypePBRMaterial,
	"Decal":          model.AssetTypePBRMaterial,
	"Atlas":          model.AssetTypePBRMaterial,
	"HDRI":           model.AssetTypeHDRI,
	"3DModel":        model.AssetTypeModel3D,
	"SculptingBrush": model.AssetTypeOther,
	"Terrain":        model.AssetTypeOther,
	"SBSAR":          model.AssetTypeSubstanceMaterial,
	"Substance":      model.AssetTypeSubstanceMaterial,
	"PlainTexture":   model.AssetTypePBRMaterial,
	"Brush":          model.AssetTypeOther,
	"HDRIElement":    model.AssetTypeHDRI,
}

func (s *AmbientCGScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 25
	const apiURL = "https://ambientcg.com/api/v2/full_json?limit=100&offset=0&include=displayData,tagData,imageData"

	var results []ScrapedAsset
	targetURL := apiURL

	for targetURL != "" && len(results) < maxPerRun {
		var resp struct {
			FoundAssets []struct {
				ShortLink    string   `json:"shortLink"`
				AssetID      string   `json:"assetId"`
				DisplayName  string   `json:"displayName"`
				Tags         []string `json:"tags"`
				DataType     string   `json:"dataType"`
				PreviewImage struct {
					PNG512 string `json:"512-PNG"`
				} `json:"previewImage"`
			} `json:"foundAssets"`
			NextPageHTTP string `json:"nextPageHttp"`
		}

		if _, err := FetchJSON(ctx, targetURL, &resp); err != nil {
			return results, fmt.Errorf("ambientcg api: %w", err)
		}

		for _, a := range resp.FoundAssets {
			if len(results) >= maxPerRun {
				break
			}
			if ContainsURL(existing, strings.ToLower(a.ShortLink)) {
				continue
			}

			img, err := FetchImage(ctx, a.PreviewImage.PNG512)
			if err != nil {
				slog.Warn("ambientcg: thumbnail fetch failed", "url", a.ShortLink, "err", err)
				continue
			}

			at := acgTypeMapping[a.DataType]
			cgid := a.AssetID
			results = append(results, ScrapedAsset{
				CreatorGivenID: &cgid,
				Title:          a.DisplayName,
				URL:            a.ShortLink,
				Tags:           a.Tags,
				Type:           at,
				Creator:        model.CreatorAmbientCG,
				Status:         model.ScrapedNewlyFound,
				RawThumbnail:   img,
			})
		}

		targetURL = resp.NextPageHTTP
	}

	return results, nil
}

func (s *AmbientCGScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
