from urllib.parse import unquote, urlparse

from app.asset.asset_type import AssetType
from app.asset.scraped_asset import ScrapedAsset
from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.scraped_asset_status import ScrapedAssetStatus
from app.asset.stored_asset_collection import StoredAssetCollection
from app.creator.creator import Creator
from app.creator.creator_logic import CreatorLogic
from app.fetch.web_item_reference import WebItemReference


class CreatorLogicNoEmotionsHdr(CreatorLogic):
    creator = Creator.NOEMOTIONHDRS
    max_assets_per_run = 10

    url_list = [
        "http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FH",
        "http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FG",
        "http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FF",
        "http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FE",
        "http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FD",
        "http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FC",
        "http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FB",
        "http://noemotionhdrs.net/hdrday.html#:~:text=06%2D07%5FDay%5FA",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D28%5FDay%5FD",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D28%5FDay%5FC",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D28%5FDay%5FB",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D28%5FDay%5FA",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FG",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FF",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FE",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FD",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FC",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FB",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D18%5FDay%5FA",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FF",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FE",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FD",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FC",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FB",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D16%5FDay%5FA",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D08%5FDay%5FB",
        "http://noemotionhdrs.net/hdrday.html#:~:text=05%2D08%5FDay%5FA",
        "http://noemotionhdrs.net/hdrday.html#:~:text=04%2D23%5FDay%5FD",
        "http://noemotionhdrs.net/hdrday.html#:~:text=04%2D23%5FDay%5FC",
        "http://noemotionhdrs.net/hdrday.html#:~:text=04%2D23%5FDay%5FB",
        "http://noemotionhdrs.net/hdrday.html#:~:text=04%2D23%5FDay%5FA",
        "http://noemotionhdrs.net/hdrday.html#:~:text=04%2D18%5FCloudy",
        "http://noemotionhdrs.net/hdrday.html#:~:text=04%2D17%5FCloudy",
        "http://noemotionhdrs.net/hdrday.html#:~:text=04%2D16%5FSky",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=03%2D28%5FSunset",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=03%2D29%5FSunset",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D03%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D03%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D06%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D06%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D06%5FSun%5FC",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D11%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D11%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D11%5FSun%5FC",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D11%5FSun%5FD",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D12%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FC",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FD",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D16%5FSun%5FE",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D18%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D18%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D18%5FSun%5FC",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D23%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D23%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D23%5FSun%5FC",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D23%5FSun%5FD",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D26%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D26%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D29%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D29%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=04%2D29%5FSun%5FC",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FC",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FD",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FE",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D01%5FSun%5FG",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D11%5FSun%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D11%5FSun%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D11%5FSun%5FC",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D11%5FSun%5FD",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D23%5FSunset%5FA",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D23%5FSunset%5FB",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D23%5FSunset%5FC",
        "http://noemotionhdrs.net/hdrevening.html#:~:text=05%2D23%5FSunset%5FD",
        "http://noemotionhdrs.net/hdrnight.html#:~:text=05%2D01%5FNight%5FA",
        "http://noemotionhdrs.net/hdrnight.html#:~:text=05%2D01%5FNight%5FB",
        "http://noemotionhdrs.net/hdrnight.html#:~:text=05%2D01%5FNight%5FC",
        "http://noemotionhdrs.net/hdrnight.html#:~:text=05%2D01%5FNight%5FD",
        "http://noemotionhdrs.net/hdrindoor.html#:~:text=04%2D19%5FIndoor%5FA",
        "http://noemotionhdrs.net/hdrindoor.html#:~:text=04%2D19%5FIndoor%5FB",
        "http://noemotionhdrs.net/hdrindoor.html#:~:text=04%2D19%5FIndoor%5FC",
        "http://noemotionhdrs.net/hdrindoor.html#:~:text=04%2D21%5FIndoor%5FA",
        "http://noemotionhdrs.net/hdrindoor.html#:~:text=04%2D21%5FIndoor%5FB",
        "http://noemotionhdrs.net/hdrindoor.html#:~:text=04%2D21%5FIndoor%5FC",
    ]

    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        tmp_collection = ScrapedAssetCollection()

        for url in self.url_list:
            if existing_assets.contains_url(url) or len(tmp_collection) >= self.max_assets_per_run:
                continue

            # Parse category and name from URL
            path = urlparse(url).path
            filename = path.rsplit("/", 1)[-1].split(".")[0]  # e.g. "hdrday"
            category = filename.replace("hdr", "").capitalize()  # e.g. "day" -> "Day"

            text_part = url.split("#:~:text=")[-1] if "#:~:text=" in url else ""
            name = unquote(text_part)

            thumbnail_url = f"https://noemotionhdrs.net/Previews/772x386/{category}/{name}.jpg"

            raw_thumbnail = WebItemReference(thumbnail_url).fetch().parse_as_image()

            asset = ScrapedAsset(
                id=None,
                creator_given_id=None,
                title=name,
                url=url,
                tags=["Sky", category],
                type=AssetType.HDRI,
                creator=Creator.NOEMOTIONHDRS,
                status=ScrapedAssetStatus.NEWLY_FOUND,
                raw_thumbnail=raw_thumbnail,
            )
            tmp_collection.append(asset)

        return tmp_collection
