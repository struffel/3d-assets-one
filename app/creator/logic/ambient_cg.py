from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference


class CreatorLogicAmbientCg(CreatorLogic):
    creator = Creator.AMBIENTCG
    api_url = "https://ambientcg.com/api/v2/full_json"
    initial_parameters = {
        "limit": 100,
        "offset": 0,
        "include": "displayData,tagData,imageData",
    }
    max_assets_per_run = 25
    type_mapping: dict[str, AssetType] = {
        "Material": AssetType.PBR_MATERIAL,
        "Decal": AssetType.PBR_MATERIAL,
        "Atlas": AssetType.PBR_MATERIAL,
        "HDRI": AssetType.HDRI,
        "3DModel": AssetType.MODEL_3D,
        "SculptingBrush": AssetType.OTHER,
        "Terrain": AssetType.OTHER,
        "SBSAR": AssetType.SUBSTANCE_MATERIAL,
        "Substance": AssetType.SUBSTANCE_MATERIAL,
        "PlainTexture": AssetType.PBR_MATERIAL,
        "Brush": AssetType.OTHER,
        "HDRIElement": AssetType.HDRI,
    }

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        from urllib.parse import urlencode

        target_url = self.api_url + "?" + urlencode(self.initial_parameters)
        new_assets = ScrapedAssetCollection()

        while target_url and len(new_assets) < self.max_assets_per_run:
            result = WebItemReference(target_url).fetch().parse_as_json()
            if result is None or "foundAssets" not in result:
                break

            for acg_asset in result["foundAssets"]:
                url = acg_asset.get("shortLink", "")
                if not existing_assets.contains_url(url) and len(new_assets) < self.max_assets_per_run:
                    thumbnail_url = acg_asset.get("previewImage", {}).get("512-PNG", "")
                    raw_thumbnail = WebItemReference(url=thumbnail_url).fetch().parse_as_image() if thumbnail_url else None

                    asset = ScrapedAsset(
                        id=None,
                        creator_given_id=acg_asset.get("assetId"),
                        title=acg_asset.get("displayName", ""),
                        url=url,
                        tags=acg_asset.get("tags", []),
                        type=self.type_mapping.get(acg_asset.get("dataType", ""), AssetType.OTHER),
                        creator=Creator.AMBIENTCG,
                        status=ScrapedAssetStatus.NEWLY_FOUND,
                        raw_thumbnail=raw_thumbnail,
                    )
                    new_assets.append(asset)

            target_url = result.get("nextPageHttp", "")

        return new_assets
