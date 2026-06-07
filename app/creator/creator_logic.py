from abc import ABC, abstractmethod

from app.asset.scraped_asset_collection import ScrapedAssetCollection
from app.asset.stored_asset_collection import StoredAssetCollection
from app.asset.stored_asset import StoredAsset
from app.asset.scraped_asset_status import ScrapedAssetStatus


class CreatorLogic(ABC):
    def get_creator_state(self, key: str) -> str | int | None:
        from app.database.database import Database
        result = Database.run_query(
            "SELECT stateValue FROM FetchingState WHERE creatorId = ? AND stateKey = ?",
            [self.creator.value, key],
        )
        row = result.fetchone()
        return row[0] if row else None

    def set_creator_state(self, key: str, value: str | int) -> None:
        from app.database.database import Database
        Database.run_query(
            "INSERT OR REPLACE INTO FetchingState (creatorId, stateKey, stateValue) VALUES (?, ?, ?)",
            [self.creator.value, key, str(value)],
        )

    def validate_asset(self, asset: StoredAsset) -> bool:
        from app.fetch.web_item_reference import WebItemReference
        try:
            result = WebItemReference(url=asset.url).fetch()
            return result.http_status_code == 200 and result.content is not None
        except Exception:
            return False

    @abstractmethod
    def scrape_assets(self, existing_assets: StoredAssetCollection) -> ScrapedAssetCollection:
        ...
