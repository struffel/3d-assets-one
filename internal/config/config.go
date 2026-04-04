package config

import (
	"os"
)

// Config holds all application configuration loaded from environment variables.
type Config struct {
	// Database
	DatabaseURL       string
	DatabaseAuthToken string

	// Bunny Storage (FTP)
	StorageZone     string
	StoragePassword string
	StorageHost     string
	CDNBaseURL      string // e.g. https://your-zone.b-cdn.net

	// Admin
	AdminToken string

	// Server
	Port string
}

// Load reads configuration from environment variables.
func Load() *Config {
	c := &Config{
		DatabaseURL:       getEnv("BUNNY_DATABASE_URL", ""),
		DatabaseAuthToken: getEnv("BUNNY_DATABASE_AUTH_TOKEN", ""),
		StorageZone:       getEnv("BUNNY_STORAGE_ZONE", ""),
		StoragePassword:   getEnv("BUNNY_STORAGE_PASSWORD", ""),
		StorageHost:       getEnv("BUNNY_STORAGE_HOST", "storage.bunnycdn.com"),
		CDNBaseURL:        getEnv("BUNNY_CDN_URL", ""),
		AdminToken:        getEnv("ADMIN_TOKEN", "default"),
		Port:              getEnv("PORT", "8080"),
	}
	return c
}

func getEnv(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}
