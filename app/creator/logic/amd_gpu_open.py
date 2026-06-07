from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference


class CreatorLogicAmdGpuOpen(CreatorLogic):
    creator = Creator.GPUOPENMATLIB
    api_url = "https://api.matlib.gpuopen.com/api/materials/?limit=50&ordering=-published_date&status=Published&updateKey=1&offset=0"
    tag_api_url = "https://api.matlib.gpuopen.com/api/tags/"
    url_template = "https://matlib.gpuopen.com/main/materials/all?material={id}"
    preview_image_template = "https://image.matlib.gpuopen.com/{id}.jpeg"
    exclude_title_prefix = "TH: "
    max_assets_per_run = 5

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        target_url: str | None = self.api_url

        while target_url and len(tmp_collection) < self.max_assets_per_run:
            api_json = WebItemReference(target_url).fetch().parse_as_json()
            if api_json is None or "results" not in api_json:
                raise RuntimeError("Failed to fetch or parse AMD GPUOpen API data.")

            for amd_asset in api_json["results"]:
                if len(tmp_collection) >= self.max_assets_per_run:
                    break
                if amd_asset.get("title", "").startswith(self.exclude_title_prefix):
                    continue

                url = self.url_template.format(id=amd_asset["id"])
                if existing_assets.contains_url(url):
                    continue

                # Fetch tags
                tags: list[str] = []
                for tag_id in amd_asset.get("tags", []):
                    tag_json = WebItemReference(self.tag_api_url + str(tag_id)).fetch().parse_as_json()
                    if tag_json and "title" in tag_json:
                        tags.append(tag_json["title"])

                renders = amd_asset.get("renders_order", [])
                if not renders:
                    continue
                thumbnail_url = self.preview_image_template.format(id=renders[0])

                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=str(amd_asset["id"]),
                    url=url,
                    title=amd_asset["title"],
                    tags=tags,
                    type=AssetType.PBR_MATERIAL,
                    creator=Creator.GPUOPENMATLIB,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    raw_thumbnail=WebItemReference(url=thumbnail_url).fetch().parse_as_image(),
                )
                tmp_collection.append(asset)

            target_url = api_json.get("next")

        return tmp_collection
