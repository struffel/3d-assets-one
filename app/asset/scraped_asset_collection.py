from typing import Iterator, Optional, TYPE_CHECKING

if TYPE_CHECKING:
    from app.asset.scraped_asset import ScrapedAsset


class ScrapedAssetCollection:
    def __init__(
        self,
        assets: "list[ScrapedAsset] | None" = None,
        pending_to_be_scraped_count: Optional[int] = None,
    ) -> None:
        self._assets: "list[ScrapedAsset]" = assets or []
        self.pending_to_be_scraped_count = pending_to_be_scraped_count

    def append(self, asset: "ScrapedAsset") -> None:
        self._assets.append(asset)

    def __len__(self) -> int:
        return len(self._assets)

    def __iter__(self) -> "Iterator[ScrapedAsset]":
        return iter(self._assets)

    def __getitem__(self, index: int) -> "ScrapedAsset":
        return self._assets[index]
