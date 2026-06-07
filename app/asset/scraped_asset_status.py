from enum import IntEnum
from typing import TYPE_CHECKING

if TYPE_CHECKING:
    from app.asset.stored_asset_status import StoredAssetStatus


class ScrapedAssetStatus(IntEnum):
    NEWLY_FOUND = 0
    UPDATED = 1
    VALIDATED = 2
    NEWLY_FOUND_FAILED = 100
    UPDATED_FAILED = 101
    VALIDATED_FAILED = 102

    def to_stored_asset_status(self) -> "StoredAssetStatus":
        from app.asset.stored_asset_status import StoredAssetStatus

        success_statuses = {
            ScrapedAssetStatus.NEWLY_FOUND,
            ScrapedAssetStatus.UPDATED,
            ScrapedAssetStatus.VALIDATED,
        }
        if self in success_statuses:
            return StoredAssetStatus.ACTIVE
        return StoredAssetStatus.VALIDATION_FAILED_RECENTLY
