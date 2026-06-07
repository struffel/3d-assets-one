import re

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


class CreatorLogicTexturesCom(CreatorLogic):
    creator = Creator.TEXTURES_COM
    max_assets_per_run = 25
    api_base_url = "https://www.textures.com/api/v1/texture/search?filter=free&page="
    category_mapping: dict[int, AssetType] = {
        114553: AssetType.MODEL_3D,
        114561: AssetType.OTHER,
        114548: AssetType.PBR_MATERIAL,
        114563: AssetType.PBR_MATERIAL,
        114570: AssetType.MODEL_3D,
        114558: AssetType.PBR_MATERIAL,
        114557: AssetType.OTHER,
        114552: AssetType.HDRI,
        23740: AssetType.HDRI,
        114568: AssetType.OTHER,
        114571: AssetType.OTHER,
        114579: AssetType.MODEL_3D,
        114590: AssetType.MODEL_3D,
        114576: AssetType.MODEL_3D,
    }

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        page = 1

        while True:
            api_data = WebItemReference(self.api_base_url + str(page)).fetch().parse_as_json()
            if api_data is None or "data" not in api_data:
                raise RuntimeError("Failed to fetch or parse API data from textures.com")

            assets_found = len(api_data["data"])

            for asset_data in api_data["data"]:
                if len(tmp_collection) >= self.max_assets_per_run:
                    return tmp_collection

                photo_set = asset_data.get("defaultPhotoSet", {})
                url = f"https://textures.com/download/{asset_data.get('filenameWithoutSet', '')}/{photo_set.get('id', '')}"
                title = photo_set.get("titleThumbnail", "")

                if existing_assets.contains_url(url):
                    continue

                Log.write("Found new textures.com asset", {"title": title}, LogLevel.DEBUG)

                tags = [t for t in re.split(r"[^A-Za-z0-9]", title) if t]
                category_id = int(asset_data.get("defaultCategoryId", 0))
                asset_type = self.category_mapping.get(category_id, AssetType.OTHER)
                picture_path = asset_data.get("picture", "")
                thumbnail_url = f"https://textures.com/{picture_path}"

                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=None,
                    title=title,
                    url=url,
                    tags=tags,
                    type=asset_type,
                    creator=Creator.TEXTURES_COM,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    raw_thumbnail=WebItemReference(url=thumbnail_url).fetch().parse_as_image(),
                )
                tmp_collection.append(asset)

            page += 1
            if assets_found == 0 or page >= 20:
                break

        return tmp_collection
