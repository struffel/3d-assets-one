package config

import (
	"log/slog"
	"os"
)

type LibSqlConfig struct {
	DatabaseUrl       string
	DatabaseAuthToken string
}

// Config holds all application configuration loaded from environment variables.
type PublicConfig struct {
	LibSqlConfig
	CdnBaseUrl string
	Port       string
}

type ScraperConfig struct {
	LibSqlConfig

	// Remote Storage (FTP)
	FtpHost     string
	FtpUserName string
	FtpUserPass string

	// Admin
	AdminUser string
	AdminPass string

	// Server
	Port string
}

// Load reads configuration from environment variables.
func LoadPublicConfig() *PublicConfig {
	config := PublicConfig{
		LibSqlConfig: LibSqlConfig{
			DatabaseUrl:       os.Getenv("3D1_LIBSQL_DATABASE_URL"),
			DatabaseAuthToken: os.Getenv("3D1_LIBSQL_DATABASE_AUTH_TOKEN"),
		},
		CdnBaseUrl: os.Getenv("3D1_CDN_URL"),
		Port:       os.Getenv("3D1_PORT"),
	}

	if config.DatabaseUrl == "" || config.DatabaseAuthToken == "" {
		slog.Error("Database configuration missing, both 3D1_LIBSQL_DATABASE_URL and 3D1_LIBSQL_DATABASE_AUTH_TOKEN are required")
		os.Exit(1)
	}

	if config.CdnBaseUrl == "" {
		slog.Error("3D1_CDN_URL not set.")
		os.Exit(1)
	}

	if config.Port == "" {
		slog.Warn("3D1_PORT not set, defaulting to 7000")
		config.Port = "7000"
	}

	return &config
}

func LoadScraperConfig() *ScraperConfig {
	config := ScraperConfig{
		LibSqlConfig: LibSqlConfig{
			DatabaseUrl:       os.Getenv("3D1_LIBSQL_DATABASE_URL"),
			DatabaseAuthToken: os.Getenv("3D1_LIBSQL_DATABASE_AUTH_TOKEN"),
		},
		FtpHost:     os.Getenv("3D1_FTP_HOST"),
		FtpUserName: os.Getenv("3D1_FTP_USER"),
		FtpUserPass: os.Getenv("3D1_FTP_PASS"),
		AdminUser:   os.Getenv("3D1_ADMIN_USER"),
		AdminPass:   os.Getenv("3D1_ADMIN_PASS"),
		Port:        os.Getenv("3D1_PORT"),
	}

	if config.DatabaseUrl == "" || config.DatabaseAuthToken == "" {
		slog.Error("Database configuration missing, both 3D1_LIBSQL_DATABASE_URL and 3D1_LIBSQL_DATABASE_AUTH_TOKEN are required")
		os.Exit(1)
	}

	if config.FtpHost == "" {
		slog.Error("3D1_FTP_HOST not set.")
		os.Exit(1)
	}

	if config.AdminUser == "" || config.AdminPass == "" {
		slog.Warn("Admin credentials not fully set, defaulting to admin/admin")
		config.AdminUser = "admin"
		config.AdminPass = "admin"
	}

	if config.Port == "" {
		slog.Warn("3D1_PORT not set, defaulting to 7001")
		config.Port = "7001"
	}

	return &config
}
