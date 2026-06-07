package scraper

import (
	"context"
	"database/sql"
	"fmt"
	"log/slog"
	"regexp"
	"strconv"
	"strings"

	"github.com/PuerkitoBio/goquery"
	"github.com/struffel/3d-assets-one/internal/database"
	"github.com/struffel/3d-assets-one/internal/model"
)

type CgMoodScraper struct {
	DB *sql.DB
}

func (s *CgMoodScraper) Creator() model.Creator { return model.CreatorCGMood }

var cgMoodURLTypeRegex = map[string]model.AssetType{
	"/3d-model/": model.AssetTypeModel3D,
	"/material/": model.AssetTypePBRMaterial,
}

func (s *CgMoodScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPages = 3
	const indexBase = "https://cgmood.com/free?page="

	tagRe := regexp.MustCompile(`[^A-Za-z0-9]`)

	pageStr, _ := database.GetCreatorState(s.DB, int(model.CreatorCGMood), "page")
	page, _ := strconv.Atoi(pageStr)
	if page < 1 {
		page = 1
	}

	var results []ScrapedAsset
	pagesProcessed := 0

	for pagesProcessed < maxPages {
		body, _, err := FetchURL(ctx, fmt.Sprintf("%s%d", indexBase, page))
		if err != nil {
			break
		}

		doc, err := goquery.NewDocumentFromReader(strings.NewReader(body))
		if err != nil {
			break
		}

		imgElements := doc.Find(".product img")
		if imgElements.Length() == 0 {
			page = 1
			break
		}

		imgElements.Each(func(_ int, sel *goquery.Selection) {
			productURL, _ := sel.Attr("data-product-url")
			productTitle, _ := sel.Attr("data-product-title")
			imgSrc, _ := sel.Attr("src")

			// Determine type from URL
			var at model.AssetType
			found := false
			for pattern, t := range cgMoodURLTypeRegex {
				if strings.Contains(productURL, pattern) {
					at = t
					found = true
					break
				}
			}
			if !found || ContainsURL(existing, productURL) {
				return
			}

			// Tags from title
			parts := tagRe.Split(productTitle, -1)
			var tags []string
			for _, p := range parts {
				if p != "" {
					tags = append(tags, p)
				}
			}

			thumbURL := "https://cgmood.com" + imgSrc
			img, err := FetchImage(ctx, thumbURL)
			if err != nil {
				slog.Warn("cgmood: thumbnail failed", "url", productURL, "err", err)
				return
			}

			results = append(results, ScrapedAsset{
				Title:        productTitle,
				URL:          productURL,
				Tags:         tags,
				Type:         at,
				Creator:      model.CreatorCGMood,
				Status:       model.ScrapedNewlyFound,
				RawThumbnail: img,
			})
		})

		page++
		pagesProcessed++
	}

	database.SetCreatorState(s.DB, int(model.CreatorCGMood), "page", strconv.Itoa(page))
	return results, nil
}

func (s *CgMoodScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	body, status, err := FetchURL(ctx, asset.URL)
	if err != nil || status != 200 {
		return false
	}
	doc, err := goquery.NewDocumentFromReader(strings.NewReader(body))
	if err != nil {
		return false
	}
	btn := doc.Find(".download-button")
	if btn.Length() == 0 {
		return false
	}
	text := btn.First().Text()
	return strings.Contains(text, "Free download")
}
