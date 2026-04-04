# Contributing

## Running 3DAssets.one locally

### Docker (recommended)

1. Clone the repository and open a terminal in it.
2. Copy `.env.template` to `.env` and fill in the required values.
3. Build and start the containers:
```bash
docker compose up --build -d
```
4. Go to `http://localhost:7000` to access the site.

The Docker setup includes a local LibSQL database. Thumbnail storage is optional for local development — the app will run without `BUNNY_STORAGE_*` variables, but thumbnails won't be processed.

### Manual setup

Requirements: **Go 1.25+**

```bash
# Install dependencies
go mod download

# Build
go build -o server ./cmd/server

# Run (set environment variables first — see .env.template)
./server
```

The server listens on port `8080` by default and serves static assets from the `public/` directory.

### Running tests

```bash
go test ./...
```

## Adding a new creator

### Registration

In `internal/model/creator.go`:

1. Add a new `Creator` constant with an unused integer ID.
2. Add the value to the `allCreators` slice.
3. Add switch cases in `Slug()`, `Title()`, `Description()`, `BaseURL()`, and `LicenseURL()`.
4. Add the creator to `regularScrapingTargets` if it should be indexed on a schedule.

### Adding a scraper

In `internal/scraper/` create a new `.go` file for the creator.

Implement a function with signature:

```go
func scrape<Name>(ctx context.Context, existing []model.StoredAsset) ([]model.ScrapedAsset, error)
```

Then register it in `internal/scraper/registry.go` by adding an entry to the `Registry` map:

```go
model.Creator<Name>: scrape<Name>,
```

The function receives the existing assets for this creator and returns newly scraped assets.

