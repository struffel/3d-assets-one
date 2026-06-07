package scraper

import (
	"context"
	"fmt"
	"log/slog"
	"strings"

	"github.com/struffel/3d-assets-one/internal/model"
)

type AmdGpuOpenScraper struct{}

func (s *AmdGpuOpenScraper) Creator() model.Creator { return model.CreatorGPUOpenMatLib }

func (s *AmdGpuOpenScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 5
	const apiURL = "https://api.matlib.gpuopen.com/api/materials/?limit=50&ordering=-published_date&status=Published&updateKey=1&offset=0"
	const tagAPIURL = "https://api.matlib.gpuopen.com/api/tags/"
	const urlTemplate = "https://matlib.gpuopen.com/main/materials/all?material=#ID#"
	const previewTemplate = "https://image.matlib.gpuopen.com/#ID#.jpeg"

	var results []ScrapedAsset
	targetURL := apiURL

	for targetURL != "" && len(results) < maxPerRun {
		var resp struct {
			Results []struct {
				ID           string   `json:"id"`
				Title        string   `json:"title"`
				Tags         []string `json:"tags"`
				RendersOrder []string `json:"renders_order"`
			} `json:"results"`
			Next *string `json:"next"`
		}

		if _, err := FetchJSON(ctx, targetURL, &resp); err != nil {
			return results, fmt.Errorf("amd gpuopen api: %w", err)
		}

		for _, a := range resp.Results {
			if len(results) >= maxPerRun {
				break
			}
			// Exclude title starting with "TH: "
			if strings.HasPrefix(a.Title, "TH: ") {
				continue
			}

			url := strings.ReplaceAll(urlTemplate, "#ID#", a.ID)
			if ContainsURL(existing, url) {
				continue
			}

			// Fetch tags
			var tags []string
			for _, tagID := range a.Tags {
				var tagResp struct {
					Title string `json:"title"`
				}
				if _, err := FetchJSON(ctx, tagAPIURL+tagID, &tagResp); err == nil {
					tags = append(tags, tagResp.Title)
				}
			}

			// Thumbnail from first render
			thumbURL := previewTemplate
			if len(a.RendersOrder) > 0 {
				thumbURL = strings.ReplaceAll(thumbURL, "#ID#", a.RendersOrder[0])
			} else {
				thumbURL = strings.ReplaceAll(thumbURL, "#ID#", a.ID)
			}

			img, err := FetchImage(ctx, thumbURL)
			if err != nil {
				slog.Warn("amd gpuopen: thumbnail fetch failed", "id", a.ID, "err", err)
				continue
			}

			cgid := a.ID
			results = append(results, ScrapedAsset{
				CreatorGivenID: &cgid,
				Title:          a.Title,
				URL:            url,
				Tags:           tags,
				Type:           model.AssetTypePBRMaterial,
				Creator:        model.CreatorGPUOpenMatLib,
				Status:         model.ScrapedNewlyFound,
				RawThumbnail:   img,
			})
		}

		if resp.Next != nil {
			targetURL = *resp.Next
		} else {
			targetURL = ""
		}
	}

	return results, nil
}

func (s *AmdGpuOpenScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
