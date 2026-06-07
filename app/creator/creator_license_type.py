from enum import IntEnum
from typing import Optional


class CreatorLicenseType(IntEnum):
    PUBLIC_DOMAIN = 1
    OPEN_LICENSE = 2
    ANY_LICENSE = 3

    def title(self) -> str:
        return {
            CreatorLicenseType.PUBLIC_DOMAIN: "Public Domain Only",
            CreatorLicenseType.OPEN_LICENSE: "Open License",
            CreatorLicenseType.ANY_LICENSE: "Any License",
        }[self]

    def slug(self) -> str:
        return {
            CreatorLicenseType.PUBLIC_DOMAIN: "public-domain",
            CreatorLicenseType.OPEN_LICENSE: "open",
            CreatorLicenseType.ANY_LICENSE: "any",
        }[self]

    @classmethod
    def from_slug(cls, slug: str) -> "CreatorLicenseType":
        return cls.try_from_slug(slug) or cls.ANY_LICENSE

    @classmethod
    def try_from_slug(cls, slug: str) -> Optional["CreatorLicenseType"]:
        for member in cls:
            if member.slug() == slug:
                return member
        return None
