from enum import Enum
from typing import Optional


class ThumbnailFormat(str, Enum):
    JPG_32_FFFFFF = "32-JPG-FFFFFF"
    JPG_64_FFFFFF = "64-JPG-FFFFFF"
    JPG_128_FFFFFF = "128-JPG-FFFFFF"
    JPG_256_FFFFFF = "256-JPG-FFFFFF"
    PNG_32 = "32-PNG"
    PNG_64 = "64-PNG"
    PNG_128 = "128-PNG"
    PNG_256 = "256-PNG"

    def get_size(self) -> int:
        size_map = {
            ThumbnailFormat.JPG_32_FFFFFF: 32,
            ThumbnailFormat.JPG_64_FFFFFF: 64,
            ThumbnailFormat.JPG_128_FFFFFF: 128,
            ThumbnailFormat.JPG_256_FFFFFF: 256,
            ThumbnailFormat.PNG_32: 32,
            ThumbnailFormat.PNG_64: 64,
            ThumbnailFormat.PNG_128: 128,
            ThumbnailFormat.PNG_256: 256,
        }
        return size_map[self]

    def get_extension(self) -> str:
        if self in (
            ThumbnailFormat.JPG_32_FFFFFF,
            ThumbnailFormat.JPG_64_FFFFFF,
            ThumbnailFormat.JPG_128_FFFFFF,
            ThumbnailFormat.JPG_256_FFFFFF,
        ):
            return "JPG"
        return "PNG"

    def get_background_color_hex(self) -> Optional[str]:
        if self in (
            ThumbnailFormat.JPG_32_FFFFFF,
            ThumbnailFormat.JPG_64_FFFFFF,
            ThumbnailFormat.JPG_128_FFFFFF,
            ThumbnailFormat.JPG_256_FFFFFF,
        ):
            return "FFFFFF"
        return None

    @classmethod
    def try_from(cls, value: str) -> Optional["ThumbnailFormat"]:
        try:
            return cls(value)
        except ValueError:
            return None
