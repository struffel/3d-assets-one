from dataclasses import dataclass, field
from typing import Optional, TYPE_CHECKING

if TYPE_CHECKING:
    from app.asset.asset_type import AssetType
    from app.creator.creator import Creator


@dataclass
class Asset:
    id: Optional[int]
    creator_given_id: Optional[str]
    title: str
    url: str
    type: "AssetType"
    creator: "Creator"
    tags: list[str] = field(default_factory=list)
