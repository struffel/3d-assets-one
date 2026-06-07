import json
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


class CreatorLogicPbrPx(CreatorLogic):
    creator = Creator.PBR_PX
    indexing_api_base_url = "https://api.pbrpx.com/home/api_product/getPmsg?page_number="
    asset_api_base_url = "https://api.pbrpx.com/home/api_product/getasset"
    asset_viewing_base_url = "https://library.pbrpx.com/#/asset?asset="
    media_base_url = "https://asset.pbrpx.com/"
    thumbnail_identifier = "preview_360_360"
    max_assets_per_run = 25

    def validate_asset(self, asset: StoredAsset) -> bool:
        url_token = asset.url.split("_")[0]
        url_parts = url_token.split("=")
        asset_id = url_parts[-1] if url_parts else ""
        try:
            response = WebItemReference(
                url=self.asset_api_base_url,
                method="POST",
                request_body=json.dumps({"asset": asset_id}),
                headers={"Content-Type": "application/json"},
            ).fetch().parse_as_json()
            return isinstance(response, dict) and response.get("errno") == 0
        except Exception:
            return False

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        page = 1
        processed = 0

        while processed < self.max_assets_per_run:
            raw_list = WebItemReference(url=self.indexing_api_base_url + str(page)).fetch().parse_as_json()
            if not isinstance(raw_list, dict) or "data" not in raw_list.get("data", {}):
                break

            asset_list = raw_list["data"]["data"]
            if not asset_list:
                break

            for pbr_asset in asset_list:
                asset_url = self.asset_viewing_base_url + str(pbr_asset["id"])
                if existing_assets.contains_url(asset_url):
                    continue

                # Fetch asset details
                details_raw = WebItemReference(
                    url=self.asset_api_base_url,
                    method="POST",
                    request_body=json.dumps({"asset": str(pbr_asset["id"])}),
                    headers={"Content-Type": "application/json"},
                ).fetch().parse_as_json()

                Log.write("PBR PX Asset Details", details_raw, LogLevel.DEBUG)

                if not isinstance(details_raw, dict) or not details_raw.get("data"):
                    continue
                details = details_raw["data"][0]

                # Tags
                tags = [t for t in re.split(r"[^A-Za-z0-9]", details.get("ename", "")) if t]

                # Type
                zips = details.get("zips", "")
                if zips.startswith("HDRI"):
                    asset_type = AssetType.HDRI
                elif zips.startswith("Textures"):
                    asset_type = AssetType.PBR_MATERIAL
                elif zips.startswith("3D_Model"):
                    asset_type = AssetType.MODEL_3D
                else:
                    asset_type = AssetType.OTHER

                # Thumbnail
                img_candidates = details.get("img_url", "").split("+")
                thumbnail_url = self.media_base_url + img_candidates[0]
                for candidate in img_candidates:
                    if self.thumbnail_identifier in candidate:
                        thumbnail_url = self.media_base_url + candidate
                        break

                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=None,
                    title=details.get("ename", ""),
                    url=asset_url,
                    type=asset_type,
                    creator=Creator.PBR_PX,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    tags=list(tags),
                    raw_thumbnail=WebItemReference(url=thumbnail_url).fetch().parse_as_image(),
                )
                tmp_collection.append(asset)
                processed += 1

                if processed >= self.max_assets_per_run:
                    return tmp_collection

            page += 1

        return tmp_collection
