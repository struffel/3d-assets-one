import re

from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset import StoredAsset
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference
from app.log.log import Log
from app.log.log_level import LogLevel


class CreatorLogicCgMood(CreatorLogic):
    creator = Creator.CGMOOD
    max_assets_per_run = 3
    indexing_base_url = "https://cgmood.com/free?page="
    url_type_patterns = {
        r"/3d-model/": AssetType.MODEL_3D,
        r"/material/": AssetType.PBR_MATERIAL,
    }

    def validate_asset(self, asset: StoredAsset) -> bool:
        response = WebItemReference(asset.url).fetch()
        if response.http_status_code != 200:
            return False
        dom = response.parse_as_dom()
        if dom is None:
            return False
        download_buttons = dom.find_all(class_="download-button")
        if not download_buttons:
            return False
        button_text = download_buttons[0].get_text()
        return bool(re.search(r"Free download", button_text))

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        page = int(self.get_creator_state("page") or 1)
        pages_processed = 0

        while pages_processed < self.max_assets_per_run:
            dom = WebItemReference(self.indexing_base_url + str(page)).fetch().parse_as_dom()
            if dom is None:
                break

            asset_images = dom.find_all("img", attrs={"data-product-url": True})

            for img in asset_images:
                product_url = img.get("data-product-url", "")
                asset_type: AssetType | None = None
                for pattern, t in self.url_type_patterns.items():
                    if re.search(pattern, product_url):
                        asset_type = t

                if not asset_type:
                    Log.write("Skipping URL (no type match)", product_url, LogLevel.WARNING)
                    continue

                if existing_assets.contains_url(product_url):
                    continue

                title = img.get("data-product-title", "")
                tags = [t for t in re.split(r"[^A-Za-z0-9]", title) if t]

                thumbnail_src = img.get("src", "")
                thumbnail_url = "https://cgmood.com" + thumbnail_src if thumbnail_src.startswith("/") else thumbnail_src

                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=None,
                    title=title,
                    url=product_url,
                    tags=tags,
                    type=asset_type,
                    creator=Creator.CGMOOD,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    raw_thumbnail=WebItemReference(url=thumbnail_url).fetch().parse_as_image(),
                )
                tmp_collection.append(asset)

            page += 1
            pages_processed += 1

            if not asset_images:
                page = 1

        self.set_creator_state("page", page)
        return tmp_collection
