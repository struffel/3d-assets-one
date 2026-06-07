from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference


class CreatorLogicPolyhaven(CreatorLogic):
    creator = Creator.POLYHAVEN
    api_url = "https://api.polyhaven.com/assets"
    view_base_url = "https://polyhaven.com/a/"
    thumbnail_url_prefix = "https://cdn.polyhaven.com/asset_img/thumbs/"
    thumbnail_url_suffix = ".png?height=512"
    type_mapping = {0: AssetType.HDRI, 1: AssetType.PBR_MATERIAL, 2: AssetType.MODEL_3D}
    max_assets_per_run = 10

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        result = WebItemReference(self.api_url).fetch().parse_as_json()
        if result is None:
            raise RuntimeError(f"Could not fetch or parse Polyhaven API data from {self.api_url}")

        for key, ph_asset in result.items():
            url = self.view_base_url + key
            if not existing_assets.contains_url(url) and len(tmp_collection) < self.max_assets_per_run:
                thumbnail_url = self.thumbnail_url_prefix + key + self.thumbnail_url_suffix
                raw_thumbnail = WebItemReference(url=thumbnail_url).fetch().parse_as_image()

                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=None,
                    url=url,
                    title=ph_asset.get("name", key),
                    tags=ph_asset.get("tags", []),
                    type=self.type_mapping.get(int(ph_asset.get("type", 0)), AssetType.OTHER),
                    creator=Creator.POLYHAVEN,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    raw_thumbnail=raw_thumbnail,
                )
                tmp_collection.append(asset)

        return tmp_collection
