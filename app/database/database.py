import os
import sqlite3
import threading
from pathlib import Path


class Database:
    _local = threading.local()

    @staticmethod
    def _get_db_path() -> str:
        return str(Path(__file__).parent.parent.parent / "data" / "3d1.sqlite")

    @classmethod
    def _get_connection(cls) -> sqlite3.Connection:
        if not getattr(cls._local, "connection", None):
            db_path = cls._get_db_path()
            conn = sqlite3.connect(db_path, check_same_thread=False, timeout=5.0)
            conn.execute("PRAGMA journal_mode=WAL")
            conn.execute("PRAGMA foreign_keys=ON")
            conn.isolation_level = None  # autocommit mode; use explicit BEGIN/COMMIT
            cls._local.connection = conn
            if cls._get_user_version(conn) == 0:
                cls.migrate()
        return cls._local.connection

    @staticmethod
    def _get_user_version(conn: sqlite3.Connection) -> int:
        row = conn.execute("PRAGMA user_version;").fetchone()
        return int(row[0]) if row else 0

    @classmethod
    def migrate(cls) -> None:
        conn = cls._get_connection()
        sql_dir = Path(__file__).parent / "sql"

        conn.execute("BEGIN")
        try:
            while True:
                current_version = cls._get_user_version(conn)
                next_version = current_version + 1
                migration_path = sql_dir / f"migration_{next_version}.sql"

                if migration_path.exists():
                    sql = migration_path.read_text(encoding="utf-8")
                    conn.executescript(sql)
                    conn.execute(f"PRAGMA user_version = {next_version};")
                else:
                    break
            conn.execute("COMMIT")
        except Exception:
            conn.execute("ROLLBACK")
            raise

        try:
            os.chmod(cls._get_db_path(), 0o666)
        except OSError:
            pass

    @classmethod
    def run_query(cls, sql: str, params: list | None = None) -> sqlite3.Cursor:
        conn = cls._get_connection()
        cursor = conn.execute(sql, params or [])
        return cursor

    @classmethod
    def start_transaction(cls) -> None:
        cls._get_connection().execute("BEGIN")

    @classmethod
    def commit_transaction(cls) -> None:
        cls._get_connection().execute("COMMIT")

    @classmethod
    def rollback_transaction(cls) -> None:
        cls._get_connection().execute("ROLLBACK")

    @classmethod
    def add_asset_click_by_id(cls, asset_id: int) -> None:
        cls.run_query("UPDATE Asset SET clicks = clicks + 1 WHERE id = ?", [asset_id])

    @classmethod
    def update_popularity_scores(cls) -> None:
        sql = (
            "UPDATE Asset SET popularityScore = "
            "((clicks / (ABS(JULIANDAY('now') - JULIANDAY(date)) + 1)) / "
            "(SELECT (COUNT(*) + 1) FROM Asset a2 WHERE a2.creatorId = Asset.creatorId "
            "AND a2.date >= datetime('now', '-14 days')))"
        )
        cls.run_query(sql)
