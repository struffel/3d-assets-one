import io

from PIL import Image

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


class CreatorLogicLightbeans(CreatorLogic):
    creator = Creator.LIGHTBEANS
    max_assets_per_run = 20
    sitemap_url = "https://lightbeans.com/sitemap.xml"
    sitemap_url_must_contain = "lightbeans.com/en/texture/"
    banned_tags = {"Lightbeans", "|", "-", "from"}

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()

        sitemap = WebItemReference(self.sitemap_url).fetch().parse_as_sitemap()
        if not sitemap:
            return tmp_collection

        new_urls: list[str] = []
        for site in sitemap:
            url = site.url
            if not existing_assets.contains_url(url) and self.sitemap_url_must_contain in url:
                new_urls.append(url)
            if len(new_urls) >= self.max_assets_per_run:
                break

        for new_url in new_urls:
            meta = WebItemReference(new_url).fetch().parse_html_meta_tags()
            if not meta:
                continue

            og_image = meta.get("og:image", "")
            thumbnail_url = og_image.replace("dynamic-rectangle-image", "dynamic-square-image")

            title = meta.get("og:title", "").replace("| Lightbeans", "").strip()
            tags = [t for t in title.split() if t not in self.banned_tags]

            Log.write("Resolved tags", tags, LogLevel.DEBUG)

            thumbnail = self._fetch_cropped_thumbnail(thumbnail_url)
            if thumbnail is None:
                Log.write("Failed to fetch thumbnail, skipping", new_url, LogLevel.WARNING)
                continue

            asset = ScrapedAsset(
                id=None,
                creator_given_id=None,
                title=title,
                url=new_url,
                tags=tags,
                type=AssetType.PBR_MATERIAL,
                creator=Creator.LIGHTBEANS,
                status=ScrapedAssetStatus.NEWLY_FOUND,
                raw_thumbnail=thumbnail,
            )
            tmp_collection.append(asset)

        return tmp_collection

    def _fetch_cropped_thumbnail(self, url: str) -> Image.Image | None:
        content = WebItemReference(url).fetch().content
        if content is None:
            return None

        try:
            image = Image.open(io.BytesIO(content)).convert("RGBA")
        except Exception:
            return None

        width, height = image.size
        crop_size = max(int(min(width, height) * 0.65), 1)
        x = (width - crop_size) // 2
        y = int((height - crop_size) / 1.5)
        cropped = image.crop((x, y, x + crop_size, y + crop_size))
        return cropped
