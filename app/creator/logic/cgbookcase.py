from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference
from app.misc.string_util import StringUtil


class CreatorLogicCgBookcase(CreatorLogic):
    creator = Creator.CGBOOKCASE
    max_assets_per_run = 5
    base_url = "https://www.cgbookcase.com/textures/"

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        dom = WebItemReference(self.base_url).fetch().parse_as_dom()
        if dom is None:
            return ScrapedAssetCollection()

        asset_links = dom.find_all("a", href=lambda h: h and "/textures/" in h)
        url_array = [
            "https://www.cgbookcase.com" + a.get("href", "") + "?source=3dassets.one"
            for a in asset_links
            if a.get("href")
        ]

        tmp_collection = ScrapedAssetCollection()
        count = 0

        for url in url_array:
            if existing_assets.contains_url(url):
                continue

            meta = WebItemReference(url).fetch().parse_html_meta_tags()
            if meta is None:
                continue
            required = {"tex1:name", "tex1:release-date", "tex1:tags", "tex1:type", "tex1:preview-image"}
            if not required.issubset(meta.keys()):
                continue

            asset = ScrapedAsset(
                id=None,
                creator_given_id=None,
                title=meta["tex1:name"],
                url=url,
                tags=StringUtil.explode_filter_trim(",", meta["tex1:tags"]),
                type=AssetType.from_tex1_tag(meta["tex1:type"]),
                creator=Creator.CGBOOKCASE,
                status=ScrapedAssetStatus.NEWLY_FOUND,
                raw_thumbnail=WebItemReference(url=meta["tex1:preview-image"]).fetch().parse_as_image(),
            )
            tmp_collection.append(asset)
            count += 1
            if count >= self.max_assets_per_run:
                break

        return tmp_collection
