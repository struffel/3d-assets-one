package scraper

import (
	"context"
	"database/sql"
	"log/slog"
	"math/rand"
	"strings"
	"time"

	"github.com/struffel/3d-assets-one/internal/database"
	"github.com/struffel/3d-assets-one/internal/model"
	"github.com/struffel/3d-assets-one/internal/query"
	"github.com/struffel/3d-assets-one/internal/thumbnail"
)

// Scheduler runs periodic scraping in the background.
type Scheduler struct {
	db        *sql.DB
	scrapers  map[model.Creator]CreatorScraper
	thumbProc *thumbnail.Processor
	interval  time.Duration
}

// NewScheduler creates a background scraper scheduler.
func NewScheduler(db *sql.DB, thumbProc *thumbnail.Processor, interval time.Duration) *Scheduler {
	return &Scheduler{
		db:        db,
		scrapers:  AllScrapers(db),
		thumbProc: thumbProc,
		interval:  interval,
	}
}

// Start begins the background scraping loop. Blocks until ctx is cancelled.
func (s *Scheduler) Start(ctx context.Context) {
	slog.Info("Scraper scheduler started", "interval", s.interval)
	ticker := time.NewTicker(s.interval)
	defer ticker.Stop()

	// Run one immediate cycle on startup
	s.runCycle(ctx)

	for {
		select {
		case <-ctx.Done():
			slog.Info("Scraper scheduler stopping")
			return
		case <-ticker.C:
			s.runCycle(ctx)
		}
	}
}

func (s *Scheduler) runCycle(ctx context.Context) {
	// Pick a random creator from the regular scraping targets
	targets := model.RegularScrapingTargets()
	if len(targets) == 0 {
		return
	}
	creator := targets[rand.Intn(len(targets))]

	slog.Info("Starting scrape cycle", "creator", creator.Slug())
	s.scrapeCreator(ctx, creator)

	// Update popularity scores after scraping
	if err := database.UpdatePopularityScores(s.db); err != nil {
		slog.Error("Failed to update popularity scores", "err", err)
	}
}

// ScrapeCreator runs a scrape for a specific creator (used by scheduler and admin trigger).
func (s *Scheduler) ScrapeCreator(ctx context.Context, creator model.Creator) {
	s.scrapeCreator(ctx, creator)
}

func (s *Scheduler) scrapeCreator(ctx context.Context, creator model.Creator) {
	scraper, ok := s.scrapers[creator]
	if !ok {
		slog.Warn("No scraper for creator", "creator", creator.Slug())
		return
	}

	// Get existing assets for this creator
	q := &query.AssetQuery{}
	q.FilterCreator = []model.Creator{creator}
	q.FilterStatus = nil // all statuses
	q.Limit = nil        // no limit
	q.Sort = model.SortLatest

	existingCollection, err := q.Execute(s.db)
	if err != nil {
		slog.Error("Failed to fetch existing assets", "creator", creator.Slug(), "err", err)
		database.UpdateCreatorAvailability(s.db, int(creator), false)
		return
	}

	existing := existingCollection.Assets
	slog.Info("Existing assets loaded", "creator", creator.Slug(), "count", len(existing))

	// Convert to value slice for interface
	existingVals := make([]model.StoredAsset, len(existing))
	for i, a := range existing {
		existingVals[i] = *a
	}

	// Scrape
	scraped, err := scraper.ScrapeAssets(ctx, existingVals)
	if err != nil {
		slog.Error("Scrape failed", "creator", creator.Slug(), "err", err)
		database.UpdateCreatorAvailability(s.db, int(creator), false)
		return
	}

	slog.Info("Scraped new assets", "creator", creator.Slug(), "count", len(scraped))

	// Post-process: expand tags with title words + creator slug
	for i := range scraped {
		titleWords := strings.Fields(scraped[i].Title)
		scraped[i].Tags = append(scraped[i].Tags, titleWords...)
		scraped[i].Tags = append(scraped[i].Tags, creator.Slug())
		scraped[i].Tags = FilterTagArray(scraped[i].Tags)
	}

	// Save each asset
	saved := 0
	for _, sa := range scraped {
		if sa.Creator != creator {
			slog.Warn("Skipping asset with mismatched creator", "expected", creator.Slug())
			continue
		}

		// Convert to stored asset
		now := time.Now()
		storedAsset := &model.StoredAsset{
			CreatorGivenID: sa.CreatorGivenID,
			Title:          sa.Title,
			URL:            sa.URL,
			Date:           now,
			Type:           sa.Type,
			Creator:        sa.Creator,
			Tags:           sa.Tags,
			Status:         sa.Status.ToStoredAssetStatus(),
		}

		// Write asset to database
		assetID, err := query.WriteAsset(s.db, storedAsset)
		if err != nil {
			slog.Error("Failed to write asset", "url", sa.URL, "err", err)
			continue
		}

		// Save thumbnail variations
		if sa.RawThumbnail != nil && s.thumbProc != nil {
			if err := s.thumbProc.SaveVariations(ctx, assetID, sa.RawThumbnail); err != nil {
				slog.Warn("Failed to save thumbnails", "assetID", assetID, "err", err)
			}
		}

		saved++
	}

	slog.Info("Scrape cycle complete", "creator", creator.Slug(), "saved", saved)
	database.UpdateCreatorAvailability(s.db, int(creator), true)
}
