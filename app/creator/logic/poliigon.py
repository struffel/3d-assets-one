import re

from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference


class CreatorLogicPoliigon(CreatorLogic):
    creator = Creator.POLIIGON
    base_url = "https://www.poliigon.com"
    search_base_url = "https://www.poliigon.com/free?sort=newest&page="
    url_type_patterns = {
        r"/texture/": AssetType.PBR_MATERIAL,
        r"/model/": AssetType.MODEL_3D,
        r"/hdri/": AssetType.HDRI,
    }
    max_assets_per_run = 100

    def _extract_id(self, url: str) -> str:
        return url.rstrip("/").rsplit("/", 1)[-1]

    def _is_in_existing(self, url: str, existing: StoredAssetCollection) -> bool:
        url_id = self._extract_id(url)
        return any(self._extract_id(a.url) == url_id for a in existing)

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        page = 1

        while True:
            dom = WebItemReference(self.search_base_url + str(page)).fetch().parse_as_dom()
            if dom is None:
                break

            asset_boxes = dom.find_all("div", class_="asset-box__item-inner")
            boxes_found = len(asset_boxes)

            for box in asset_boxes:
                if len(tmp_collection) >= self.max_assets_per_run:
                    return tmp_collection

                link_tag = box.find("a", class_="asset-box__item-link")
                if not link_tag:
                    continue
                url_path = link_tag.get("href", "")
                url = self.base_url + url_path

                if self._is_in_existing(url, existing_assets):
                    continue

                name_tag = box.find(class_="asset-box__item-title-name")
                name = name_tag.get_text(strip=True) if name_tag else url_path.rsplit("/", 1)[-1]

                asset_type: AssetType | None = None
                for pattern, t in self.url_type_patterns.items():
                    if re.search(pattern, url_path, re.IGNORECASE):
                        asset_type = t
                if asset_type is None:
                    continue

                tags = [t for t in re.split(r"[\s,]+", name) if t]

                img_tag = box.find("img")
                thumbnail_url = img_tag.get("src", "") if img_tag else ""
                raw_thumbnail = WebItemReference(url=thumbnail_url).fetch().parse_as_image() if thumbnail_url else None

                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=None,
                    title=name,
                    url=url,
                    tags=tags,
                    type=asset_type,
                    creator=Creator.POLIIGON,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    raw_thumbnail=raw_thumbnail,
                )
                tmp_collection.append(asset)

            page += 1
            if boxes_found == 0 or page >= 20:
                break

        return tmp_collection
