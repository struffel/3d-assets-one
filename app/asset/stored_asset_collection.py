from typing import Iterator, Optional, TYPE_CHECKING

if TYPE_CHECKING:
    from app.asset.stored_asset import StoredAsset
    from app.asset.stored_asset_query import StoredAssetQuery


class StoredAssetCollection:
    def __init__(
        self,
        assets: "list[StoredAsset] | None" = None,
        next_collection: "Optional[StoredAssetQuery]" = None,
    ) -> None:
        self._assets: "list[StoredAsset]" = assets or []
        self.next_collection: "Optional[StoredAssetQuery]" = next_collection

    def append(self, asset: "StoredAsset") -> None:
        self._assets.append(asset)

    def __len__(self) -> int:
        return len(self._assets)

    def __iter__(self) -> "Iterator[StoredAsset]":
        return iter(self._assets)

    def __getitem__(self, index: int) -> "StoredAsset":
        return self._assets[index]

    def contains_url(self, url: str) -> bool:
        url_lower = url.lower()
        return any(a.url.lower() == url_lower for a in self._assets)
