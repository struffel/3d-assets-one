import json
import re
import sys
import traceback
from datetime import datetime
from pathlib import Path
from typing import Any

from app.log.log_level import LogLevel


class Log:
    _enabled: bool = False
    _write_to_stdout: bool = False
    _log_name: str = ""
    _finalized: bool = False
    _log_entries: list[dict] = []

    @staticmethod
    def _get_log_base_path() -> Path:
        return Path(__file__).parent.parent.parent / "data" / "log"

    @classmethod
    def timestamp_helper(cls) -> str:
        return datetime.now().strftime("%Y-%m-%dT%H-%M-%S-%f")[:-3]

    @classmethod
    def start(cls, log_name: str, write_to_stdout: bool = False) -> None:
        if cls._enabled:
            raise RuntimeError("Logging has already been started.")
        cls._log_name = re.sub(r"[^a-zA-Z0-9/_-]", "", log_name)
        cls._enabled = True
        cls._write_to_stdout = write_to_stdout
        cls._finalized = False
        cls._log_entries = []
        cls.write("Started logging", {"path": str(cls._get_log_base_path() / cls._log_name)}, LogLevel.SYSTEM)

    @classmethod
    def stop(cls, success: bool = True) -> None:
        if cls._finalized:
            return
        cls._finalized = True
        cls._enabled = False

        suffix = ".ok.log" if success else ".err.log"
        log_path = cls._get_log_base_path() / (cls._log_name + suffix)
        log_path.parent.mkdir(parents=True, exist_ok=True)

        with open(log_path, "w", encoding="utf-8") as f:
            for entry in cls._log_entries:
                f.write(json.dumps(entry, default=str) + "\n")

    @classmethod
    def write(cls, message: str, data: Any = None, level: LogLevel = LogLevel.INFO) -> None:
        if cls._finalized:
            raise RuntimeError("Logger has already been stopped.")
        if not cls._enabled:
            return

        entry: dict[str, Any] = {
            "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "level": level.name,
            "message": message,
            "data": data,
        }
        cls._log_entries.append(entry)

        if cls._write_to_stdout:
            data_str = f": {data}" if data is not None else ""
            print(f"[{entry['time']}] [{level.name}] {message}{data_str}", file=sys.stdout)

    @classmethod
    def exception_handler(cls, exc: BaseException) -> None:
        details = {
            "message": str(exc),
            "trace": traceback.format_exc(),
        }
        cls.write("Uncaught exception", details, LogLevel.EXCEPTION)
        cls.stop(False)

    @staticmethod
    def log_is_successful(log_file_path: str) -> bool | None:
        if log_file_path.endswith(".ok.log"):
            return True
        if log_file_path.endswith(".err.log"):
            return False
        return None
