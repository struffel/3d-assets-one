from __future__ import annotations

import copy
import re
from dataclasses import dataclass, field
from datetime import datetime, timezone
from typing import Optional
from urllib.parse import urlencode

from app.asset.asset_sorting import AssetSorting
from app.asset.asset_type import AssetType
from app.asset.stored_asset_status import StoredAssetStatus


@dataclass
class StoredAssetQuery:
    offset: int = 0
    limit: Optional[int] = None
    sort: AssetSorting = AssetSorting.LATEST
    filter_asset_id: list[int] = field(default_factory=list)
    filter_tag: list[str] = field(default_factory=list)
    filter_creator: list = field(default_factory=list)  # list[Creator]
    filter_type: list[AssetType] = field(default_factory=list)
    filter_status: Optional[StoredAssetStatus] = StoredAssetStatus.ACTIVE
    filter_license_type: object = None  # CreatorLicenseType, set in __post_init__

    def __post_init__(self) -> None:
        if self.filter_license_type is None:
            from app.creator.creator_license_type import CreatorLicenseType
            self.filter_license_type = CreatorLicenseType.ANY_LICENSE

    @staticmethod
    def asset_count_total() -> int:
        from app.database.database import Database
        result = Database.run_query("SELECT COUNT(*) AS count FROM Asset")
        row = result.fetchone()
        return int(row[0]) if row else 0

    @staticmethod
    def asset_count_by_creator(last_n_days: int | None = None) -> dict[int, int]:
        from app.database.database import Database
        sql = "SELECT creatorId, COUNT(*) AS count FROM Asset"
        if last_n_days is not None:
            sql += f" WHERE date >= datetime('now', '-{last_n_days} days')"
        sql += " GROUP BY creatorId"
        result = Database.run_query(sql)
        return {row[0]: row[1] for row in result.fetchall()}

    def to_http_get(self, include_status: bool = False) -> str:
        params: dict = {}
        params["q"] = ",".join(self.filter_tag)
        params["offset"] = self.offset
        if self.limit is not None:
            params["limit"] = self.limit
        params["sort"] = self.sort.value
        for i, aid in enumerate(self.filter_asset_id):
            params[f"id[{i}]"] = aid
        for i, c in enumerate(self.filter_creator):
            params[f"creator[{i}]"] = c.slug()
        for i, t in enumerate(self.filter_type):
            params[f"type[{i}]"] = t.slug()
        params["license"] = self.filter_license_type.slug()
        if include_status and self.filter_status is not None:
            params["status"] = self.filter_status.value
        return urlencode(params)

    @classmethod
    def from_request(
        cls,
        params: dict,
        filter_status: Optional[StoredAssetStatus] = StoredAssetStatus.ACTIVE,
    ) -> "StoredAssetQuery":
        from app.creator.creator import Creator
        from app.creator.creator_license_type import CreatorLicenseType

        # Normalize params: support both dict and QueryParams (FastAPI)
        def get_list(key: str) -> list[str]:
            if hasattr(params, "getlist"):
                return params.getlist(key)
            val = params.get(key, [])
            return val if isinstance(val, list) else [val] if val else []

        def get_str(key: str, default: str = "") -> str:
            if hasattr(params, "get"):
                val = params.get(key, default)
                return str(val) if val is not None else default
            return default

        # status filter
        if filter_status is None:
            status_val = get_str("status")
            if status_val:
                filter_status = StoredAssetStatus.try_from(int(status_val))

        # license filter
        filter_license_type = (
            CreatorLicenseType.try_from_slug(get_str("license")) or CreatorLicenseType.ANY_LICENSE
        )

        # asset id filter
        filter_asset_id = [int(i) for i in get_list("id") if i]

        # creator filter
        filter_creator = [c for slug in get_list("creator") if (c := Creator.try_from_slug(slug)) is not None]

        # type filter
        filter_type = [t for slug in get_list("type") if (t := AssetType.try_from_slug(slug)) is not None]

        # tag filter
        q = get_str("q")
        filter_tag = [t.strip() for t in re.split(r"[\s,]+", q) if t.strip()]

        limit = min(int(get_str("limit", "150") or 150), 500)
        offset = int(get_str("offset", "0") or 0)
        sort = AssetSorting.try_from_slug(get_str("sort", "")) or AssetSorting.LATEST

        return cls(
            offset=offset,
            limit=limit,
            sort=sort,
            filter_asset_id=filter_asset_id,
            filter_tag=filter_tag,
            filter_creator=filter_creator,
            filter_type=filter_type,
            filter_status=filter_status,
            filter_license_type=filter_license_type,
        )

    def _build_query(self, count_by_creator: bool = False) -> tuple[str, list]:
        from app.creator.creator import Creator as CreatorEnum

        if count_by_creator:
            sql = " SELECT creatorId, COUNT(*) AS count FROM Asset "
        else:
            sql = (
                " SELECT id, url, title, state, date, clicks, lastSuccessfulValidation, "
                "typeId, creatorId, tags FROM Asset "
            )

        params: list = []

        sql += " LEFT JOIN (SELECT id, GROUP_CONCAT(tag, ',') AS tags FROM Tag GROUP BY id) AllTags USING (id) "
        sql += " WHERE TRUE "

        for tag in self.filter_tag:
            sql += " AND id IN (SELECT id FROM Tag WHERE tag = ?) "
            params.append(tag)

        if self.filter_asset_id:
            placeholders = ",".join("?" * len(self.filter_asset_id))
            sql += f" AND id IN ({placeholders}) "
            params.extend(self.filter_asset_id)

        if self.filter_type:
            placeholders = ",".join("?" * len(self.filter_type))
            sql += f" AND typeId IN ({placeholders}) "
            params.extend(t.value for t in self.filter_type)

        # Creator filter with license consideration
        actual_creator_filter = list(CreatorEnum)
        if not count_by_creator and self.filter_creator:
            actual_creator_filter = list(self.filter_creator)
        actual_creator_filter = [
            c for c in actual_creator_filter
            if c.license_type().value <= self.filter_license_type.value
        ]

        if actual_creator_filter:
            placeholders = ",".join("?" * len(actual_creator_filter))
            sql += f" AND creatorId IN ({placeholders}) "
            params.extend(c.value for c in actual_creator_filter)

        if self.filter_status is not None:
            sql += " AND state = ? "
            params.append(self.filter_status.value)

        if not count_by_creator:
            sort_clauses = {
                AssetSorting.LATEST: " ORDER BY date DESC, id DESC ",
                AssetSorting.OLDEST: " ORDER BY date ASC, id ASC ",
                AssetSorting.RANDOM: " ORDER BY RANDOM() ",
                AssetSorting.POPULAR: " ORDER BY popularityScore DESC, date DESC, id DESC ",
                AssetSorting.LEAST_CLICKED: " ORDER BY clicks ASC ",
                AssetSorting.MOST_CLICKED: " ORDER BY clicks DESC ",
                AssetSorting.LEAST_TAGGED: " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.id = Asset.id) ASC ",
                AssetSorting.MOST_TAGGED: " ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.id = Asset.id) DESC ",
                AssetSorting.LATEST_VALIDATION_SUCCESS: " ORDER BY lastSuccessfulValidation DESC, RANDOM() ",
                AssetSorting.OLDEST_VALIDATION_SUCCESS: " ORDER BY lastSuccessfulValidation ASC, RANDOM() ",
            }
            sql += sort_clauses[self.sort]

        if not count_by_creator and self.limit is not None:
            limit = max(1, self.limit)
            offset = max(0, self.offset)
            sql += " LIMIT ? OFFSET ? "
            params.append(limit)
            params.append(offset)

        if count_by_creator:
            sql += " GROUP BY creatorId "

        return sql, params

    def execute_count_by_creator(self) -> dict[int, int]:
        from app.database.database import Database
        sql, params = self._build_query(count_by_creator=True)
        result = Database.run_query(sql, params)
        return {row[0]: row[1] for row in result.fetchall()}

    def execute(self) -> "StoredAssetCollection":
        from app.database.database import Database
        from app.asset.stored_asset import StoredAsset
        from app.asset.stored_asset_collection import StoredAssetCollection
        from app.creator.creator import Creator as CreatorEnum

        sql, params = self._build_query(count_by_creator=False)
        result = Database.run_query(sql, params)
        rows = result.fetchall()

        output = StoredAssetCollection()

        if self.limit is not None and len(rows) == self.limit:
            next_query = copy.copy(self)
            next_query.offset = self.offset + self.limit
            output.next_collection = next_query

        for row in rows:
            # columns: id, url, title, state, date, clicks, lastSuccessfulValidation, typeId, creatorId, tags
            tags = [t for t in (row[9] or "").split(",") if t]

            date_str = row[4]
            try:
                date = datetime.fromisoformat(date_str).replace(tzinfo=timezone.utc) if date_str else datetime.now(timezone.utc)
            except (ValueError, TypeError):
                date = datetime.now(timezone.utc)

            lsv_str = row[6]
            try:
                lsv = datetime.fromisoformat(lsv_str).replace(tzinfo=timezone.utc) if lsv_str else None
            except (ValueError, TypeError):
                lsv = None

            asset = StoredAsset(
                id=row[0],
                url=row[1],
                title=row[2],
                status=StoredAssetStatus.try_from(row[3]) or StoredAssetStatus.ACTIVE,
                date=date,
                clicks=row[5] or 0,
                last_successful_validation=lsv,
                type=AssetType(row[7]),
                creator=CreatorEnum(row[8]),
                creator_given_id=None,
                tags=tags,
            )
            output.append(asset)

        return output
