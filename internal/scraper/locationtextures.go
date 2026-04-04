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

type LocationTexturesScraper struct{}

func (s *LocationTexturesScraper) Creator() model.Creator { return model.CreatorLocationTextures }

func (s *LocationTexturesScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 5
	const indexBase = "https://locationtextures.com/panoramas/free-panoramas/?page="

	tagRe := regexp.MustCompile(`[^A-Za-z0-9]`)
	var results []ScrapedAsset
	page := 1

	for {
		body, _, err := FetchURL(ctx, fmt.Sprintf("%s%d", indexBase, page))
		if err != nil {
			break
		}

		doc, err := goquery.NewDocumentFromReader(strings.NewReader(body))
		if err != nil {
			break
		}

		links := doc.Find("#product-category a.pack-link")
		if links.Length() == 0 {
			break
		}

		links.Each(func(_ int, sel *goquery.Selection) {
			if len(results) >= maxPerRun {
				return
			}

			href, _ := sel.Attr("href")
			imgEl := sel.Find("img.pack-link-img")
			imgTitle, _ := imgEl.Attr("title")
			imgSrc, _ := imgEl.Attr("data-src")

			if ContainsURL(existing, href) {
				return
			}

			// Fetch detail page for tags
			var detailTags []string
			detailBody, _, err := FetchURL(ctx, href)
			if err == nil {
				detailDoc, err := goquery.NewDocumentFromReader(strings.NewReader(detailBody))
				if err == nil {
					detailDoc.Find(`section a[href*="?tag"]`).Each(func(_ int, tagSel *goquery.Selection) {
						detailTags = append(detailTags, tagSel.Text())
					})
				}
			}

			// Tags from title + detail tags
			titleParts := tagRe.Split(imgTitle, -1)
			var tags []string
			for _, p := range titleParts {
				if p != "" {
					tags = append(tags, p)
				}
			}
			tags = append(tags, detailTags...)

			img, err := FetchImage(ctx, imgSrc)
			if err != nil {
				slog.Warn("locationtextures: thumbnail failed", "url", href, "err", err)
				return
			}

			results = append(results, ScrapedAsset{
				Title:        imgTitle,
				URL:          href,
				Tags:         tags,
				Type:         model.AssetTypeHDRI,
				Creator:      model.CreatorLocationTextures,
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

func (s *LocationTexturesScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
