import re

from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference


class CreatorLogicLocationTextures(CreatorLogic):
    creator = Creator.LOCATION_TEXTURES
    max_assets_per_run = 5
    indexing_base_url = "https://locationtextures.com/panoramas/free-panoramas/?page="

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        page = 1
        processed = 0

        while True:
            dom = WebItemReference(self.indexing_base_url + str(page)).fetch().parse_as_dom()
            if dom is None:
                break

            pack_links = dom.select("#product-category a.pack-link")
            found_count = len(pack_links)

            for link in pack_links:
                href = link.get("href", "")
                img_tag = link.find("img", class_="pack-link-img")
                img_src = img_tag.get("data-src", "") if img_tag else ""
                title = img_tag.get("title", "") if img_tag else ""

                if existing_assets.contains_url(href):
                    continue

                # Fetch tags from the detail page
                detail_dom = WebItemReference(href).fetch().parse_as_dom()
                tags: list[str] = []
                if detail_dom:
                    tag_links = detail_dom.find_all("a", href=lambda h: h and "?tag" in h)
                    tags = [tl.get_text(strip=True) for tl in tag_links]

                title_tags = [t for t in re.split(r"[^A-Za-z0-9]", title) if t]
                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=None,
                    title=title,
                    url=href,
                    tags=title_tags + tags,
                    type=AssetType.HDRI,
                    creator=Creator.LOCATION_TEXTURES,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    raw_thumbnail=WebItemReference(url=img_src).fetch().parse_as_image() if img_src else None,
                )
                tmp_collection.append(asset)
                processed += 1

                if processed >= self.max_assets_per_run:
                    return tmp_collection

            page += 1
            if found_count == 0 or processed >= self.max_assets_per_run:
                break

        return tmp_collection
