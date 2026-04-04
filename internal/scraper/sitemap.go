package scraper

import (
	"context"
	"encoding/xml"
)

// parseSitemapURLs fetches a sitemap.xml and returns all <loc> URLs.
func parseSitemapURLs(ctx context.Context, sitemapURL string) ([]string, error) {
	body, _, err := FetchURL(ctx, sitemapURL)
	if err != nil {
		return nil, err
	}

	var sitemap struct {
		URLs []struct {
			Loc string `xml:"loc"`
		} `xml:"url"`
	}
	if err := xml.Unmarshal([]byte(body), &sitemap); err != nil {
		return nil, err
	}

	var urls []string
	for _, u := range sitemap.URLs {
		urls = append(urls, u.Loc)
	}
	return urls, nil
}
