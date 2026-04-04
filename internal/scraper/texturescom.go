package scraper

import (
	"context"
	"fmt"
	"log/slog"
	"regexp"

	"github.com/struffel/3d-assets-one/internal/model"
)

type TexturesComScraper struct{}

func (s *TexturesComScraper) Creator() model.Creator { return model.CreatorTexturesCom }

var texComCategoryMap = map[int]model.AssetType{
	114553: model.AssetTypeModel3D,
	114561: model.AssetTypeOther,
	114548: model.AssetTypePBRMaterial,
	114563: model.AssetTypePBRMaterial,
	114570: model.AssetTypeModel3D,
	114558: model.AssetTypePBRMaterial,
	114557: model.AssetTypeOther,
	114552: model.AssetTypeHDRI,
	23740:  model.AssetTypeHDRI,
	114568: model.AssetTypeOther,
	114571: model.AssetTypeOther,
	114579: model.AssetTypeModel3D,
	114590: model.AssetTypeModel3D,
	114576: model.AssetTypeModel3D,
}

func (s *TexturesComScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 25
	const apiBase = "https://www.textures.com/api/v1/texture/search?filter=free&page="

	var results []ScrapedAsset
	tagRe := regexp.MustCompile(`[^A-Za-z0-9]`)
	page := 1

	for page < 20 { // failsafe
		var resp struct {
			Data []struct {
				FilenameWithoutSet string `json:"filenameWithoutSet"`
				DefaultCategoryID  int    `json:"defaultCategoryId"`
				Picture            string `json:"picture"`
				DefaultPhotoSet    struct {
					ID             int    `json:"id"`
					TitleThumbnail string `json:"titleThumbnail"`
				} `json:"defaultPhotoSet"`
			} `json:"data"`
		}

		if _, err := FetchJSON(ctx, fmt.Sprintf("%s%d", apiBase, page), &resp); err != nil {
			return results, fmt.Errorf("textures.com api: %w", err)
		}
		if len(resp.Data) == 0 {
			break
		}

		for _, a := range resp.Data {
			if len(results) >= maxPerRun {
				return results, nil
			}

			url := fmt.Sprintf("https://textures.com/download/%s/%d", a.FilenameWithoutSet, a.DefaultPhotoSet.ID)
			if ContainsURL(existing, url) {
				continue
			}

			parts := tagRe.Split(a.DefaultPhotoSet.TitleThumbnail, -1)
			var tags []string
			for _, p := range parts {
				if p != "" {
					tags = append(tags, p)
				}
			}

			at := texComCategoryMap[a.DefaultCategoryID]

			img, err := FetchImage(ctx, "https://textures.com/"+a.Picture)
			if err != nil {
				slog.Warn("textures.com: thumbnail failed", "url", url, "err", err)
				continue
			}

			results = append(results, ScrapedAsset{
				Title:        a.DefaultPhotoSet.TitleThumbnail,
				URL:          url,
				Tags:         tags,
				Type:         at,
				Creator:      model.CreatorTexturesCom,
				Status:       model.ScrapedNewlyFound,
				RawThumbnail: img,
			})
		}

		page++
	}

	return results, nil
}

func (s *TexturesComScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
