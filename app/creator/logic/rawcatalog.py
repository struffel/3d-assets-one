import re
from xml.etree import ElementTree

from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference


class CreatorLogicRawCatalog(CreatorLogic):
    creator = Creator.RAWCATALOG
    max_assets_per_run = 25
    api_url = "https://rawcatalog.com/freeset.xml"
    type_matching: dict[str, AssetType] = {
        "blueprints": AssetType.OTHER,
        "materials": AssetType.PBR_MATERIAL,
        "atlases": AssetType.PBR_MATERIAL,
        "models": AssetType.MODEL_3D,
    }

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        response = WebItemReference(self.api_url).fetch()
        root = response.parse_as_xml_element()

        if root is None:
            raise RuntimeError(f"Could not fetch or parse RawCatalog source data from {self.api_url}")

        count = 0
        for xpath_prefix, asset_type in self.type_matching.items():
            for raw_asset in root.findall(f"{xpath_prefix}//file"):
                if count >= self.max_assets_per_run:
                    break

                url_el = raw_asset.find("url")
                url = url_el.text.strip() if url_el is not None and url_el.text else ""
                if not url or existing_assets.contains_url(url):
                    continue

                name_el = raw_asset.find("name")
                title = name_el.text.strip() if name_el is not None and name_el.text else ""

                tags: list[str] = []
                tags_el = raw_asset.find("tags")
                if tags_el is not None:
                    for tag_el in tags_el.findall("tag"):
                        if tag_el.text:
                            tags.append(tag_el.text.strip())

                cover_el = raw_asset.find("cover")
                cover_url = cover_el.text.strip() if cover_el is not None and cover_el.text else ""

                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=None,
                    url=url,
                    title=title,
                    tags=tags,
                    type=asset_type,
                    creator=Creator.RAWCATALOG,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    raw_thumbnail=WebItemReference(url=cover_url).fetch().parse_as_image() if cover_url else None,
                )
                tmp_collection.append(asset)
                count += 1

        return tmp_collection
