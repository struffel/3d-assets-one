package scraper

import (
	"context"
	"log/slog"
	"strings"

	"github.com/PuerkitoBio/goquery"
	"github.com/struffel/3d-assets-one/internal/model"
)

type CgBookcaseScraper struct{}

func (s *CgBookcaseScraper) Creator() model.Creator { return model.CreatorCGBookcase }

func (s *CgBookcaseScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 5
	const baseURL = "https://www.cgbookcase.com/textures/"

	body, _, err := FetchURL(ctx, baseURL)
	if err != nil {
		return nil, err
	}

	doc, err := goquery.NewDocumentFromReader(strings.NewReader(body))
	if err != nil {
		return nil, err
	}

	var urls []string
	doc.Find(`a[href*="/textures/"]`).Each(func(_ int, sel *goquery.Selection) {
		href, exists := sel.Attr("href")
		if exists {
			urls = append(urls, "https://www.cgbookcase.com"+href+"?source=3dassets.one")
		}
	})

	var results []ScrapedAsset
	for _, u := range urls {
		if len(results) >= maxPerRun {
			break
		}
		if ContainsURL(existing, u) {
			continue
		}

		meta, err := parseHTMLMetaTags(ctx, u)
		if err != nil {
			continue
		}

		name, ok := meta["tex1:name"]
		if !ok || name == "" {
			continue
		}
		previewURL, ok := meta["tex1:preview-image"]
		if !ok || previewURL == "" {
			continue
		}
		tagStr, ok := meta["tex1:tags"]
		if !ok {
			continue
		}
		typeStr := meta["tex1:type"]

		img, err := FetchImage(ctx, previewURL)
		if err != nil {
			slog.Warn("cgbookcase: thumbnail failed", "url", u, "err", err)
			continue
		}

		results = append(results, ScrapedAsset{
			Title:        name,
			URL:          u,
			Tags:         ExplodeFilterTrim(",", tagStr),
			Type:         model.AssetTypeFromTex1Tag(typeStr),
			Creator:      model.CreatorCGBookcase,
			Status:       model.ScrapedNewlyFound,
			RawThumbnail: img,
		})
	}

	return results, nil
}

func (s *CgBookcaseScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
