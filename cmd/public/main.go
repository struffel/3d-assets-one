package main

import (
	"log/slog"
	"os"

	"github.com/gin-gonic/gin"
	"github.com/struffel/3d-assets-one/internal/config"
	"github.com/struffel/3d-assets-one/internal/database"
	"github.com/struffel/3d-assets-one/internal/web"
)

func main() {
	// Structured logging
	defaultLogger := slog.New(slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{Level: slog.LevelInfo}))
	slog.SetDefault(defaultLogger)

	// Load config
	cfg := config.LoadPublicConfig()

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

	// Web server — public routes only
	gin.SetMode(gin.ReleaseMode)
	srv := web.NewServer(db, cfg)

	staticFS := os.DirFS("public")
	router := srv.SetupPublicRouter(staticFS)

	addr := ":" + cfg.Port
	slog.Info("Starting public server", "addr", addr)
	if err := router.Run(addr); err != nil {
		slog.Error("Server failed", "error", err)
		os.Exit(1)
	}
}
