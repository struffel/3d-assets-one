import pytest
from app.creator.creator import Creator
from app.creator.creator_license_type import CreatorLicenseType


def test_all_creators_have_slug():
    for c in Creator:
        slug = c.slug()
        assert slug and isinstance(slug, str)


def test_all_creators_have_title():
    for c in Creator:
        title = c.title()
        assert title and isinstance(title, str)


def test_all_creators_have_base_url():
    for c in Creator:
        url = c.base_url()
        assert url.startswith("http")


def test_from_slug_round_trip():
    for c in Creator:
        assert Creator.from_slug(c.slug()) == c


def test_from_value_or_slug_by_value():
    creator = Creator.from_value_or_slug("1")
    assert isinstance(creator, Creator)


def test_from_value_or_slug_by_slug():
    for c in Creator:
        assert Creator.from_value_or_slug(c.slug()) == c


def test_try_from_slug_invalid():
    assert Creator.try_from_slug("not-a-real-creator") is None


def test_license_type_returns_enum():
    for c in Creator:
        lt = c.license_type()
        assert isinstance(lt, CreatorLicenseType)


def test_creator_license_type_slugs():
    for lt in CreatorLicenseType:
        slug = lt.slug()
        assert slug and isinstance(slug, str)
        assert CreatorLicenseType.from_slug(slug) == lt
