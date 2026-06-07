package scraper

import (
	"context"
	"fmt"
	"log/slog"
	"strings"

	"github.com/struffel/3d-assets-one/internal/model"
)

type ThreeDTexturesScraper struct{}

func (s *ThreeDTexturesScraper) Creator() model.Creator { return model.CreatorThreeDTextures }

func (s *ThreeDTexturesScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 10
	const apiBase = "https://3dtextures.me/wp-json/wp/v2/"

	var results []ScrapedAsset
	page := 1

	for {
		var posts []struct {
			Link  string `json:"link"`
			Title struct {
				Rendered string `json:"rendered"`
			} `json:"title"`
			JetpackMedia string `json:"jetpack_featured_media_url"`
			Embedded     struct {
				WPTerm [][]struct {
					Name string `json:"name"`
				} `json:"wp:term"`
				WPFeatured []struct {
					SourceURL    string `json:"source_url"`
					MediaDetails struct {
						Sizes struct {
							Square struct {
								SourceURL string `json:"source_url"`
							} `json:"square"`
						} `json:"sizes"`
					} `json:"media_details"`
				} `json:"wp:featuredmedia"`
			} `json:"_embedded"`
		}

		apiURL := fmt.Sprintf("%sposts?_embed&per_page=100&page=%d&orderby=date", apiBase, page)
		if _, err := FetchJSON(ctx, apiURL, &posts); err != nil || len(posts) == 0 {
			break
		}

		for _, post := range posts {
			if len(results) >= maxPerRun {
				return results, nil
			}

			if ContainsURL(existing, strings.ToLower(post.Link)) {
				continue
			}

			// Tags from embedded terms
			var tags []string
			for _, termGroup := range post.Embedded.WPTerm {
				for _, term := range termGroup {
					tags = append(tags, term.Name)
				}
			}

			// Thumbnail - try multiple sources
			var thumbURL string
			if len(post.Embedded.WPFeatured) > 0 {
				fmedia := post.Embedded.WPFeatured[0]
				thumbURL = fmedia.MediaDetails.Sizes.Square.SourceURL
				if thumbURL == "" {
					thumbURL = fmedia.SourceURL
				}
			}
			if thumbURL == "" {
				thumbURL = post.JetpackMedia
			}
			if thumbURL == "" {
				slog.Warn("3dtextures: no thumbnail", "url", post.Link)
				continue
			}

			img, err := FetchImage(ctx, thumbURL)
			if err != nil {
				slog.Warn("3dtextures: thumbnail failed", "url", post.Link, "err", err)
				continue
			}

			results = append(results, ScrapedAsset{
				Title:        post.Title.Rendered,
				URL:          post.Link,
				Tags:         tags,
				Type:         model.AssetTypePBRMaterial,
				Creator:      model.CreatorThreeDTextures,
				Status:       model.ScrapedNewlyFound,
				RawThumbnail: img,
			})
		}

		page++
	}

	return results, nil
}

func (s *ThreeDTexturesScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}
