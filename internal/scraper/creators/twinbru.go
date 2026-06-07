package scraper

import (
	"context"
	"database/sql"
	"fmt"
	"log/slog"
	"net/url"
	"regexp"
	"strconv"
	"strings"

	"github.com/struffel/3d-assets-one/internal/database"
	"github.com/struffel/3d-assets-one/internal/model"
)

type TwinbruScraper struct {
	DB *sql.DB
}

func (s *TwinbruScraper) Creator() model.Creator { return model.CreatorTwinbru }

func (s *TwinbruScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const indexBase = "https://textures.twinbru.com/api/ods/products"
	const viewBase = "https://textures.twinbru.com/products/"
	const thumbQueryBase = "https://textures.twinbru.com/api/ods/assets"
	const thumbBase = "https://cdn.twinbru.com/ods/assets/"

	tagRe := regexp.MustCompile(`[^A-Za-z0-9%]`)

	pageStr, _ := database.GetCreatorState(s.DB, int(model.CreatorTwinbru), "page")
	page, _ := strconv.Atoi(pageStr)
	if page < 1 {
		page = 1
	}

	params := url.Values{
		"pageSize":      {"50"},
		"sortAttribute": {"launch"},
		"sortDirection": {"DSC"},
		"prefilter":     {"status.eq.RN/bvs_special.ne.any(customer%20special,treatment%20special)/has3dTexture.eq.True"},
		"page":          {strconv.Itoa(page)},
	}

	var resp struct {
		Results []struct {
			Item map[string]interface{} `json:"item"`
		} `json:"results"`
	}

	apiURL := indexBase + "?" + params.Encode()
	if _, err := FetchJSON(ctx, apiURL, &resp); err != nil {
		return nil, fmt.Errorf("twinbru api: %w", err)
	}

	var results []ScrapedAsset
	for _, r := range resp.Results {
		item := r.Item
		if item == nil {
			continue
		}

		itemID, _ := item["itemId"].(string)
		assetURL := viewBase + itemID

		if ContainsURL(existing, assetURL) {
			continue
		}

		// Find thumbnail
		var thumbURL string
		for _, renderScene := range []string{"Swatch_ruler", "BL_20_CU", "BL_65_CU", "BL_20", "BL_65"} {
			thumbParams := url.Values{
				"pageSize": {"200"},
				"filter":   {fmt.Sprintf("renderScene.eq.%s/stockId.eq.%s", renderScene, itemID)},
			}
			var thumbResp struct {
				Results []struct {
					Item struct {
						AssetID string `json:"assetId"`
					} `json:"item"`
				} `json:"results"`
			}
			if _, err := FetchJSON(ctx, thumbQueryBase+"?"+thumbParams.Encode(), &thumbResp); err == nil && len(thumbResp.Results) > 0 {
				thumbURL = thumbBase + thumbResp.Results[0].Item.AssetID + "/Thumbnail.jpg"
				break
			}
		}
		if thumbURL == "" {
			slog.Warn("twinbru: no thumbnail", "url", assetURL)
			continue
		}

		// Extract tags from various fields
		var tags []string
		for _, field := range []string{"class", "use", "finish", "quality", "characteristics", "brand", "company", "designName", "collectionName", "colouring", "main_colour_type_description"} {
			if v, ok := item[field]; ok {
				tags = append(tags, splitTwinbruTags(tagRe, v)...)
			}
		}
		for _, field := range []string{"cat_woven", "end_use", "colour_type_description"} {
			if v, ok := item[field]; ok {
				tags = append(tags, splitTwinbruTags(tagRe, v)...)
			}
		}
		tags = uniqueNonEmpty(tags)

		// Type
		at := model.AssetTypePBRMaterial

		// Name
		designName, _ := item["designName"].(string)
		collectionName, _ := item["collectionName"].(string)
		colourDesc, _ := item["main_colour_type_description"].(string)
		var name string
		if designName == collectionName {
			name = collectionName + " / " + colourDesc
		} else {
			name = designName + " / " + collectionName + " / " + colourDesc
		}

		img, err := FetchImage(ctx, thumbURL)
		if err != nil {
			slog.Warn("twinbru: thumbnail failed", "url", assetURL, "err", err)
			continue
		}

		results = append(results, ScrapedAsset{
			Title:        name,
			URL:          assetURL,
			Tags:         tags,
			Type:         at,
			Creator:      model.CreatorTwinbru,
			Status:       model.ScrapedNewlyFound,
			RawThumbnail: img,
		})
	}

	if len(resp.Results) > 0 {
		database.SetCreatorState(s.DB, int(model.CreatorTwinbru), "page", strconv.Itoa(page+1))
	} else {
		database.SetCreatorState(s.DB, int(model.CreatorTwinbru), "page", "1")
	}

	return results, nil
}

func splitTwinbruTags(re *regexp.Regexp, v interface{}) []string {
	switch val := v.(type) {
	case string:
		return re.Split(val, -1)
	case []interface{}:
		var result []string
		for _, item := range val {
			if s, ok := item.(string); ok {
				result = append(result, re.Split(s, -1)...)
			}
		}
		return result
	default:
		return nil
	}
}

func uniqueNonEmpty(ss []string) []string {
	seen := make(map[string]bool)
	var result []string
	for _, s := range ss {
		s = strings.TrimSpace(s)
		if s != "" && !seen[s] {
			seen[s] = true
			result = append(result, s)
		}
	}
	return result
}

func (s *TwinbruScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
