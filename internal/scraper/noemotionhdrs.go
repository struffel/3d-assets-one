package scraper

import (
	"context"
	"log/slog"
	"net/url"
	"path/filepath"
	"strings"

	"github.com/struffel/3d-assets-one/internal/model"
)

type NoEmotionHDRsScraper struct{}

func (s *NoEmotionHDRsScraper) Creator() model.Creator { return model.CreatorNoEmotionHDRs }

func (s *NoEmotionHDRsScraper) ScrapeAssets(ctx context.Context, existing []model.StoredAsset) ([]ScrapedAsset, error) {
	const maxPerRun = 10

	var results []ScrapedAsset
	for _, u := range noEmotionURLList {
		if len(results) >= maxPerRun {
			break
		}
		if ContainsURL(existing, u) {
			continue
		}

		// Extract category and name from URL
		parsed, err := url.Parse(u)
		if err != nil {
			continue
		}
		filename := filepath.Base(parsed.Path)                                       // e.g. "hdrday.html"
		category := strings.TrimSuffix(strings.TrimPrefix(filename, "hdr"), ".html") // e.g. "day"
		category = strings.ToUpper(category[:1]) + category[1:]                      // "Day"

		// Name from fragment (after "text=")
		fragment := parsed.Fragment
		name := ""
		if idx := strings.Index(fragment, "text="); idx >= 0 {
			name, _ = url.QueryUnescape(fragment[idx+5:])
		}
		if name == "" {
			name = category
		}

		thumbURL := "https://noemotionhdrs.net/Previews/772x386/" + category + "/" + name + ".jpg"

		img, err := FetchImage(ctx, thumbURL)
		if err != nil {
			slog.Warn("noemotionhdrs: thumbnail failed", "url", u, "err", err)
			continue
		}

		results = append(results, ScrapedAsset{
			Title:        name,
			URL:          u,
			Tags:         []string{"Sky", category},
			Type:         model.AssetTypeHDRI,
			Creator:      model.CreatorNoEmotionHDRs,
			Status:       model.ScrapedNewlyFound,
			RawThumbnail: img,
		})
	}

	return results, nil
}

func (s *NoEmotionHDRsScraper) ValidateAsset(ctx context.Context, asset model.StoredAsset) bool {
	return HeadOK(ctx, asset.URL)
}

var noEmotionURLList = []string{
	"http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FH",
	"http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FG",
	"http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FF",
	"http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FE",
	"http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FD",
	"http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FC",
	"http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FB",
	"http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FA",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D28%5FDay%5FD",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D28%5FDay%5FC",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D28%5FDay%5FB",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D28%5FDay%5FA",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FG",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FF",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FE",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FD",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FC",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FB",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FA",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FF",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FE",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FD",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FC",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FB",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FA",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D08%5FDay%5FB",
	"http://noemotionhdrs.net/hdrday.html#:~:text=05%2D08%5FDay%5FA",
	"http://noemotionhdrs.net/hdrday.html#:~:text=04%2D23%5FDay%5FD",
	"http://noemotionhdrs.net/hdrday.html#:~:text=04%2D23%5FDay%5FC",
	"http://noemotionhdrs.net/hdrday.html#:~:text=04%2D23%5FDay%5FB",
	"http://noemotionhdrs.net/hdrday.html#:~:text=04%2D23%5FDay%5FA",
	"http://noemotionhdrs.net/hdrday.html#:~:text=04%2D18%5FCloudy",
	"http://noemotionhdrs.net/hdrday.html#:~:text=04%2D17%5FCloudy",
	"http://noemotionhdrs.net/hdrday.html#:~:text=04%2D16%5FSky",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=03%2D28%5FSunset",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=03%2D29%5FSunset",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D03%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D03%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D06%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D06%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D06%5FSun%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D11%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D11%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D11%5FSun%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D11%5FSun%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D12%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D12%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D12%5FSun%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FE",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D18%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D18%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D18%5FSun%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D23%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D23%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D23%5FSun%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D23%5FSun%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D26%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D26%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D29%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D29%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D29%5FSun%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FE",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FG",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D11%5FSun%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D11%5FSun%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D11%5FSun%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D11%5FSun%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D23%5FSunset%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D23%5FSunset%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D23%5FSunset%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D23%5FSunset%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D29%5FSunset%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D29%5FSunset%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D29%5FSunset%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D29%5FSunset%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D02%5FSunset%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D02%5FSunset%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D02%5FSunset%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D02%5FSunset%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D03%5FSunset%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D03%5FSunset%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D03%5FSunset%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D09%5FSunset%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D09%5FSunset%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D09%5FSunset%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D14%5FSunset%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D14%5FSunset%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=06%2D14%5FSunset%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=07%2D25%5FSunset%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=07%2D25%5FSunset%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=07%2D25%5FSunset%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=07%2D25%5FSunset%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=08%2D08%5FSunset%5FA",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=08%2D08%5FSunset%5FB",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=08%2D08%5FSunset%5FC",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=08%2D08%5FSunset%5FD",
	"http://noemotionhdrs.net/hdrevening.html#:~:text=08%2D08%5FSunset%5FE",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=02%2D04%5FGarage",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=03%2D19%5FNight%5FA",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=03%2D19%5FNight%5FB",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=03%2D30%5FNight",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D06%5FFila%5FNight%5FA",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D06%5FFila%5FNight%5FB",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D07%5FBrum%5FNight%5FA",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D07%5FFila%5Flnter",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D19%5FNight%5FA",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D19%5FNight%5FB",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D19%5FNight%5FC",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D21%5FNight%5FA",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D21%5FNight%5FB",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D21%5FNight%5FC",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D28%5FNight%5FA",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D28%5FNight%5FB",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D28%5FNight%5FC",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D28%5FNight%5FD",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D28%5FNight%5FE",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D29%5FNight%5FA",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=04%2D29%5FNight%5FB",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=05%2D05%5FNight%5FA",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=05%2D05%5FNight%5FB",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=08%2D07%5FNight%5FA",
	"http://noemotionhdrs.net/hdrnight.html#:~:text=08%2D07%5FNight%5FB",
	"http://noemotionhdrs.net/hdrother.html#:~:text=05%2D20%5FPark%5FA",
	"http://noemotionhdrs.net/hdrother.html#:~:text=05%2D20%5FPark%5FB",
	"http://noemotionhdrs.net/hdrother.html#:~:text=05%2D20%5FPark%5FC",
	"http://noemotionhdrs.net/hdrother.html#:~:text=05%2D20%5FPark%5FD",
	"http://noemotionhdrs.net/hdrother.html#:~:text=05%2D20%5FPark%5FE",
	"http://noemotionhdrs.net/hdrother.html#:~:text=05%2D20%5FPark%5FF",
	"http://noemotionhdrs.net/hdrother.html#:~:text=08%2D21%5FSwiss%5FA",
	"http://noemotionhdrs.net/hdrother.html#:~:text=08%2D21%5FSwiss%5FB",
	"http://noemotionhdrs.net/hdrother.html#:~:text=08%2D21%5FSwiss%5FC",
	"http://noemotionhdrs.net/hdrother.html#:~:text=08%2D21%5FSwiss%5FD",
	"http://noemotionhdrs.net/hdrother.html#:~:text=08%2D21%5FSwiss%5FE",
	"http://noemotionhdrs.net/hdrother.html#:~:text=10%2D30%5FForest%5FA",
	"http://noemotionhdrs.net/hdrother.html#:~:text=10%2D30%5FForest%5FB",
	"http://noemotionhdrs.net/hdrother.html#:~:text=10%2D30%5FForest%5FC",
	"http://noemotionhdrs.net/hdrother.html#:~:text=10%2D30%5FForest%5FD",
	"http://noemotionhdrs.net/hdrother.html#:~:text=10%2D30%5FForest%5FE",
	"http://noemotionhdrs.net/hdrother.html#:~:text=10%2D30%5FForest%5FF",
	"http://noemotionhdrs.net/hdrother.html#:~:text=11%2D13%5FForest%5FA",
	"http://noemotionhdrs.net/hdrother.html#:~:text=11%2D13%5FForest%5FB",
	"http://noemotionhdrs.net/hdrother.html#:~:text=11%2D13%5FForest%5FC",
	"http://noemotionhdrs.net/hdrother.html#:~:text=11%2D13%5FForest%5FD",
}
