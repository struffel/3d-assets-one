package scraper

import (
	"context"
	"log/slog"
	"strings"

	"github.com/PuerkitoBio/goquery"
	"github.com/struffel/3d-assets-one/internal/model"
)

// parseHTMLMetaTags fetches a URL and extracts <meta> name/property -> content mappings.
func parseHTMLMetaTags(ctx context.Context, rawURL string) (map[string]string, error) {
	body, _, err := FetchURL(ctx, rawURL)
	if err != nil {
		return nil, err
	}
	doc, err := goquery.NewDocumentFromReader(strings.NewReader(body))
	if err != nil {
		return nil, err
	}
	tags := make(map[string]string)
	doc.Find("meta").Each(func(_ int, s *goquery.Selection) {
		name, _ := s.Attr("name")
		prop, _ := s.Attr("property")
		content, _ := s.Attr("content")
		if name != "" {
			tags[name] = content
		} else if prop != "" {
			tags[prop] = content
		}
	})
	return tags, nil
}

// parseCommaSeparatedList fetches a URL and returns comma-separated entries.
func parseCommaSeparatedList(ctx context.Context, rawURL string) ([]string, error) {
	body, _, err := FetchURL(ctx, rawURL)
	if err != nil {
		return nil, err
	}
	body = strings.ReplaceAll(body, "\n", "")
	parts := strings.Split(body, ",")
	var result []string
	for _, p := range parts {
		p = strings.TrimSpace(p)
		if p != "" {
			result = append(result, p)
		}
	}
	return result, nil
}

// --- ShareTextures ---

type ShareTexturesScraper struct{}

func (s *ShareTexturesScraper) Creator() model.Creator { return model.CreatorShareTextures }

func (s *ShareTexturesScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 10
	const listURL = "https://www.sharetextures.com/tex1-list.php"

	urls, err := parseCommaSeparatedList(ctx, listURL)
	if err != nil {
		return nil, err
	}

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
			slog.Warn("sharetextures: meta fetch failed", "url", u, "err", err)
			continue
		}

		title := meta["og:title"]
		if title == "" {
			continue
		}
		tagStr := meta["tex1:tags"]
		tags := strings.Split(tagStr, ",")
		previewURL := meta["tex1:preview-image"]
		if previewURL == "" {
			continue
		}

		img, err := FetchImage(ctx, previewURL)
		if err != nil {
			slog.Warn("sharetextures: thumbnail failed", "url", u, "err", err)
			continue
		}

		results = append(results, ScrapedAsset{
			Title:        title,
			URL:          u,
			Tags:         tags,
			Type:         model.AssetTypeFromTex1Tag(meta["tex1:type"]),
			Creator:      model.CreatorShareTextures,
			Status:       model.ScrapedNewlyFound,
			RawThumbnail: img,
		})
	}

	return results, nil
}

func (s *ShareTexturesScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}

// --- TextureCan ---

type TextureCanScraper struct{}

func (s *TextureCanScraper) Creator() model.Creator { return model.CreatorTextureCan }

func (s *TextureCanScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 5
	const listURL = "https://www.texturecan.com/tex1-list.php"

	urls, err := parseCommaSeparatedList(ctx, listURL)
	if err != nil {
		return nil, err
	}

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
			slog.Warn("texturecan: meta fetch failed", "url", u, "err", err)
			continue
		}

		title := meta["tex1:name"]
		if title == "" {
			continue
		}
		tags := ExplodeFilterTrim(",", meta["tex1:tags"])
		previewURL := meta["tex1:preview-image"]
		if previewURL == "" {
			continue
		}

		img, err := FetchImage(ctx, previewURL)
		if err != nil {
			slog.Warn("texturecan: thumbnail failed", "url", u, "err", err)
			continue
		}

		results = append(results, ScrapedAsset{
			Title:        title,
			URL:          u,
			Tags:         tags,
			Type:         model.AssetTypeFromTex1Tag(meta["tex1:type"]),
			Creator:      model.CreatorTextureCan,
			Status:       model.ScrapedNewlyFound,
			RawThumbnail: img,
		})
	}

	return results, nil
}

func (s *TextureCanScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
