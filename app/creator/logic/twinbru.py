import re
from urllib.parse import urlencode

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


class CreatorLogicTwinbru(CreatorLogic):
    creator = Creator.TWINBRU
    tag_regex = r"[^A-Za-z0-9%]"
    indexing_base_url = "https://textures.twinbru.com/api/ods/products"
    indexing_base_params: dict = {
        "pageSize": 50,
        "sortAttribute": "launch",
        "sortDirection": "DSC",
        "prefilter": "status.eq.RN/bvs_special.ne.any(customer%20special,treatment%20special)/has3dTexture.eq.True",
    }
    view_page_base_url = "https://textures.twinbru.com/products/"
    thumbnail_query_base_url = "https://textures.twinbru.com/api/ods/assets"
    thumbnail_base_url = "https://cdn.twinbru.com/ods/assets/"

    def _extract_tags(self, value: list | str) -> list[str]:
        if isinstance(value, list):
            result: list[str] = []
            for item in value:
                result.extend(t for t in re.split(self.tag_regex, str(item)) if t)
            return result
        return [t for t in re.split(self.tag_regex, str(value)) if t]

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        page = int(self.get_creator_state("page") or 1)

        params = dict(self.indexing_base_params)
        params["page"] = page
        url = self.indexing_base_url + "?" + urlencode(params)
        raw_data = WebItemReference(url=url).fetch().parse_as_json()

        if not raw_data:
            return tmp_collection

        for product_wrap in raw_data.get("results", []):
            product = product_wrap.get("item")
            if not product:
                continue

            asset_url = self.view_page_base_url + product["itemId"]
            if existing_assets.contains_url(asset_url):
                continue

            # Resolve thumbnail
            thumbnail_url: str | None = None
            for render_scene in ["Swatch_ruler", "BL_20_CU", "BL_65_CU", "BL_20", "BL_65"]:
                thumb_params = urlencode({"pageSize": 200, "filter": f"renderScene.eq.{render_scene}/stockId.eq.{product['itemId']}"})
                thumb_response = WebItemReference(url=self.thumbnail_query_base_url + "?" + thumb_params).fetch().parse_as_json()
                if thumb_response and thumb_response.get("results"):
                    asset_id = thumb_response["results"][0]["item"]["assetId"]
                    thumbnail_url = self.thumbnail_base_url + asset_id + "/Thumbnail.jpg"
                    break
                else:
                    Log.write(f"No thumbnail for render scene {render_scene}", None, LogLevel.WARNING)

            if not thumbnail_url:
                Log.write("Skipping asset (no thumbnail)", asset_url, LogLevel.ERROR)
                continue

            # Tags
            tags = list(set(filter(None, (
                self._extract_tags(product.get("class", ""))
                + self._extract_tags(product.get("use", ""))
                + self._extract_tags(product.get("finish", ""))
                + self._extract_tags(product.get("quality", ""))
                + self._extract_tags(product.get("characteristics", ""))
                + self._extract_tags(product.get("brand", ""))
                + self._extract_tags(product.get("company", ""))
            ))))

            asset = ScrapedAsset(
                id=None,
                creator_given_id=None,
                title=product.get("descriptionShort") or product.get("itemId", ""),
                url=asset_url,
                tags=tags,
                type=AssetType.PBR_MATERIAL,
                creator=Creator.TWINBRU,
                status=ScrapedAssetStatus.NEWLY_FOUND,
                raw_thumbnail=WebItemReference(url=thumbnail_url).fetch().parse_as_image(),
            )
            tmp_collection.append(asset)

        self.set_creator_state("page", page + 1)
        return tmp_collection
