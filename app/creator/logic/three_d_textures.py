from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference
from app.log.log import Log
from app.log.log_level import LogLevel


class CreatorLogicThreeDTextures(CreatorLogic):
    creator = Creator.THREE_D_TEXTURES
    max_assets_per_run = 10
    api_url = "https://3dtextures.me/wp-json/wp/v2/"

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        page = 1
        processed = 0
        continue_loop = True

        while continue_loop:
            wp_link = f"{self.api_url}posts?_embed&per_page=100&page={page}&orderby=date"
            wp_output = WebItemReference(wp_link).fetch().parse_as_json()

            if not wp_output:
                break

            for wp_post in wp_output:
                if existing_assets.contains_url(wp_post.get("link", "").lower()):
                    continue

                # Tags
                tmp_tags: list[str] = []
                for embedded_cat in wp_post.get("_embedded", {}).get("wp:term", []):
                    for obj in embedded_cat:
                        tmp_tags.append(obj.get("name", ""))

                # Thumbnail (try 3 sources)
                thumbnail_url: str | None = None
                media = wp_post.get("_embedded", {}).get("wp:featuredmedia", [{}])
                if media:
                    sizes = media[0].get("media_details", {}).get("sizes", {})
                    thumbnail_url = sizes.get("square", {}).get("source_url")
                    if not thumbnail_url:
                        thumbnail_url = media[0].get("source_url")
                if not thumbnail_url:
                    thumbnail_url = wp_post.get("jetpack_featured_media_url")

                if not thumbnail_url:
                    Log.write("Could not resolve thumbnail, skipping", wp_post.get("link"), LogLevel.ERROR)
                    continue

                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=None,
                    title=wp_post.get("title", {}).get("rendered", ""),
                    url=wp_post.get("link", ""),
                    tags=tmp_tags,
                    type=AssetType.PBR_MATERIAL,
                    creator=Creator.THREE_D_TEXTURES,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    raw_thumbnail=WebItemReference(url=thumbnail_url).fetch().parse_as_image(),
                )
                tmp_collection.append(asset)
                processed += 1

                if processed >= self.max_assets_per_run:
                    continue_loop = False
                    break

            page += 1

        return tmp_collection
