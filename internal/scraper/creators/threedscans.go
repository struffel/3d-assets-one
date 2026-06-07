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

type ThreeDScansScraper struct{}

func (s *ThreeDScansScraper) Creator() model.Creator { return model.CreatorThreeDScans }

func (s *ThreeDScansScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 10
	const indexBase = "https://threedscans.com/page/"

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

		links := doc.Find("article a")
		if links.Length() == 0 {
			break
		}

		links.Each(func(_ int, sel *goquery.Selection) {
			if len(results) >= maxPerRun {
				return
			}

			href, _ := sel.Attr("href")
			title, _ := sel.Attr("title")
			imgEl := sel.Find("img.frontPageImg")
			imgSrc, _ := imgEl.Attr("src")

			if ContainsURL(existing, href) {
				return
			}

			// Tags from title + fixed sculpture tags
			titleParts := tagRe.Split(title, -1)
			var tags []string
			for _, p := range titleParts {
				if p != "" {
					tags = append(tags, p)
				}
			}
			tags = append(tags, "statue", "sculpture")

			img, err := FetchImage(ctx, imgSrc)
			if err != nil {
				slog.Warn("threedscans: thumbnail failed", "url", href, "err", err)
				return
			}

			results = append(results, ScrapedAsset{
				Title:        title,
				URL:          href,
				Tags:         tags,
				Type:         model.AssetTypeModel3D,
				Creator:      model.CreatorThreeDScans,
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

func (s *ThreeDScansScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
