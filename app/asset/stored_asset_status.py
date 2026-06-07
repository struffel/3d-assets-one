from enum import IntEnum
from typing import Optional


class StoredAssetStatus(IntEnum):
    MANUALLY_BLOCKED = -1
    PENDING = 0
    ACTIVE = 1
    VALIDATION_FAILED_RECENTLY = -2
    VALIDATION_FAILED_PERMANENTLY = -3

    @classmethod
    def try_from(cls, value: int) -> Optional["StoredAssetStatus"]:
        try:
            return cls(value)
        except ValueError:
            return None
