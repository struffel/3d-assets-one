from dataclasses import dataclass, field
from typing import Optional, TYPE_CHECKING

from app.asset.asset import Asset
from app.asset.scraped_asset_status import ScrapedAssetStatus

if TYPE_CHECKING:
    from PIL import Image
    from app.asset.stored_asset import StoredAsset


@dataclass
class ScrapedAsset(Asset):
    status: ScrapedAssetStatus = ScrapedAssetStatus.NEWLY_FOUND
    raw_thumbnail: Optional["Image.Image"] = field(default=None, repr=False)

    def to_stored_asset(self) -> "StoredAsset":
        from app.asset.stored_asset import StoredAsset
        from datetime import datetime, timezone

        return StoredAsset(
            id=self.id,
            creator_given_id=self.creator_given_id,
            title=self.title,
            url=self.url,
            date=datetime.now(timezone.utc),
            type=self.type,
            creator=self.creator,
            tags=list(self.tags),
            status=self.status.to_stored_asset_status(),
            last_successful_validation=None,
        )
