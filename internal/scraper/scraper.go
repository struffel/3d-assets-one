package scraper

import (
	"context"
	"encoding/json"
	"fmt"
	"image"
	_ "image/gif"
	_ "image/jpeg"
	_ "image/png"
	"io"
	"net/http"
	"net/url"
	"strings"
	"time"

	"github.com/struffel/3d-assets-one/internal/model"
)

// CreatorScraper defines the contract each creator scraper must implement.
type CreatorScraper interface {
	Creator() model.Creator
	ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error)
	ValidateAsset(ctx context.Context, asset model.StoredAsset) bool
}

// ScrapedAsset is a temporary asset obtained from scraping, before persistence.
type ScrapedAsset struct {
	CreatorGivenID *string
	Title          string
	URL            string
	Type           model.AssetType
	Creator        model.Creator
	Tags           []string
	Status         model.ScrapedAssetStatus
	RawThumbnail   image.Image // nil if thumbnail couldn't be fetched
}

// httpClient is the shared client for all scrapers.
var httpClient = &http.Client{
	Timeout: 60 * time.Second,
}

const userAgent = "3dassets.one / Fetching"

// FetchURL performs a GET request and returns the response body as a string.
func FetchURL(ctx context.Context, rawURL string) (string, int, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, rawURL, nil)
	if err != nil {
		return "", 0, fmt.Errorf("create request: %w", err)
	}
	req.Header.Set("User-Agent", userAgent)

	resp, err := httpClient.Do(req)
	if err != nil {
		return "", 0, fmt.Errorf("http get: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", resp.StatusCode, fmt.Errorf("read body: %w", err)
	}
	return string(body), resp.StatusCode, nil
}

// FetchJSON performs a GET and decodes JSON into target.
func FetchJSON(ctx context.Context, rawURL string, target any) (int, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, rawURL, nil)
	if err != nil {
		return 0, fmt.Errorf("create request: %w", err)
	}
	req.Header.Set("User-Agent", userAgent)

	resp, err := httpClient.Do(req)
	if err != nil {
		return 0, fmt.Errorf("http get: %w", err)
	}
	defer resp.Body.Close()

	if err := json.NewDecoder(resp.Body).Decode(target); err != nil {
		return resp.StatusCode, fmt.Errorf("decode json: %w", err)
	}
	return resp.StatusCode, nil
}

// FetchImage downloads an image from a URL and decodes it.
func FetchImage(ctx context.Context, rawURL string) (image.Image, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, rawURL, nil)
	if err != nil {
		return nil, fmt.Errorf("create request: %w", err)
	}
	req.Header.Set("User-Agent", userAgent)

	resp, err := httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("http get: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != 200 {
		return nil, fmt.Errorf("image fetch returned status %d", resp.StatusCode)
	}

	img, _, err := image.Decode(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("decode image: %w", err)
	}
	return img, nil
}

// HeadOK performs a HEAD request and returns true if status == 200.
func HeadOK(ctx context.Context, rawURL string) bool {
	req, err := http.NewRequestWithContext(ctx, http.MethodHead, rawURL, nil)
	if err != nil {
		return false
	}
	req.Header.Set("User-Agent", userAgent)
	resp, err := httpClient.Do(req)
	if err != nil {
		return false
	}
	resp.Body.Close()
	return resp.StatusCode == 200
}

// AddHTTPParameters appends or merges query parameters into a URL string.
func AddHTTPParameters(rawURL string, params map[string]string) (string, error) {
	u, err := url.Parse(rawURL)
	if err != nil {
		return "", err
	}
	q := u.Query()
	for k, v := range params {
		q.Set(k, v)
	}
	u.RawQuery = q.Encode()
	return u.String(), nil
}

// ContainsURL checks if any existing asset has the given URL.
func ContainsURL(existing []model.StoredAsset, u string) bool {
	for i := range existing {
		if strings.EqualFold(existing[i].URL, u) {
			return true
		}
	}
	return false
}
