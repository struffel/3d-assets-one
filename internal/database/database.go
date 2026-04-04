package database

import (
	"database/sql"
	"embed"
	"fmt"
	"io/fs"
	"log/slog"
	"sort"
	"strings"

	_ "github.com/tursodatabase/libsql-client-go/libsql"
)

//go:embed migrations/*.sql
var migrationsFS embed.FS

// Connect opens a connection to BunnyDatabase (LibSQL) using the provided URL and auth token.
func Connect(databaseURL, authToken string) (*sql.DB, error) {
	if databaseURL == "" {
		return nil, fmt.Errorf("database URL is empty: set BUNNY_DATABASE_URL")
	}

	connStr := databaseURL
	if authToken != "" {
		connStr += "?authToken=" + authToken
	}

	db, err := sql.Open("libsql", connStr)
	if err != nil {
		return nil, fmt.Errorf("failed to open database: %w", err)
	}
	if err := db.Ping(); err != nil {
		return nil, fmt.Errorf("failed to ping database: %w", err)
	}
	slog.Info("Connected to database", "url", databaseURL)
	return db, nil
}

// Migrate applies all pending SQL migration files.
// Uses a _migrations table to track which versions have been applied.
func Migrate(db *sql.DB) error {
	// Ensure migration tracking table exists
	_, err := db.Exec(`CREATE TABLE IF NOT EXISTS "_migrations" ("version" INTEGER PRIMARY KEY)`)
	if err != nil {
		return fmt.Errorf("failed to create _migrations table: %w", err)
	}

	// Get current max version
	var currentVersion int
	row := db.QueryRow(`SELECT COALESCE(MAX(version), 0) FROM "_migrations"`)
	if err := row.Scan(&currentVersion); err != nil {
		return fmt.Errorf("failed to read migration version: %w", err)
	}
	slog.Info("Current migration version", "version", currentVersion)

	// Read migration files
	entries, err := fs.ReadDir(migrationsFS, "migrations")
	if err != nil {
		return fmt.Errorf("failed to read migrations directory: %w", err)
	}

	// Sort by filename
	sort.Slice(entries, func(i, j int) bool {
		return entries[i].Name() < entries[j].Name()
	})

	for _, entry := range entries {
		if entry.IsDir() {
			continue
		}
		name := entry.Name()
		if !strings.HasSuffix(name, ".sql") {
			continue
		}

		// Parse version number from filename (e.g., "001_initial.sql" -> 1)
		var version int
		_, err := fmt.Sscanf(name, "%03d_", &version)
		if err != nil {
			slog.Warn("Skipping non-migration file", "file", name)
			continue
		}

		if version <= currentVersion {
			continue
		}

		// Read and execute migration
		content, err := migrationsFS.ReadFile("migrations/" + name)
		if err != nil {
			return fmt.Errorf("failed to read migration %s: %w", name, err)
		}

		slog.Info("Running migration", "version", version, "file", name)

		// Split by semicolons and execute each statement (libsql HTTP doesn't support multi-statement)
		statements := splitStatements(string(content))
		for _, stmt := range statements {
			stmt = strings.TrimSpace(stmt)
			if stmt == "" {
				continue
			}
			if _, err := db.Exec(stmt); err != nil {
				return fmt.Errorf("migration %d failed on statement %q: %w", version, stmt, err)
			}
		}

		// Record migration
		if _, err := db.Exec(`INSERT INTO "_migrations" ("version") VALUES (?)`, version); err != nil {
			return fmt.Errorf("failed to record migration %d: %w", version, err)
		}

		slog.Info("Applied migration", "version", version)
	}

	return nil
}

// splitStatements splits SQL text on semicolons, handling simple cases.
func splitStatements(sql string) []string {
	var stmts []string
	for _, s := range strings.Split(sql, ";") {
		s = strings.TrimSpace(s)
		if s != "" {
			stmts = append(stmts, s)
		}
	}
	return stmts
}

// GeneratePlaceholders returns "?,?,?" for n items.
func GeneratePlaceholders(n int) string {
	if n <= 0 {
		return ""
	}
	return strings.Repeat("?,", n-1) + "?"
}

// AddAssetClick increments the click counter for an asset.
func AddAssetClick(db *sql.DB, assetID int64) error {
	_, err := db.Exec("UPDATE Asset SET clicks = clicks + 1 WHERE id = ?", assetID)
	return err
}

// UpdatePopularityScores recalculates popularity for all assets.
func UpdatePopularityScores(db *sql.DB) error {
	sql := `UPDATE Asset SET popularityScore = (
		(clicks / (ABS(JULIANDAY('now') - JULIANDAY(date)) + 1))
		/ (SELECT (COUNT(*) + 1) FROM Asset a2 WHERE a2.creatorId = Asset.creatorId AND a2.date >= datetime('now', '-14 days'))
	)`
	_, err := db.Exec(sql)
	return err
}

// GetCreatorState reads a per-creator state value from FetchingState.
func GetCreatorState(db *sql.DB, creatorID int, key string) (string, bool) {
	var val string
	err := db.QueryRow(
		"SELECT stateValue FROM FetchingState WHERE creatorId = ? AND stateKey = ?",
		creatorID, key,
	).Scan(&val)
	if err != nil {
		return "", false
	}
	return val, true
}

// SetCreatorState writes a per-creator state value to FetchingState.
func SetCreatorState(db *sql.DB, creatorID int, key, value string) error {
	_, err := db.Exec(
		"REPLACE INTO FetchingState (creatorId, stateKey, stateValue) VALUES (?, ?, ?)",
		creatorID, key, value,
	)
	return err
}

// UpdateCreatorAvailability records a scrape attempt result.
func UpdateCreatorAvailability(db *sql.DB, creatorID int, success bool) error {
	successInt := 0
	if success {
		successInt = 1
	}
	_, err := db.Exec(`
		INSERT INTO CreatorAvailability (creatorId, lastChecked, lastSuccess, failedAttempts)
		VALUES (?, datetime('now'), ?, CASE WHEN ? = 1 THEN 0 ELSE 1 END)
		ON CONFLICT(creatorId) DO UPDATE SET
			lastChecked = datetime('now'),
			lastSuccess = CASE WHEN ? = 1 THEN datetime('now') ELSE lastSuccess END,
			failedAttempts = CASE WHEN ? = 1 THEN 0 ELSE failedAttempts + 1 END
	`, creatorID, successInt, successInt, successInt, successInt)
	return err
}
