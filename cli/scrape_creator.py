#!/usr/bin/env python3
"""CLI: Scrape assets for a given creator."""
import sys
from pathlib import Path

# Ensure project root is on path when run directly
sys.path.insert(0, str(Path(__file__).parent.parent))

from dotenv import load_dotenv
load_dotenv()

from app.database.database import Database
from app.asset.stored_asset_query import StoredAssetQuery
from app.asset.stored_asset_status import StoredAssetStatus
from app.creator.creator import Creator
from app.log.log import Log
from app.misc.string_util import StringUtil
from app.thumbnail.thumbnail import Thumbnail


def main() -> None:
    args = sys.argv[1:]
    if not args:
        print("Usage: scrape_creator.py <creator-slug|creator-id> [force]")
        sys.exit(1)

    creator_arg = args[0]
    force = len(args) > 1 and args[1].lower() == "force"

    creator = Creator.from_value_or_slug(creator_arg)

    if not force and not creator.is_available_for_scrape():
        print(f"Creator {creator.slug()} is not available for scraping (too many failed attempts). Use 'force' to override.")
        sys.exit(0)

    Log.start(f"scrape-creator/{creator.slug()}")

    try:
        creator.increment_failed_attempts()

        # Load existing assets for this creator to check for duplicates
        existing_query = StoredAssetQuery(
            offset=0,
            limit=999999,
            filter_creator=[creator.slug()],
        )
        existing_assets = existing_query.execute()

        # Run the scraper
        logic = creator.get_logic()
        scraped_collection = logic.scrape_assets()

        saved_count = 0
        skipped_count = 0

        for scraped_asset in scraped_collection.assets:
            # Post-process tags
            scraped_asset.tags = StringUtil.filter_tag_array(scraped_asset.tags)
            # Add creator slug as a tag
            if creator.slug() not in scraped_asset.tags:
                scraped_asset.tags.append(creator.slug())

            # Skip if already exists
            if existing_assets.contains_url(scraped_asset.url):
                skipped_count += 1
                continue

            # Write to database
            stored = scraped_asset.to_stored_asset()
            Database.start_transaction()
            try:
                stored.write_to_database()
                Database.commit_transaction()
            except Exception:
                Database.rollback_transaction()
                raise

            # Save thumbnails
            if scraped_asset.raw_thumbnail is not None:
                try:
                    Thumbnail.save_thumbnail_variations(stored.id, scraped_asset.raw_thumbnail)
                except Exception as e:
                    Log.write(f"Failed to save thumbnail for asset {stored.id}: {e}")

            saved_count += 1

        creator.reset_failed_attempts()
        Thumbnail.delete_orphaned_thumbnails()
        Database.update_popularity_scores()

        Log.write(f"Scraped {len(scraped_collection.assets)} assets. Saved: {saved_count}, Skipped (already exists): {skipped_count}.")
    except Exception as e:
        Log.write(f"Error during scraping: {e}", error=True)
        raise
    finally:
        Log.stop()


if __name__ == "__main__":
    main()
