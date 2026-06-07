package scraper

import (
	"context"
	"log/slog"
	"strings"

	"github.com/struffel/3d-assets-one/internal/model"
)

type LightbeansScraper struct{}

func (s *LightbeansScraper) Creator() model.Creator { return model.CreatorLightbeans }

var lightbeansBannedTags = map[string]bool{
	"lightbeans": true, "|": true, "-": true, "from": true,
}

func (s *LightbeansScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 20
	const sitemapURL = "https://lightbeans.com/sitemap.xml"
	const mustContain = "lightbeans.com/en/texture/"

	urls, err := parseSitemapURLs(ctx, sitemapURL)
	if err != nil {
		return nil, err
	}

	// Filter to only texture URLs not already known
	var newURLs []string
	for _, u := range urls {
		if len(newURLs) >= maxPerRun {
			break
		}
		if strings.Contains(u, mustContain) && !ContainsURL(existing, u) {
			newURLs = append(newURLs, u)
		}
	}

	var results []ScrapedAsset
	for _, u := range newURLs {
		meta, err := parseHTMLMetaTags(ctx, u)
		if err != nil {
			slog.Warn("lightbeans: meta failed", "url", u, "err", err)
			continue
		}

		thumbURL := meta["og:image"]
		thumbURL = strings.ReplaceAll(thumbURL, "dynamic-rectangle-image", "dynamic-square-image")

		title := meta["og:title"]
		title = strings.ReplaceAll(title, "| Lightbeans", "")
		title = strings.TrimSpace(title)

		words := strings.Fields(title)
		var tags []string
		for _, w := range words {
			if !lightbeansBannedTags[w] {
				tags = append(tags, w)
			}
		}

		if thumbURL == "" {
			slog.Warn("lightbeans: no thumbnail", "url", u)
			continue
		}

		img, err := FetchImage(ctx, thumbURL)
		if err != nil {
			slog.Warn("lightbeans: thumbnail failed", "url", u, "err", err)
			continue
		}

		results = append(results, ScrapedAsset{
			Title:        title,
			URL:          u,
			Tags:         tags,
			Type:         model.AssetTypePBRMaterial,
			Creator:      model.CreatorLightbeans,
			Status:       model.ScrapedNewlyFound,
			RawThumbnail: img,
		})
	}

	return results, nil
}

func (s *LightbeansScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
