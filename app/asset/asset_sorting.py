from enum import Enum
from typing import Optional


class AssetSorting(str, Enum):
    POPULAR = "popular"
    LATEST = "latest"
    OLDEST = "oldest"
    RANDOM = "random"
    MOST_CLICKED = "most-clicked"
    LEAST_CLICKED = "least-clicked"
    MOST_TAGGED = "most-tagged"
    LEAST_TAGGED = "least-tagged"
    OLDEST_VALIDATION_SUCCESS = "oldest-validation-success"
    LATEST_VALIDATION_SUCCESS = "latest-validation-success"

    def slug(self) -> str:
        return self.value

    @classmethod
    def from_slug(cls, slug: str) -> "AssetSorting":
        return cls.try_from_slug(slug) or cls.LATEST

    @classmethod
    def try_from_slug(cls, slug: str) -> Optional["AssetSorting"]:
        try:
            return cls(slug)
        except ValueError:
            return None
