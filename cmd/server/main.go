package main

import (
	"context"
	"io/fs"
	"log/slog"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/struffel/3d-assets-one/internal/config"
	"github.com/struffel/3d-assets-one/internal/database"
	"github.com/struffel/3d-assets-one/internal/scraper"
	"github.com/struffel/3d-assets-one/internal/storage"
	"github.com/struffel/3d-assets-one/internal/thumbnail"
	"github.com/struffel/3d-assets-one/internal/web"
)

//go:generate echo "Ensure public/ static assets are embedded"

func main() {
	// Structured logging
	slog.SetDefault(slog.New(slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{Level: slog.LevelInfo})))

	// Load config
	cfg := config.Load()

	// Connect to database
	db, err := database.Connect(cfg.DatabaseURL, cfg.DatabaseAuthToken)
	if err != nil {
		slog.Error("Database connection failed", "error", err)
		os.Exit(1)
	}
	defer db.Close()

	// Run migrations
	if err := database.Migrate(db); err != nil {
		slog.Error("Database migration failed", "error", err)
		os.Exit(1)
	}

	// Object storage (FTP to Bunny) - only if configured
	var thumbProc *thumbnail.Processor
	if cfg.StorageZone != "" && cfg.StoragePassword != "" {
		store := storage.NewFTPStorage(cfg.StorageZone, cfg.StoragePassword, cfg.StorageHost, cfg.CDNBaseURL)
		thumbProc = thumbnail.NewProcessor(store)
	} else {
		slog.Warn("Storage not configured, thumbnails and scraping will be disabled")
	}

	// Background scraper - only if thumbnail processor is available
	ctx, cancel := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer cancel()

	if thumbProc != nil {
		sched := scraper.NewScheduler(db, thumbProc, 2*time.Minute)
		go sched.Start(ctx)
	}

	// Web server
	gin.SetMode(gin.ReleaseMode)
	srv := web.NewServer(db, cfg)

	// Use the public/ directory for static assets.
	// In production this is the working directory; adjust as needed.
	staticFS := os.DirFS("public")
	router := srv.SetupRouter(staticFS)

	addr := ":" + cfg.Port
	slog.Info("Starting server", "addr", addr)
	if err := router.Run(addr); err != nil {
		slog.Error("Server failed", "error", err)
		os.Exit(1)
	}
}

// Ensure fs.FS interface is satisfied at compile time.
var _ fs.FS = os.DirFS(".")
