package main

import (
	"context"
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

func main() {
	// Structured logging
	defaultLogger := slog.New(slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{Level: slog.LevelInfo}))
	slog.SetDefault(defaultLogger)

	// Load config
	cfg := config.LoadScraperConfig()

	// Connect to database
	db, err := database.Connect(cfg.DatabaseUrl, cfg.DatabaseAuthToken)
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

	// Storage client and thumbnail processor
	storageClient := storage.NewFTPStorage(cfg.FtpHost, cfg.FtpUserName, cfg.FtpUserPass)
	thumbnailProcessor := thumbnail.NewThumbnailProcessor(storageClient)

	// Background scraper
	ctx, cancel := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer cancel()

	sched := scraper.NewScheduler(db, thumbnailProcessor, time.Minute)
	go sched.Start(ctx)

	// Admin web server
	gin.SetMode(gin.ReleaseMode)
	srv := web.NewServer(db, cfg)

	staticFS := os.DirFS("public")
	router := srv.SetupAdminRouter(staticFS)

	addr := ":" + cfg.Port
	slog.Info("Starting scraper/admin server", "addr", addr)
	if err := router.Run(addr); err != nil {
		slog.Error("Server failed", "error", err)
		os.Exit(1)
	}
}
