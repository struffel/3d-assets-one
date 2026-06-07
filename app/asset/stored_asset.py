from dataclasses import dataclass, field
from datetime import datetime, timezone
from typing import Optional, TYPE_CHECKING

from app.asset.asset import Asset
from app.asset.stored_asset_status import StoredAssetStatus

if TYPE_CHECKING:
    from app.thumbnail.thumbnail_format import ThumbnailFormat


@dataclass
class StoredAsset(Asset):
    date: datetime = field(default_factory=lambda: datetime.now(timezone.utc))
    status: StoredAssetStatus = StoredAssetStatus.ACTIVE
    last_successful_validation: Optional[datetime] = None
    clicks: int = 0

    def __post_init__(self) -> None:
        now = datetime.now(timezone.utc)
        if self.date.tzinfo is None:
            self.date = self.date.replace(tzinfo=timezone.utc)
        if self.date > now:
            self.date = now

    def get_thumbnail_url(self, fmt: "ThumbnailFormat", full_url: bool = False, base_url: str = "") -> str:
        variation = fmt.value
        extension = fmt.get_extension().lower()
        url = base_url.rstrip("/") if full_url else ""
        url += f"/thumbnail/{variation}/{self.id}.{extension}"
        return url

    def api_representation(self, fmt: "ThumbnailFormat", base_url: str = "") -> dict:
        return {
            "id": self.id,
            "creatorGivenId": self.creator_given_id,
            "title": self.title,
            "url": self.url,
            "date": self.date.isoformat(),
            "type": self.type.slug(),
            "creator": self.creator.slug(),
            "tags": self.tags,
            "thumbnail": self.get_thumbnail_url(fmt, full_url=True, base_url=base_url),
        }

    def write_to_database(self) -> None:
        from app.database.database import Database
        from app.log.log import Log
        from app.log.log_level import LogLevel

        date_str = self.date.strftime("%Y-%m-%d %H:%M:%S")
        lsv_str = self.last_successful_validation.strftime("%Y-%m-%d %H:%M:%S") if self.last_successful_validation else None

        if self.id:
            Log.write("Updating asset", self.title, LogLevel.DEBUG)
            sql = (
                "UPDATE Asset SET creatorGivenId=?, title=?, state=?, url=?, date=?, "
                "typeId=?, creatorId=?, lastSuccessfulValidation=? WHERE id = ?"
            )
            params = [
                self.creator_given_id, self.title, self.status.value, self.url,
                date_str, self.type.value, self.creator.value, lsv_str, self.id,
            ]
            Database.run_query(sql, params)
            Database.run_query("DELETE FROM Tag WHERE id = ?", [self.id])
            for tag in self.tags:
                Database.run_query("INSERT INTO Tag (id, tag) VALUES (?, ?)", [self.id, tag])
        else:
            Log.write("Inserting new asset", self.title, LogLevel.DEBUG)
            sql = (
                "INSERT INTO Asset (id, creatorGivenId, state, title, url, date, clicks, typeId, creatorId) "
                "VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)"
            )
            params = [
                self.creator_given_id, self.status.value, self.title, self.url,
                date_str, 0, self.type.value, self.creator.value,
            ]
            cursor = Database.run_query(sql, params)
            self.id = cursor.lastrowid
            for tag in self.tags:
                Database.run_query("INSERT INTO Tag (id, tag) VALUES (?, ?)", [self.id, tag])
