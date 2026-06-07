package scraper

import (
	"context"
	"fmt"
	"log/slog"
	"regexp"
	"strings"

	"github.com/PuerkitoBio/goquery"
	"github.com/struffel/3d-assets-one/internal/model"
)

type PoliigonScraper struct{}

func (s *PoliigonScraper) Creator() model.Creator { return model.CreatorPoliigon }

var poliigonURLTypeRegex = map[string]model.AssetType{
	"/texture/": model.AssetTypePBRMaterial,
	"/model/":   model.AssetTypeModel3D,
	"/hdri/":    model.AssetTypeHDRI,
}

func extractPoliigonID(u string) string {
	parts := strings.Split(strings.TrimRight(u, "/"), "/")
	if len(parts) == 0 {
		return ""
	}
	return parts[len(parts)-1]
}

func (s *PoliigonScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 100
	const baseURL = "https://www.poliigon.com"
	const searchBase = "https://www.poliigon.com/free?sort=newest&page="

	tagRe := regexp.MustCompile(`[\s,]+`)
	var results []ScrapedAsset
	page := 1

	for page < 20 { // failsafe
		body, _, err := FetchURL(ctx, fmt.Sprintf("%s%d", searchBase, page))
		if err != nil {
			break
		}

		doc, err := goquery.NewDocumentFromReader(strings.NewReader(body))
		if err != nil {
			break
		}

		boxes := doc.Find("div.asset-box__item-inner")
		if boxes.Length() == 0 {
			break
		}

		boxes.Each(func(_ int, sel *goquery.Selection) {
			if len(results) >= maxPerRun {
				return
			}

			linkEl := sel.Find("a.asset-box__item-link")
			urlPath, _ := linkEl.Attr("href")
			url := baseURL + urlPath

			// Check if exists by ID comparison
			newID := extractPoliigonID(url)
			alreadyExists := false
			for _, ex := range existing {
				if extractPoliigonID(ex.URL) == newID {
					alreadyExists = true
					break
				}
			}
			if alreadyExists {
				return
			}

			name := sel.Find(".asset-box__item-title-name").Text()

			// Determine type from URL
			var at model.AssetType
			found := false
			for pattern, t := range poliigonURLTypeRegex {
				if strings.Contains(strings.ToLower(urlPath), pattern) {
					at = t
					found = true
					break
				}
			}
			if !found {
				return
			}

			tags := tagRe.Split(name, -1)
			var filteredTags []string
			for _, t := range tags {
				if t != "" {
					filteredTags = append(filteredTags, t)
				}
			}

			imgSrc, _ := sel.Find("img").Attr("src")
			img, err := FetchImage(ctx, imgSrc)
			if err != nil {
				slog.Warn("poliigon: thumbnail failed", "url", url, "err", err)
				return
			}

			results = append(results, ScrapedAsset{
				Title:        name,
				URL:          url,
				Tags:         filteredTags,
				Type:         at,
				Creator:      model.CreatorPoliigon,
				Status:       model.ScrapedNewlyFound,
				RawThumbnail: img,
			})
		})

		if len(results) >= maxPerRun {
			break
		}
		page++
	}

	return results, nil
}

func (s *PoliigonScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
