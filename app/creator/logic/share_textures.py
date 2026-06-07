import re

from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference
from app.misc.string_util import StringUtil


class CreatorLogicShareTextures(CreatorLogic):
    creator = Creator.SHARETEXTURES
    max_assets_per_run = 10
    list_url = "https://www.sharetextures.com/tex1-list.php"

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        url_array = WebItemReference(self.list_url).fetch().parse_as_comma_separated_list()
        tmp_collection = ScrapedAssetCollection()
        count = 0

        for url in url_array:
            if existing_assets.contains_url(url):
                continue

            meta = WebItemReference(url).fetch().parse_html_meta_tags()
            if not meta:
                continue

            title = meta.get("og:title")
            tags_raw = meta.get("tex1:tags", "")
            preview = meta.get("tex1:preview-image", "")
            tex1_type = meta.get("tex1:type")

            if not title or not tags_raw or not preview:
                continue

            asset = ScrapedAsset(
                id=None,
                creator_given_id=None,
                title=title,
                url=url,
                type=AssetType.from_tex1_tag(tex1_type),
                creator=Creator.SHARETEXTURES,
                status=ScrapedAssetStatus.NEWLY_FOUND,
                raw_thumbnail=WebItemReference(preview).fetch().parse_as_image(),
                tags=tags_raw.split(","),
            )
            tmp_collection.append(asset)
            count += 1
            if count >= self.max_assets_per_run:
                break

        return tmp_collection
