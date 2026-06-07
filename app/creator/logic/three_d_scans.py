import re

from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference


class CreatorLogicThreeDScans(CreatorLogic):
    creator = Creator.THREE_D_SCANS
    indexing_base_url = "https://threedscans.com/page/"
    max_assets_per_run = 10

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()
        page = 1

        while True:
            dom = WebItemReference(self.indexing_base_url + str(page)).fetch().parse_as_dom()
            if dom is None:
                break

            article_links = dom.find_all("article")
            assets_found = 0

            for article in article_links:
                a_tag = article.find("a")
                if not a_tag:
                    continue

                href = a_tag.get("href", "")
                title = a_tag.get("title", "")
                img_tag = a_tag.find("img", class_="frontPageImg")
                img_src = img_tag.get("src", "") if img_tag else ""

                assets_found += 1

                if existing_assets.contains_url(href):
                    continue

                tags = [t for t in re.split(r"[^A-Za-z0-9]", title) if t]
                tags.extend(["statue", "sculpture"])

                asset = ScrapedAsset(
                    id=None,
                    creator_given_id=None,
                    title=title,
                    url=href,
                    tags=tags,
                    type=AssetType.MODEL_3D,
                    creator=Creator.THREE_D_SCANS,
                    status=ScrapedAssetStatus.NEWLY_FOUND,
                    raw_thumbnail=WebItemReference(url=img_src).fetch().parse_as_image() if img_src else None,
                )
                tmp_collection.append(asset)

                if len(tmp_collection) >= self.max_assets_per_run:
                    return tmp_collection

            page += 1
            if assets_found == 0:
                break

        return tmp_collection
