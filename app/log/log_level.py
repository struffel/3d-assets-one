from enum import IntEnum


class LogLevel(IntEnum):
    DEBUG = 1
    INFO = 2
    WARNING = 3
    ERROR = 4
    EXCEPTION = 5
    SYSTEM = 6

    @classmethod
    def from_name(cls, name: str | None) -> "LogLevel | None":
        if name is None:
            return None
        try:
            return cls[name.upper()]
        except KeyError:
            return None
