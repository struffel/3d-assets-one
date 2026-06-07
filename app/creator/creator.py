from __future__ import annotations

import random
from datetime import datetime, timezone
from enum import IntEnum
from typing import Optional, TYPE_CHECKING

from app.creator.creator_license_type import CreatorLicenseType

if TYPE_CHECKING:
    from app.creator.creator_logic import CreatorLogic


class Creator(IntEnum):
    AMBIENTCG = 1
    POLYHAVEN = 2
    SHARETEXTURES = 3
    THREE_D_TEXTURES = 4
    CGBOOKCASE = 5
    TEXTURECAN = 6
    NOEMOTIONHDRS = 7
    GPUOPENMATLIB = 10
    RAWCATALOG = 11
    POLIIGON = 14
    TEXTURES_COM = 15
    CGMOOD = 16
    THREE_D_SCANS = 18
    LOCATION_TEXTURES = 19
    PBR_PX = 20
    TWINBRU = 21
    LIGHTBEANS = 22

    def slug(self) -> str:
        return {
            Creator.AMBIENTCG: "ambientcg",
            Creator.POLYHAVEN: "polyhaven",
            Creator.SHARETEXTURES: "sharetextures",
            Creator.THREE_D_TEXTURES: "3d-textures",
            Creator.CGBOOKCASE: "cgbookcase",
            Creator.TEXTURECAN: "texturecan",
            Creator.NOEMOTIONHDRS: "noemotionhdrs",
            Creator.GPUOPENMATLIB: "gpuopen-matlib",
            Creator.RAWCATALOG: "rawcatalog",
            Creator.POLIIGON: "poliigon",
            Creator.TEXTURES_COM: "textures-com",
            Creator.CGMOOD: "cgmood",
            Creator.THREE_D_SCANS: "three-d-scans",
            Creator.LOCATION_TEXTURES: "location-textures",
            Creator.PBR_PX: "pbr-px",
            Creator.TWINBRU: "twinbru",
            Creator.LIGHTBEANS: "lightbeans",
        }[self]

    def title(self) -> str:
        return {
            Creator.AMBIENTCG: "ambientCG",
            Creator.POLYHAVEN: "Poly Haven",
            Creator.SHARETEXTURES: "Share Textures",
            Creator.THREE_D_TEXTURES: "3D Textures",
            Creator.CGBOOKCASE: "CG Bookcase",
            Creator.TEXTURECAN: "Texture Can",
            Creator.NOEMOTIONHDRS: "NoEmotion HDRs",
            Creator.GPUOPENMATLIB: "AMD GPUOpen MaterialX Library",
            Creator.RAWCATALOG: "Raw Catalog",
            Creator.POLIIGON: "Poliigon (Free Section)",
            Creator.TEXTURES_COM: "Textures.com (Free Section)",
            Creator.CGMOOD: "CGMood (Free Section)",
            Creator.THREE_D_SCANS: "Three D Scans",
            Creator.LOCATION_TEXTURES: "Location Textures",
            Creator.PBR_PX: "PBRPX",
            Creator.TWINBRU: "Twinbru",
            Creator.LIGHTBEANS: "Lightbeans",
        }[self]

    def description(self) -> str:
        return {
            Creator.AMBIENTCG: '2000+ Public Domain materials, HDRIs and models for Physically Based Rendering.',
            Creator.POLYHAVEN: 'The Public 3D Asset Library - A combination of the websites "HDRI Haven", "Texture Haven" and "3D Model Haven."',
            Creator.SHARETEXTURES: "ShareTextures.com is creating and sharing PBR textures since 2018.",
            Creator.THREE_D_TEXTURES: "Free seamless PBR textures and unique creations in Substance Designer.",
            Creator.CGBOOKCASE: "Free PBR textures that come with all the map types needed to create photorealistic materials.",
            Creator.TEXTURECAN: "Offers free CG textures, free graphics and free patterns for 3D artists.",
            Creator.NOEMOTIONHDRS: "An older website with an impressive collection of free HDRIs.",
            Creator.GPUOPENMATLIB: "A collection of high-quality materials and related textures that is available completely for free, hosted by AMD GPUOpen. (Duplicates of materials from Polyhaven are excluded.)",
            Creator.RAWCATALOG: "A unique library that includes many ready-to-use resources for creating amazing projects in the field of video games, films, animation and visualization.",
            Creator.POLIIGON: 'Textures, models and HDRIs for photorealistic 3D rendering. Make better renders, faster. Currently, only the "Free" section is indexed.',
            Creator.TEXTURES_COM: 'Take your CG art to the next level with our highest quality content! Currently, only the "Free" section is indexed.',
            Creator.CGMOOD: 'CGMood is a fresh, fair 3D marketplace. We are a team of architects and designers with many years of experience in the 3D visualization field. Currently, only the "Free" section is indexed.',
            Creator.THREE_D_SCANS: "A collection of high-quality statues/sculptures scanned in various european museums.",
            Creator.LOCATION_TEXTURES: "Locationtextures.com is an online platform providing high quality royalty-free photo reference packs for games and film industry. We offer free packs and every pack comes with free samples.",
            Creator.PBR_PX: "We are a small team from China, passionate about CG production. Through PBRPX, we provide artists with completely free, unrestricted digital assets, allowing them to unleash their creativity.",
            Creator.TWINBRU: "Browse our library of more than 13 000 digital fabric twins to download 3D fabric textures or order physical fabric samples.",
            Creator.LIGHTBEANS: "We Connect Manufacturers with Architects and Designers - Thousands of digitized products for your projects.",
        }[self]

    def license_type(self) -> CreatorLicenseType:
        public_domain = {
            Creator.AMBIENTCG, Creator.POLYHAVEN, Creator.SHARETEXTURES,
            Creator.PBR_PX, Creator.THREE_D_TEXTURES, Creator.CGBOOKCASE,
            Creator.GPUOPENMATLIB, Creator.TEXTURECAN,
        }
        open_license = {Creator.NOEMOTIONHDRS}
        if self in public_domain:
            return CreatorLicenseType.PUBLIC_DOMAIN
        if self in open_license:
            return CreatorLicenseType.OPEN_LICENSE
        return CreatorLicenseType.ANY_LICENSE

    def license_url(self) -> Optional[str]:
        urls: dict[Creator, str] = {
            Creator.AMBIENTCG: "https://docs.ambientcg.com/license/",
            Creator.POLYHAVEN: "https://polyhaven.com/license",
            Creator.SHARETEXTURES: "https://www.sharetextures.com/p/license",
            Creator.THREE_D_TEXTURES: "https://3dtextures.me/about/",
            Creator.CGBOOKCASE: "https://www.cgbookcase.com/textures#:~:text=The%20textures%20are%20published%20under%20the%20CC0%201.0%20license",
            Creator.TEXTURECAN: "https://www.texturecan.com/terms/",
            Creator.NOEMOTIONHDRS: "https://noemotionhdrs.net/#:~:text=NoEmotionHDRs",
            Creator.RAWCATALOG: "https://rawcatalog.com/terms/",
            Creator.POLIIGON: "https://help.poliigon.com/en/articles/8749749-asset-use-licensing",
            Creator.TEXTURES_COM: "https://www.textures.com/faq-license",
            Creator.CGMOOD: "https://cgmood.com/faq",
            Creator.THREE_D_SCANS: "https://threedscans.com/info/",
            Creator.LOCATION_TEXTURES: "https://locationtextures.com/privacy-policy/",
            Creator.PBR_PX: "https://pbrpx.com/privacy-policy/",
            Creator.TWINBRU: "https://textures.twinbru.com/en/products?page=1&page_size=50",
            Creator.LIGHTBEANS: "https://lightbeans.com/en/pages/texture-terms",
        }
        return urls.get(self)

    def base_url(self) -> str:
        return {
            Creator.AMBIENTCG: "https://ambientCG.com",
            Creator.POLYHAVEN: "https://polyhaven.com",
            Creator.SHARETEXTURES: "https://sharetextures.com",
            Creator.THREE_D_TEXTURES: "https://3dtextures.me",
            Creator.CGBOOKCASE: "https://cgbookcase.com",
            Creator.TEXTURECAN: "https://texturecan.com",
            Creator.NOEMOTIONHDRS: "http://noemotionhdrs.net",
            Creator.GPUOPENMATLIB: "https://matlib.gpuopen.com/",
            Creator.RAWCATALOG: "https://rawcatalog.com",
            Creator.POLIIGON: "https://www.poliigon.com/search/free",
            Creator.TEXTURES_COM: "https://www.textures.com/free",
            Creator.CGMOOD: "https://cgmood.com/free",
            Creator.THREE_D_SCANS: "https://threedscans.com/",
            Creator.LOCATION_TEXTURES: "https://locationtextures.com/panoramas/free-panoramas/",
            Creator.PBR_PX: "https://library.pbrpx.com/",
            Creator.TWINBRU: "https://textures.twinbru.com",
            Creator.LIGHTBEANS: "https://lightbeans.com",
        }[self]

    @classmethod
    def from_value_or_slug(cls, value: object) -> "Creator":
        if isinstance(value, (int, float)) or (isinstance(value, str) and value.isdigit()):
            return cls(int(value))
        if isinstance(value, str):
            return cls.from_slug(value)
        raise ValueError(f"Cannot convert value to Creator: {value!r}")

    @classmethod
    def from_slug(cls, slug: str) -> "Creator":
        result = cls.try_from_slug(slug)
        if result is None:
            raise ValueError(f"Invalid Creator slug: {slug!r}")
        return result

    @classmethod
    def try_from_slug(cls, slug: str) -> Optional["Creator"]:
        for member in cls:
            if member.slug() == slug:
                return member
        return None

    @classmethod
    def random_scraping_target(cls, consider_availability: bool) -> "Creator":
        from app.log.log import Log
        from app.log.log_level import LogLevel

        regular_targets = [
            cls.AMBIENTCG, cls.POLYHAVEN, cls.SHARETEXTURES, cls.THREE_D_TEXTURES,
            cls.CGBOOKCASE, cls.TEXTURECAN, cls.GPUOPENMATLIB, cls.RAWCATALOG,
            cls.POLIIGON, cls.TEXTURES_COM, cls.CGMOOD, cls.THREE_D_SCANS,
            cls.LOCATION_TEXTURES, cls.PBR_PX, cls.TWINBRU, cls.LIGHTBEANS,
        ]

        while True:
            if not regular_targets:
                raise RuntimeError("No available creators for scraping.")
            target = random.choice(regular_targets)
            regular_targets.remove(target)
            if not consider_availability or target.is_available_for_scrape():
                Log.write("Selected creator for scraping", target.slug(), LogLevel.INFO)
                return target

    def increment_failed_attempts(self, now: datetime) -> None:
        from app.database.database import Database
        now_str = now.strftime("%Y-%m-%d %H:%M:%S")
        sql = (
            "INSERT INTO CreatorAvailability (creatorId, lastChecked, lastAvailable, failedAttempts) "
            "VALUES (?, ?, NULL, 1) "
            "ON CONFLICT(creatorId) DO UPDATE SET "
            "lastChecked = ?, lastAvailable = lastAvailable, failedAttempts = failedAttempts + 1"
        )
        Database.run_query(sql, [self.value, now_str, now_str])

    def reset_failed_attempts(self, now: datetime) -> None:
        from app.database.database import Database
        now_str = now.strftime("%Y-%m-%d %H:%M:%S")
        sql = (
            "INSERT INTO CreatorAvailability (creatorId, lastChecked, lastAvailable, failedAttempts) "
            "VALUES (?, ?, ?, 0) "
            "ON CONFLICT(creatorId) DO UPDATE SET "
            "lastChecked = ?, lastAvailable = ?, failedAttempts = 0"
        )
        Database.run_query(sql, [self.value, now_str, now_str, now_str, now_str])

    def is_available_for_scrape(self) -> bool:
        from app.database.database import Database
        from app.log.log import Log
        from app.log.log_level import LogLevel

        result = Database.run_query(
            "SELECT creatorId, lastChecked, lastAvailable, failedAttempts FROM CreatorAvailability WHERE creatorId = ?",
            [self.value],
        )
        row = result.fetchone()
        if row is None:
            return True

        failed_attempts = int(row[3] or 0)
        last_checked_str = row[1]
        try:
            last_checked = datetime.fromisoformat(last_checked_str).replace(tzinfo=timezone.utc)
        except (ValueError, TypeError):
            return True

        now = datetime.now(timezone.utc)
        backoff_minutes = 2 ** failed_attempts
        next_check = last_checked.replace(second=last_checked.second + backoff_minutes * 60)

        from datetime import timedelta
        next_check_time = last_checked + timedelta(minutes=backoff_minutes)
        if now < next_check_time:
            Log.write(
                "Creator not available for scraping yet due to backoff.",
                {"creator": self.slug(), "failed_attempts": failed_attempts},
                LogLevel.WARNING,
            )
            return False
        return True

    def get_logic(self) -> "CreatorLogic":
        from app.creator.logic.ambient_cg import CreatorLogicAmbientCg
        from app.creator.logic.polyhaven import CreatorLogicPolyhaven
        from app.creator.logic.share_textures import CreatorLogicShareTextures
        from app.creator.logic.three_d_textures import CreatorLogicThreeDTextures
        from app.creator.logic.cgbookcase import CreatorLogicCgBookcase
        from app.creator.logic.texture_can import CreatorLogicTextureCan
        from app.creator.logic.no_emotions_hdr import CreatorLogicNoEmotionsHdr
        from app.creator.logic.amd_gpu_open import CreatorLogicAmdGpuOpen
        from app.creator.logic.rawcatalog import CreatorLogicRawCatalog
        from app.creator.logic.poliigon import CreatorLogicPoliigon
        from app.creator.logic.textures_com import CreatorLogicTexturesCom
        from app.creator.logic.cgmood import CreatorLogicCgMood
        from app.creator.logic.three_d_scans import CreatorLogicThreeDScans
        from app.creator.logic.location_textures import CreatorLogicLocationTextures
        from app.creator.logic.pbr_px import CreatorLogicPbrPx
        from app.creator.logic.twinbru import CreatorLogicTwinbru
        from app.creator.logic.lightbeans import CreatorLogicLightbeans

        logic_map = {
            Creator.AMBIENTCG: CreatorLogicAmbientCg,
            Creator.POLYHAVEN: CreatorLogicPolyhaven,
            Creator.SHARETEXTURES: CreatorLogicShareTextures,
            Creator.THREE_D_TEXTURES: CreatorLogicThreeDTextures,
            Creator.CGBOOKCASE: CreatorLogicCgBookcase,
            Creator.TEXTURECAN: CreatorLogicTextureCan,
            Creator.NOEMOTIONHDRS: CreatorLogicNoEmotionsHdr,
            Creator.GPUOPENMATLIB: CreatorLogicAmdGpuOpen,
            Creator.RAWCATALOG: CreatorLogicRawCatalog,
            Creator.POLIIGON: CreatorLogicPoliigon,
            Creator.TEXTURES_COM: CreatorLogicTexturesCom,
            Creator.CGMOOD: CreatorLogicCgMood,
            Creator.THREE_D_SCANS: CreatorLogicThreeDScans,
            Creator.LOCATION_TEXTURES: CreatorLogicLocationTextures,
            Creator.PBR_PX: CreatorLogicPbrPx,
            Creator.TWINBRU: CreatorLogicTwinbru,
            Creator.LIGHTBEANS: CreatorLogicLightbeans,
        }
        return logic_map[self]()
