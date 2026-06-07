package scraper

import (
	"context"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"regexp"
	"strings"

	"github.com/struffel/3d-assets-one/internal/model"
)

type PbrPxScraper struct{}

func (s *PbrPxScraper) Creator() model.Creator { return model.CreatorPbrPx }

func (s *PbrPxScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 25
	const indexBaseURL = "https://api.pbrpx.com/home/api_product/getPmsg?page_number="
	const assetAPIURL = "https://api.pbrpx.com/home/api_product/getasset"
	const viewBaseURL = "https://library.pbrpx.com/#/asset?asset="
	const mediaBaseURL = "https://asset.pbrpx.com/"
	const thumbID = "preview_360_360"

	var results []ScrapedAsset
	page := 1
	tagRe := regexp.MustCompile(`[^A-Za-z0-9]`)

	for len(results) < maxPerRun {
		var listResp struct {
			Data struct {
				Data []struct {
					ID int `json:"id"`
				} `json:"data"`
			} `json:"data"`
		}

		if _, err := FetchJSON(ctx, fmt.Sprintf("%s%d", indexBaseURL, page), &listResp); err != nil {
			break
		}
		if len(listResp.Data.Data) == 0 {
			break
		}

		for _, item := range listResp.Data.Data {
			if len(results) >= maxPerRun {
				break
			}

			assetURL := fmt.Sprintf("%s%d", viewBaseURL, item.ID)
			if ContainsURL(existing, assetURL) {
				continue
			}

			// Fetch asset details via POST
			detailResp, err := fetchPbrPxDetail(ctx, assetAPIURL, fmt.Sprintf("%d", item.ID))
			if err != nil || len(detailResp) == 0 {
				continue
			}
			detail := detailResp[0]

			// Tags from english name
			parts := tagRe.Split(detail.Ename, -1)
			var tags []string
			for _, p := range parts {
				if p != "" {
					tags = append(tags, p)
				}
			}

			// Type
			at := model.AssetTypeOther
			if strings.HasPrefix(detail.Zips, "HDRI") {
				at = model.AssetTypeHDRI
			} else if strings.HasPrefix(detail.Zips, "Textures") {
				at = model.AssetTypePBRMaterial
			} else if strings.HasPrefix(detail.Zips, "3D_Model") {
				at = model.AssetTypeModel3D
			}

			// Thumbnail
			candidates := strings.Split(detail.ImgURL, "+")
			thumbURL := mediaBaseURL + candidates[0]
			for _, c := range candidates {
				if strings.Contains(c, thumbID) {
					thumbURL = mediaBaseURL + c
				}
			}

			img, err := FetchImage(ctx, thumbURL)
			if err != nil {
				slog.Warn("pbrpx: thumbnail fetch failed", "url", assetURL, "err", err)
				continue
			}

			results = append(results, ScrapedAsset{
				Title:        detail.Ename,
				URL:          assetURL,
				Tags:         tags,
				Type:         at,
				Creator:      model.CreatorPbrPx,
				Status:       model.ScrapedNewlyFound,
				RawThumbnail: img,
			})
		}

		page++
	}

	return results, nil
}

type pbrPxDetail struct {
	Ename  string `json:"ename"`
	Zips   string `json:"zips"`
	ImgURL string `json:"img_url"`
}

func fetchPbrPxDetail(ctx context.Context, apiURL, assetID string) ([]pbrPxDetail, error) {
	body := fmt.Sprintf(`{"asset":"%s"}`, assetID)
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, apiURL, strings.NewReader(body))
	if err != nil {
		return nil, err
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("User-Agent", userAgent)

	resp, err := httpClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	var result struct {
		Errno int           `json:"errno"`
		Data  []pbrPxDetail `json:"data"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return nil, err
	}
	return result.Data, nil
}

func (s *PbrPxScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	// Extract ID from URL and check via API
	parts := strings.SplitN(asset.URL, "=", 2)
	if len(parts) < 2 {
		return false
	}
	detail, err := fetchPbrPxDetail(ctx, "https://api.pbrpx.com/home/api_product/getasset", parts[1])
	return err == nil && len(detail) > 0
}
