# Contributing

## Running 3dassets.one locally

### Docker
If available, Docker is the easiest way to run 3dassets.one locally for development purposes.

1. Clone the repository and open a terminal in it.
2. Review the provided `.env.template` file in the `src/` directory and create a `.env` file in the same directory.
3. Fetch the needed PHP composer dependencies:
```bash
docker run -it --rm -v $pwd/src:/app composer composer install
```
4. Build and start the Docker containers:
```bash
docker compose up --force-recreate --build -d
```
1. Go to `http://localhost:5000` to access the site.
2. To use the CLI tools, open a terminal in the running web container:
```bash
docker exec -it 3d1-web /bin/bash
```

and navigate to the location where the source code is mounted.
In the `cli/` subdirectory you can find the available CLI tools and run them with `php <tool.php>`.

### Manual Setup
- 3Dassets.one uses
  - PHP 8.4
  - Apache with `src/public/` as document root
  - SQLite and GDimage extensions for PHP
  - GD needs `.webp` support for some creator thumbnails

## Adding a new creator
The main form of contribution to 3dassets.one is to write an indexer for a new creator.

### Registration

In `src/include/creator/Creator.php` add a new enum value for the creator and add cases in the necessary functions:

- `slug()`
- `title()`
- `description()`
- `baseUrl()`
- `licenseUrl()`
- Add the creator to `regularREfreshList()` if it should be indexed regularly.

### Adding logic

In `src/include/creator/logic/` create a new class for the creator.

The `CreatorLogic<Name>` class needs to extend the `CreatorLogic` abstract class and implement the method:

`scrapeAssets(StoredAssetCollection $existingAssets): ScrapedAssetCollection`

This method is called during indexing.
It receives all existing assets from this creator and returns a collection of newly scraped assets (if any were found).

