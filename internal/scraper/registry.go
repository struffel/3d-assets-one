package scraper

import (
	"database/sql"

	"github.com/struffel/3d-assets-one/internal/model"
)

// AllScrapers returns all 17 creator scraper instances.
func AllScrapers(db *sql.DB) map[model.Creator]CreatorScraper {
	return map[model.Creator]CreatorScraper{
		model.CreatorAmbientCG:        &AmbientCGScraper{},
		model.CreatorPolyhaven:        &PolyhavenScraper{},
		model.CreatorShareTextures:    &ShareTexturesScraper{},
		model.CreatorThreeDTextures:   &ThreeDTexturesScraper{},
		model.CreatorCGBookcase:       &CgBookcaseScraper{},
		model.CreatorTextureCan:       &TextureCanScraper{},
		model.CreatorNoEmotionHDRs:    &NoEmotionHDRsScraper{},
		model.CreatorGPUOpenMatLib:    &AmdGpuOpenScraper{},
		model.CreatorRawCatalog:       &RawCatalogScraper{},
		model.CreatorPoliigon:         &PoliigonScraper{},
		model.CreatorTexturesCom:      &TexturesComScraper{},
		model.CreatorCGMood:           &CgMoodScraper{DB: db},
		model.CreatorThreeDScans:      &ThreeDScansScraper{},
		model.CreatorLocationTextures: &LocationTexturesScraper{},
		model.CreatorPbrPx:            &PbrPxScraper{},
		model.CreatorTwinbru:          &TwinbruScraper{DB: db},
		model.CreatorLightbeans:       &LightbeansScraper{},
	}
}
